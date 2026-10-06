<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $type): User
    {
        return User::factory()->create(['user_type' => $type, 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_mentor_cannot_open_employer_pages(): void
    {
        $mentor = $this->user('mentor');

        $this->actingAs($mentor)->get('/employer/profile')->assertForbidden();
        $this->actingAs($mentor)->get('/employer/applicants')->assertForbidden();
        $this->actingAs($mentor)->get('/employer/jobs')->assertForbidden();
    }

    public function test_employer_cannot_open_mentorship_pages(): void
    {
        $employer = $this->user('employer');

        $this->actingAs($employer)->get('/mentorship')->assertForbidden();
        $this->actingAs($employer)->get('/mentorship/mentor-profile')->assertForbidden();
    }

    public function test_participant_cannot_open_mentor_or_employer_only_pages(): void
    {
        $participant = $this->user('participant');

        $this->actingAs($participant)->get('/mentorship/mentor-profile')->assertForbidden();
        $this->actingAs($participant)->get('/employer/applicants')->assertForbidden();
    }

    public function test_mentor_can_open_own_pages(): void
    {
        $mentor = $this->user('mentor');

        $this->actingAs($mentor)->get('/mentorship')->assertOk();
        $this->actingAs($mentor)->get('/mentorship/mentor-profile')->assertOk();
    }

    public function test_employer_without_company_is_sent_to_profile_setup(): void
    {
        $employer = $this->user('employer');

        $this->actingAs($employer)->get('/employer/jobs')
            ->assertRedirect(route('employer.profile.edit'));
    }

    public function test_employer_with_company_sees_jobs(): void
    {
        $employer = $this->user('employer');
        Employer::create(['owner_user_id' => $employer->id, 'company_name' => 'Acme', 'status' => 'approved']);

        $this->actingAs($employer)->get('/employer/jobs')->assertOk();
    }

    public function test_partners_are_redirected_from_participant_dashboard(): void
    {
        $this->actingAs($this->user('mentor'))->get('/dashboard')
            ->assertRedirect(route('mentorship.dashboard'));
        $this->actingAs($this->user('employer'))->get('/dashboard')
            ->assertRedirect(route('employer.jobs.index'));
    }

    public function test_partner_logout_returns_to_landing_page(): void
    {
        $this->actingAs($this->user('mentor'))->post('/partners/logout')
            ->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
