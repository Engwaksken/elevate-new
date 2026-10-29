<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Models\MentorMatch;
use App\Models\ParticipantGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MentorshipDashboardTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00'); // Thursday

        $ai = AiIntegration::forProvider('openai');
        $ai->fill(['provider' => 'openai', 'model' => 'test-model', 'enabled' => true]);
        $ai->setApiKey('key');
        $ai->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function participant(string $name = 'Participant'): User
    {
        return User::factory()->create(['name' => $name, 'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_action_plan_and_saved_answers_tabs_filter_and_paginate(): void
    {
        $user = $this->participant();
        foreach ([['Polish CV', '2026-10-30', 'approved'], ['Apply to Andela', '2026-11-20', 'approved'], ['Old networking task', '2026-10-01', 'completed']] as [$title, $due, $status]) {
            CareerActionItem::create(['user_id' => $user->id, 'title' => $title, 'due_on' => $due, 'status' => $status]);
        }
        foreach (range(1, 12) as $i) {
            CareerActionItem::create(['user_id' => $user->id, 'title' => 'Bulk step '.$i, 'due_on' => '2027-01-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'status' => 'approved']);
        }
        CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => 'Interview tips: practise answers aloud.', 'saved_at' => now()]);
        CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => 'CV advice from last year.', 'saved_at' => '2025-05-01 10:00:00']);

        $this->actingAs($user);

        $this->get(route('mentorship.dashboard'))->assertOk()
            ->assertSee('data-eh-tab="action-plan"', false)->assertSee('data-eh-tab="saved-answers"', false)
            ->assertSee('Showing 1–10 of 15 steps')->assertSee('plan_page=2', false)
            ->assertDontSee('data-eh-tab="mentee-goals"', false);

        $this->get(route('mentorship.dashboard', ['plan_period' => 'week']))->assertSee('Polish CV')->assertDontSee('Apply to Andela')->assertDontSee('Bulk step');
        $this->get(route('mentorship.dashboard', ['plan_q' => 'andela']))->assertSee('Apply to Andela')->assertDontSee('Polish CV');
        $this->get(route('mentorship.dashboard', ['plan_status' => 'completed']))->assertSee('Old networking task')->assertDontSee('Polish CV');
        $this->get(route('mentorship.dashboard', ['plan_period' => 'custom', 'plan_from' => '2026-11-01', 'plan_to' => '2026-11-30']))
            ->assertSee('Apply to Andela')->assertDontSee('Polish CV');
        $this->get(route('mentorship.dashboard', ['plan_page' => 2]))->assertSee('Bulk step 12')->assertSee('Showing 11–15 of 15 steps');

        // The chat history on the same page also shows these answers, so check the saved list itself.
        $saved = fn (array $query) => $this->get(route('mentorship.dashboard', $query))->viewData('aiSaved')->pluck('content')->all();
        $this->assertSame(['Interview tips: practise answers aloud.'], $saved(['saved_q' => 'interview']));
        $this->assertSame(['Interview tips: practise answers aloud.'], $saved(['saved_period' => 'year']));
        $this->assertCount(2, $saved([]));
    }

    public function test_mentors_get_a_mentee_goals_tab_with_search_period_and_feedback_modal(): void
    {
        Mail::fake();
        $mentor = $this->participant('Mentor Mary');
        $amina = $this->participant('Amina Nansubuga');
        $grace = $this->participant('Grace Atim');
        foreach ([$amina, $grace] as $mentee) {
            MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);
        }
        $internship = ParticipantGoal::create(['user_id' => $amina->id, 'title' => 'Get IT internship', 'category' => 'career', 'target_date' => '2026-11-15']);
        ParticipantGoal::create(['user_id' => $grace->id, 'title' => 'Save for a laptop', 'category' => 'personal', 'target_date' => '2027-03-01']);

        $this->actingAs($mentor);

        $this->get(route('mentorship.dashboard'))->assertOk()
            ->assertSee('data-eh-tab="mentee-goals"', false)->assertSee('data-eh-pane="mentee-goals"', false)
            ->assertSee('Get IT internship')->assertSee('Save for a laptop')
            ->assertSee('id="mentee-goal-feedback-'.$internship->id.'"', false);

        $this->get(route('mentorship.dashboard', ['mg_q' => 'Grace']))->assertSee('Save for a laptop')->assertDontSee('Get IT internship');
        $this->get(route('mentorship.dashboard', ['mg_period' => 'year']))->assertSee('Get IT internship')->assertDontSee('Save for a laptop');
        $this->get(route('mentorship.dashboard', ['mg_period' => 'month']))->assertDontSee('Get IT internship');

        // Saving feedback returns to the filtered page with the Mentee goals tab open.
        $this->from(route('mentorship.dashboard', ['mg_q' => 'Amina']))
            ->post(route('mentorship.participant-goals.review', $internship), ['mentor_comment' => 'Apply to two firms this week.'])
            ->assertRedirect(route('mentorship.dashboard', ['mg_q' => 'Amina']).'#mentee-goals');
    }
}
