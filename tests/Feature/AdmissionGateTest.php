<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\MenteeProfile;
use App\Models\MentorProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionGateTest extends TestCase
{
    use RefreshDatabase;

    private function configure(?int $assessmentId, bool $mentorship, bool $jobs): void
    {
        $settings = app(SettingsService::class);
        $settings->set('admissions.entry_assessment_id', $assessmentId, 'integer', 'admissions', true);
        $settings->set('admissions.mentorship_requires_assessment', $mentorship, 'boolean', 'admissions', false);
        $settings->set('admissions.jobs_require_assessment', $jobs, 'boolean', 'admissions', false);
    }

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function assessment(): Assessment
    {
        $course = \App\Models\Course::create(['title' => 'Foundation', 'status' => 'published', 'pass_mark' => 60]);

        return Assessment::create(['course_id' => $course->id, 'title' => 'Platform entry', 'type' => 'quiz', 'max_attempts' => 3, 'is_published' => true, 'pass_mark' => 60]);
    }

    private function job(): Job
    {
        $owner = User::factory()->create(['user_type' => 'employer', 'status' => 'active', 'email_verified_at' => now()]);
        $owner->roles()->attach(Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer']));
        $employer = Employer::create(['owner_user_id' => $owner->id, 'company_name' => 'Acme', 'status' => 'approved']);

        return Job::create(['employer_id' => $employer->id, 'title' => 'Developer', 'status' => 'published']);
    }

    private function pass(User $user, Assessment $assessment, float $percentage): void
    {
        AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'percentage' => $percentage,
        ]);
    }

    public function test_job_application_is_blocked_until_the_entry_assessment_is_passed(): void
    {
        $assessment = $this->assessment();
        $this->configure($assessment->id, false, true);
        $job = $this->job();
        $participant = $this->participant();

        $this->actingAs($participant)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply', $job))
            ->assertSessionHas('error');

        $this->assertSame(0, JobApplication::count());

        $this->pass($participant, $assessment, 85);

        $this->actingAs($participant)
            ->post(route('jobs.apply', $job))
            ->assertRedirect(route('jobs.applications'));

        $this->assertSame(1, JobApplication::count());
    }

    public function test_mentor_selection_is_blocked_until_the_entry_assessment_is_passed(): void
    {
        $assessment = $this->assessment();
        $this->configure($assessment->id, true, false);

        $participant = $this->participant();
        MenteeProfile::create(['user_id' => $participant->id]);

        $mentor = User::factory()->create(['user_type' => 'mentor', 'status' => 'active', 'email_verified_at' => now()]);
        $mentor->roles()->attach(Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']));
        MentorProfile::create(['user_id' => $mentor->id, 'status' => 'approved']);

        $this->actingAs($participant)
            ->from(route('mentorship.mentors.index'))
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertSessionHasErrors('mentor_user_id');

        $this->pass($participant, $assessment, 70);

        $this->actingAs($participant)
            ->post(route('mentorship.mentors.store'), ['mentor_user_id' => $mentor->id])
            ->assertRedirect(route('mentorship.mentors.index'));
    }

    public function test_without_a_configured_assessment_everything_is_open(): void
    {
        $this->configure(null, false, true);
        $job = $this->job();
        $participant = $this->participant();

        $this->actingAs($participant)->post(route('jobs.apply', $job))->assertRedirect(route('jobs.applications'));

        $this->assertSame(1, JobApplication::count());
    }
}
