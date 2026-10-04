<?php

namespace Tests\Feature;

use App\Models\MenteeProfile;
use App\Models\MentorMatch;
use App\Models\MentorProfile;
use App\Models\MentorshipSession;
use App\Models\MentorshipSessionReport;
use App\Models\Role;
use App\Models\User;
use App\Services\MentorRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipMatchingAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);

        return $user;
    }

    private function mentor(string $name = 'Mentor'): User
    {
        $user = User::factory()->create(['name' => $name, 'user_type' => 'mentor', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']));
        MentorProfile::create(['user_id' => $user->id, 'status' => 'approved', 'mentoring_areas' => ['career', 'leadership']]);

        return $user;
    }

    private function placementOfficer(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'placement-officer'], ['name' => 'Jobs / Placement Officer']));

        return $user;
    }

    public function test_participant_selects_a_mentor_for_a_three_month_window(): void
    {
        $participant = $this->participant();
        $mentor = $this->mentor();

        $this->actingAs($participant)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertRedirect(route('mentorship.mentors.index'));

        $match = MentorMatch::sole();
        $this->assertSame($mentor->id, $match->mentor_user_id);
        $this->assertSame('active', $match->status);
        $this->assertSame(now()->addMonths(3)->toDateString(), $match->end_date->toDateString());
    }

    public function test_participant_cannot_exceed_three_active_mentors(): void
    {
        $participant = $this->participant();
        MenteeProfile::create(['user_id' => $participant->id]);

        for ($i = 0; $i < 3; $i++) {
            MentorMatch::create([
                'mentor_user_id' => $this->mentor('Mentor '.$i)->id,
                'mentee_user_id' => $participant->id,
                'status' => 'active',
                'matched_by' => $participant->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
            ]);
        }

        $extra = $this->mentor('Mentor Four');

        $this->actingAs($participant)
            ->from(route('mentorship.mentors.index'))
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $extra->id])
            ->assertSessionHasErrors('mentor_user_id');

        $this->assertSame(3, MentorMatch::where('mentee_user_id', $participant->id)->count());
    }

    public function test_recommendations_fall_back_when_ai_is_unavailable(): void
    {
        $participant = $this->participant();
        $mentor = $this->mentor();
        $mentee = MenteeProfile::create([
            'user_id' => $participant->id,
            'preferred_mentor_areas' => ['career'],
        ]);

        $ranked = app(MentorRecommendationService::class)->recommendWithAi($mentee, 5);

        $this->assertTrue($ranked->contains('user_id', $mentor->id));
    }

    public function test_participant_and_mentor_can_submit_session_reports(): void
    {
        $participant = $this->participant();
        $mentor = $this->mentor();
        $officer = $this->placementOfficer();

        $match = MentorMatch::create([
            'mentor_user_id' => $mentor->id,
            'mentee_user_id' => $participant->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);
        $session = MentorshipSession::create(['mentor_match_id' => $match->id, 'title' => 'Session 1', 'scheduled_at' => now()]);

        $this->actingAs($participant)
            ->post(route('mentorship.sessions.reports.store', $session), ['summary' => 'Good first session'])
            ->assertRedirect();

        $this->assertDatabaseHas('mentorship_session_reports', [
            'mentorship_session_id' => $session->id,
            'role' => 'mentee',
            'submitted_by' => $participant->id,
        ]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $officer->id, 'type' => 'mentorship_session_report']);

        $this->actingAs($mentor)
            ->post(route('mentorship.sessions.reports.store', $session), ['summary' => 'Mentor reflection'])
            ->assertRedirect();

        $this->assertDatabaseHas('mentorship_session_reports', [
            'mentorship_session_id' => $session->id,
            'role' => 'mentor',
            'submitted_by' => $mentor->id,
        ]);
        $this->assertSame(2, MentorshipSessionReport::where('mentorship_session_id', $session->id)->count());
    }

    public function test_an_unrelated_user_cannot_submit_a_session_report(): void
    {
        $participant = $this->participant();
        $mentor = $this->mentor();
        $stranger = $this->participant();

        $match = MentorMatch::create([
            'mentor_user_id' => $mentor->id,
            'mentee_user_id' => $participant->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);
        $session = MentorshipSession::create(['mentor_match_id' => $match->id, 'title' => 'Session 1', 'scheduled_at' => now()]);

        $this->actingAs($stranger)
            ->post(route('mentorship.sessions.reports.store', $session), ['summary' => 'Nope'])
            ->assertForbidden();
    }
}
