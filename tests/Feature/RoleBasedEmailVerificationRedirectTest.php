<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RoleBasedEmailVerificationRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function verifyLink(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_email_verification_redirects_to_the_matching_portal(): void
    {
        $participant = User::factory()->unverified()->create(['user_type' => 'participant']);
        $mentor = User::factory()->unverified()->create(['user_type' => 'mentor']);
        $employer = User::factory()->unverified()->create(['user_type' => 'employer']);
        $staff = User::factory()->unverified()->create(['user_type' => 'staff']);

        foreach ([
            [$participant, 'dashboard'],
            [$mentor, 'mentorship.dashboard'],
            [$employer, 'employer.jobs.index'],
            [$staff, 'admin.dashboard'],
        ] as [$user, $destination]) {
            $this->actingAs($user)
                ->get($this->verifyLink($user))
                ->assertRedirect(route($destination));
        }
    }

    public function test_partner_role_overrides_the_default_participant_landing_page(): void
    {
        $mentorRole = Role::firstOrCreate(['slug' => 'mentor'], ['name' => 'Mentor']);
        $user = User::factory()->unverified()->create(['user_type' => 'participant']);
        $user->roles()->attach($mentorRole);

        $this->actingAs($user)
            ->get($this->verifyLink($user))
            ->assertRedirect(route('mentorship.dashboard'));
    }
}
