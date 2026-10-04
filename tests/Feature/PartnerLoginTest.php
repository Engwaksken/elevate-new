<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\MentorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerLoginTest extends TestCase
{
    use RefreshDatabase;

    private function employer(string $status = 'active'): User
    {
        $user = User::factory()->create([
            'user_type' => 'employer',
            'status' => $status,
            'email_verified_at' => now(),
            'password' => 'SecurePassword123',
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer']));
        Employer::create(['owner_user_id' => $user->id, 'company_name' => 'Example Co', 'status' => 'approved', 'email' => $user->email]);

        return $user;
    }

    private function mentor(string $status = 'active'): User
    {
        $user = User::factory()->create([
            'user_type' => 'mentor',
            'status' => $status,
            'email_verified_at' => now(),
            'password' => 'SecurePassword123',
        ]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']));
        MentorProfile::create(['user_id' => $user->id, 'status' => 'approved']);

        return $user;
    }

    public function test_employer_can_sign_in_through_the_employer_login(): void
    {
        $employer = $this->employer();

        $this->post(route('partners.employer.login.attempt'), [
            'email' => $employer->email,
            'password' => 'SecurePassword123',
        ])->assertRedirect(route('employer.jobs.index'));

        $this->assertAuthenticatedAs($employer);
    }

    public function test_mentor_can_sign_in_through_the_mentor_login(): void
    {
        $mentor = $this->mentor();

        $this->post(route('partners.mentor.login.attempt'), [
            'email' => $mentor->email,
            'password' => 'SecurePassword123',
        ])->assertRedirect(route('mentorship.dashboard'));

        $this->assertAuthenticatedAs($mentor);
    }

    public function test_pending_partner_cannot_sign_in_until_approved(): void
    {
        $employer = $this->employer('pending');

        $this->from(route('partners.employer.login'))
            ->post(route('partners.employer.login.attempt'), [
                'email' => $employer->email,
                'password' => 'SecurePassword123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_account_cannot_sign_in_through_the_wrong_portal(): void
    {
        $employer = $this->employer();

        $this->from(route('partners.mentor.login'))
            ->post(route('partners.mentor.login.attempt'), [
                'email' => $employer->email,
                'password' => 'SecurePassword123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_participants_cannot_sign_in_through_partner_portals(): void
    {
        $participant = User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => 'SecurePassword123',
        ]);
        $participant->roles()->attach(Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']));

        $this->from(route('partners.employer.login'))
            ->post(route('partners.employer.login.attempt'), [
                'email' => $participant->email,
                'password' => 'SecurePassword123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
