<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\CalendarEvent;
use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Models\ParticipantGoal;
use App\Models\Role;
use App\Models\User;
use App\Services\CalendarFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerMentorHistoryAndActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');

        $ai = AiIntegration::forProvider('openai');
        $ai->fill(['provider' => 'openai', 'model' => 'test-model', 'endpoint' => 'https://ai.example.test/v1/chat/completions', 'enabled' => true]);
        $ai->setApiKey('key');
        $ai->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function fakeAnswer(string $content): void
    {
        Http::fake(['ai.example.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
    }

    public function test_answers_are_stored_and_hidden_steps_become_suggested_actions(): void
    {
        $user = $this->participant();
        $goal = ParticipantGoal::create(['user_id' => $user->id, 'title' => 'Get IT internship', 'status' => 'in_progress', 'progress_percent' => 50, 'target_date' => '2026-11-30']);

        $this->fakeAnswer("Kenneth, start with the roles you enjoy.\n\n1. List three roles.\n2. Update your CV.\n"
            .'ACTIONS: [{"title":"List three roles you enjoy","due":"2026-11-02","goal":"Get IT internship"},'
            .'{"title":"Update your CV","due":"2027-06-01","goal":"Get IT internship"},{"title":"Talk to a mentor","due":"2020-01-01","goal":""}]');

        $response = $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'How do I choose a career path?'])->assertOk();

        $this->assertStringNotContainsString('ACTIONS', $response->json('message'));
        $this->assertSame("Kenneth, start with the roles you enjoy.\n\n1. List three roles.\n2. Update your CV.", $response->json('content'));
        $this->assertCount(3, $response->json('actions'));
        $this->assertSame(['user', 'assistant'], CareerMentorMessage::where('user_id', $user->id)->orderBy('id')->pluck('role')->all());

        $actions = CareerActionItem::orderBy('id')->get();
        $this->assertSame(['2026-11-02', '2026-11-30', '2026-10-29'], $actions->map(fn ($a) => $a->due_on->toDateString())->all(), 'Dates are clamped to today .. goal target date.');
        $this->assertSame([$goal->id, $goal->id, null], $actions->pluck('participant_goal_id')->all());
        $this->assertTrue($actions->every(fn ($a) => $a->status === CareerActionItem::SUGGESTED));

        // The next question sends the stored conversation as history.
        $this->fakeAnswer('Next answer.');
        $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'And then?'])->assertOk();
        Http::assertSent(fn (Request $request) => count($request['messages']) === 4
            && $request['messages'][1]['content'] === 'How do I choose a career path?'
            && ! str_contains($request['messages'][2]['content'], 'ACTIONS'));
    }

    public function test_approved_steps_go_on_the_owners_calendar_only_and_can_be_completed(): void
    {
        $user = $this->participant();
        $this->fakeAnswer('Answer.'."\n".'ACTIONS: [{"title":"Apply to two internships","due":"2026-11-05","goal":""}]');
        $action = CareerActionItem::find($this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'Help'])->json('actions.0.id'));

        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.approve', $action), ['title' => 'Apply to three internships', 'due_on' => '2026-11-06'])
            ->assertOk()->assertJson(['status' => 'approved', 'title' => 'Apply to three internships', 'due_on' => '2026-11-06']);

        $event = CalendarEvent::sole();
        $this->assertSame('Career step: Apply to three internships', $event->title);
        $this->assertSame(CareerActionItem::CALENDAR_TYPE, $event->event_type);
        $this->assertSame($user->id, $event->responsible_user_id);

        $feed = app(CalendarFeedService::class);
        $from = Carbon::parse('2026-11-01'); $to = Carbon::parse('2026-12-01');
        $this->assertTrue($feed->entries($user, $from, $to)->contains('title', 'Career step: Apply to three internships'));
        $this->assertFalse($feed->entries($this->participant(), $from, $to)->contains('title', 'Career step: Apply to three internships'));
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $staff->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->assertFalse($feed->entries($staff, $from, $to)->contains('title', 'Career step: Apply to three internships'));

        // A step can only be approved once.
        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.approve', $action), ['title' => 'Again', 'due_on' => '2026-11-06'])->assertStatus(422);

        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.complete', $action))->assertOk()->assertJson(['status' => 'completed']);
        $this->assertSame('completed', $event->fresh()->status);
        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.complete', $action))->assertOk()->assertJson(['status' => 'approved']);
        $this->assertSame('scheduled', $event->fresh()->status);
    }

    public function test_participants_cannot_touch_each_others_answers_or_steps_and_past_dates_are_rejected(): void
    {
        $owner = $this->participant();
        $this->fakeAnswer('Answer.'."\n".'ACTIONS: [{"title":"Step","due":"2026-11-05","goal":""}]');
        $reply = $this->actingAs($owner)->postJson(route('mentorship.assistant.message'), ['message' => 'Help'])->json();

        $other = $this->participant();
        $this->actingAs($other)->patchJson(route('mentorship.assistant.save', $reply['id']))->assertNotFound();
        $this->actingAs($other)->postJson(route('mentorship.assistant.actions.approve', $reply['actions'][0]['id']), ['title' => 'x', 'due_on' => '2026-11-05'])->assertNotFound();
        $this->actingAs($other)->postJson(route('mentorship.assistant.actions.dismiss', $reply['actions'][0]['id']))->assertNotFound();

        $this->actingAs($owner)->postJson(route('mentorship.assistant.actions.approve', $reply['actions'][0]['id']), ['title' => 'Step', 'due_on' => '2026-10-01'])
            ->assertJsonValidationErrors('due_on');
        $this->actingAs($owner)->postJson(route('mentorship.assistant.actions.dismiss', $reply['actions'][0]['id']))->assertOk()->assertJson(['status' => 'dismissed']);
        $this->assertSame(0, CalendarEvent::count());
    }

    public function test_saved_answers_and_approved_steps_survive_clearing_the_chat(): void
    {
        $user = $this->participant();
        // One sequence: later Http::fake() calls for the same URL would never be reached.
        Http::fakeSequence('ai.example.test/*')
            ->push(['choices' => [['message' => ['content' => 'First answer.'."\n".'ACTIONS: [{"title":"Keep me","due":"2026-11-05","goal":""}]']]]])
            ->push(['choices' => [['message' => ['content' => 'Second answer.']]]])
            ->push(['choices' => [['message' => ['content' => 'Third answer.'."\n".'ACTIONS: [{"title":"Drop me","due":"2026-11-05","goal":""}]']]]]);
        $first = $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'One'])->json();
        $second = $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'Two'])->json();
        $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'Three']);

        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.approve', $first['actions'][0]['id']), ['title' => 'Keep me', 'due_on' => '2026-11-05'])->assertOk();
        $this->actingAs($user)->patchJson(route('mentorship.assistant.save', $second['id']))->assertOk()->assertJson(['saved' => true]);

        $this->actingAs($user)->deleteJson(route('mentorship.assistant.clear'))->assertOk();

        $this->assertEqualsCanonicalizing(['First answer.', 'Second answer.'], CareerMentorMessage::where('user_id', $user->id)->pluck('content')->all());
        $this->assertSame(['Keep me'], CareerActionItem::pluck('title')->all());
    }

    public function test_dashboard_shows_history_saved_answers_and_action_plan(): void
    {
        $user = $this->participant();
        $this->fakeAnswer('Plan your week.'."\n".'ACTIONS: [{"title":"Write a cover letter","due":"2026-11-05","goal":""}]');
        $reply = $this->actingAs($user)->postJson(route('mentorship.assistant.message'), ['message' => 'Help'])->json();
        $this->actingAs($user)->postJson(route('mentorship.assistant.actions.approve', $reply['actions'][0]['id']), ['title' => 'Write a cover letter', 'due_on' => '2026-11-05']);
        $this->actingAs($user)->patchJson(route('mentorship.assistant.save', $reply['id']));

        $this->actingAs($user)->get(route('mentorship.dashboard'))->assertOk()
            ->assertSee('My action plan')->assertSee('Write a cover letter')->assertSee('05 Nov 2026')
            ->assertSee('Saved answers')->assertSee('Plan your week.');
    }
}
