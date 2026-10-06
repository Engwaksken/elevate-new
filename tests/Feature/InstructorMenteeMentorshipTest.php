<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\MenteeProfile;
use App\Models\MentorMatch;
use App\Models\MentorProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff instructors / trainers can use mentorship as mentees.
 */
class InstructorMenteeMentorshipTest extends TestCase
{
    use RefreshDatabase;

    private function staff(?string $roleSlug = null, ?string $roleName = null): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);

        if ($roleSlug) {
            $user->roles()->attach(Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleName ?? ucfirst($roleSlug)]));
        }

        return $user;
    }

    private function instructor(): User
    {
        return $this->staff('instructor', 'Instructor / Trainer');
    }

    private function mentor(string $name = 'Grace Mentor'): User
    {
        $user = User::factory()->create(['name' => $name, 'user_type' => 'mentor', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']));
        MentorProfile::create(['user_id' => $user->id, 'status' => 'approved', 'mentoring_areas' => ['leadership']]);

        return $user;
    }

    public function test_instructor_opens_the_mentorship_dashboard_in_the_admin_layout(): void
    {
        $this->actingAs($this->instructor())
            ->get(route('mentorship.dashboard'))
            ->assertOk()
            ->assertSee('My Mentorship')
            ->assertSee('Find a Mentor')
            ->assertSee('eh-admin-sidebar', false)
            ->assertSee(route('mentorship.mentors.index'), false);
    }

    public function test_instructor_sees_the_mentors_list_and_gets_a_mentee_profile(): void
    {
        $instructor = $this->instructor();
        $this->mentor('Grace Mentor');

        $this->actingAs($instructor)
            ->get(route('mentorship.mentors.index'))
            ->assertOk()
            ->assertSee('Find a Mentor')
            ->assertSee('Grace Mentor')
            ->assertSee('eh-admin-sidebar', false);

        $this->assertTrue(MenteeProfile::where('user_id', $instructor->id)->exists());
    }

    public function test_trainer_role_also_qualifies(): void
    {
        $this->actingAs($this->staff('trainer', 'Trainer'))
            ->get(route('mentorship.mentors.index'))
            ->assertOk();
    }

    public function test_instructor_requests_a_mentor_and_ends_the_match(): void
    {
        $instructor = $this->instructor();
        $mentor = $this->mentor();

        $this->actingAs($instructor)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertRedirect(route('mentorship.mentors.index'))
            ->assertSessionHasNoErrors();

        $match = MentorMatch::sole();
        $this->assertSame($instructor->id, (int) $match->mentee_user_id);
        $this->assertSame($mentor->id, (int) $match->mentor_user_id);
        $this->assertSame('active', $match->status);

        // The mentor appears on the instructor's dashboard.
        $this->actingAs($instructor)
            ->get(route('mentorship.dashboard'))
            ->assertOk()
            ->assertSee($mentor->name);

        // The instructor can schedule a session within the match.
        $this->actingAs($instructor)
            ->post(route('mentorship.sessions.store', $match), [
                'title' => 'Kick-off',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\MentorshipSession::where('mentor_match_id', $match->id)->count());

        $this->actingAs($instructor)
            ->delete(route('mentorship.matches.destroy', $match))
            ->assertRedirect(route('mentorship.mentors.index'));

        $this->assertSame('completed', $match->fresh()->status);
    }

    public function test_staff_without_the_instructor_role_is_refused(): void
    {
        $staff = $this->staff('finance', 'Finance');
        $mentor = $this->mentor();

        $this->actingAs($staff)->get(route('mentorship.dashboard'))->assertForbidden();
        $this->actingAs($staff)->get(route('mentorship.mentors.index'))->assertForbidden();
        $this->actingAs($staff)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertForbidden();

        $this->assertSame(0, MentorMatch::count());
    }

    public function test_participants_are_unaffected(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        $mentor = $this->mentor();

        $this->actingAs($participant)->get(route('mentorship.dashboard'))->assertOk()->assertSee('Find a Mentor');
        $this->actingAs($participant)->get(route('mentorship.mentors.index'))->assertOk()->assertSee($mentor->name);
        $this->actingAs($participant)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertRedirect(route('mentorship.mentors.index'));

        $this->assertSame(1, MentorMatch::where('mentee_user_id', $participant->id)->count());
    }

    public function test_employers_are_still_blocked(): void
    {
        $employer = User::factory()->create(['user_type' => 'employer', 'status' => 'active', 'email_verified_at' => now()]);
        $employer->roles()->attach(Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer']));

        $this->actingAs($employer)->get(route('mentorship.dashboard'))->assertForbidden();
        $this->actingAs($employer)->get(route('mentorship.mentors.index'))->assertForbidden();
    }

    public function test_mentors_keep_dashboard_access_but_cannot_request_mentors(): void
    {
        $mentor = $this->mentor();

        $this->actingAs($mentor)->get(route('mentorship.dashboard'))->assertOk();
        $this->actingAs($mentor)->get(route('mentorship.mentors.index'))->assertForbidden();
    }

    public function test_mentor_profile_routes_stay_mentor_only_for_instructors(): void
    {
        $this->actingAs($this->instructor())
            ->get(route('mentorship.mentor-profile.edit'))
            ->assertForbidden();
    }

    public function test_admission_assessment_gate_does_not_block_instructors(): void
    {
        $course = \App\Models\Course::create(['title' => 'Foundation', 'status' => 'published', 'pass_mark' => 60]);
        $assessment = Assessment::create(['course_id' => $course->id, 'title' => 'Platform entry', 'type' => 'quiz', 'max_attempts' => 3, 'is_published' => true, 'pass_mark' => 60]);

        $settings = app(SettingsService::class);
        $settings->set('admissions.entry_assessment_id', $assessment->id, 'integer', 'admissions', true);
        $settings->set('admissions.mentorship_requires_assessment', true, 'boolean', 'admissions', false);

        $instructor = $this->instructor();
        $mentor = $this->mentor();

        $this->actingAs($instructor)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertRedirect(route('mentorship.mentors.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, MentorMatch::count());
    }

    public function test_instructor_sidebar_links_to_mentorship(): void
    {
        $this->actingAs($this->instructor())
            ->get(route('mentorship.mentors.index'))
            ->assertOk()
            ->assertSee(route('mentorship.dashboard'), false);
    }
}
