<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Role;
use App\Models\User;
use App\Services\JobApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApplicationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function employerOwner(): User
    {
        $user = User::factory()->create([
            'user_type' => 'employer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer']));
        Employer::create(['owner_user_id' => $user->id, 'company_name' => 'Acme', 'status' => 'approved', 'email' => $user->email]);

        return $user;
    }

    private function placementOfficer(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'placement-officer'], ['name' => 'Jobs / Placement Officer']));

        return $user;
    }

    private function participant(): User
    {
        return User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function jobFor(User $owner): Job
    {
        $employer = Employer::where('owner_user_id', $owner->id)->firstOrFail();

        return Job::create(['employer_id' => $employer->id, 'title' => 'Backend Developer', 'status' => 'published']);
    }

    public function test_applying_notifies_the_employer_placement_officer_and_applicant(): void
    {
        $owner = $this->employerOwner();
        $officer = $this->placementOfficer();
        $applicant = $this->participant();
        $job = $this->jobFor($owner);

        $this->actingAs($applicant)->post(route('jobs.apply', $job))->assertRedirect(route('jobs.applications'));

        $this->assertDatabaseHas('user_notifications', ['user_id' => $owner->id, 'type' => 'job_application_received']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $officer->id, 'type' => 'job_application_received']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $applicant->id, 'type' => 'job_application']);

        $this->assertDatabaseHas('job_application_status_history', ['status' => 'submitted']);
    }

    public function test_opening_the_employer_email_notifies_the_applicant(): void
    {
        $owner = $this->employerOwner();
        $applicant = $this->participant();
        $job = $this->jobFor($owner);

        $this->actingAs($applicant)->post(route('jobs.apply', $job));

        $notification = \App\Models\UserNotification::where('user_id', $owner->id)
            ->where('type', 'job_application_received')
            ->firstOrFail();

        $this->assertNull($notification->opened_at);

        $this->get(route('email.track', $notification->tracking_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        $this->assertNotNull($notification->fresh()->opened_at);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $applicant->id,
            'type' => 'job_application_viewed',
        ]);
    }

    public function test_applying_for_a_job_auto_assigns_an_available_mentor(): void
    {
        $owner = $this->employerOwner();
        $job = $this->jobFor($owner);
        $applicant = $this->participant();

        $mentor = User::factory()->create(['user_type' => 'mentor', 'status' => 'active', 'email_verified_at' => now()]);
        $mentor->roles()->attach(Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']));
        \App\Models\MentorProfile::create(['user_id' => $mentor->id, 'status' => 'approved', 'mentoring_areas' => ['career']]);

        $this->actingAs($applicant)->post(route('jobs.apply', $job))->assertRedirect(route('jobs.applications'));

        $this->assertDatabaseHas('mentor_matches', [
            'mentee_user_id' => $applicant->id,
            'mentor_user_id' => $mentor->id,
            'status' => 'active',
        ]);
    }

    public function test_changing_application_status_records_history(): void
    {
        $owner = $this->employerOwner();
        $applicant = $this->participant();
        $job = $this->jobFor($owner);
        $application = JobApplication::create(['job_id' => $job->id, 'user_id' => $applicant->id, 'status' => 'submitted']);

        app(JobApplicationService::class)->changeStatus($application, 'shortlisted', 'Strong profile');

        $this->assertSame('shortlisted', $application->fresh()->status);
        $this->assertDatabaseHas('job_application_status_history', [
            'job_application_id' => $application->id,
            'status' => 'shortlisted',
            'notes' => 'Strong profile',
        ]);
    }
}
