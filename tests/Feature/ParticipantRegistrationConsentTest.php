<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantRegistrationConsentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.consent@gmail.com',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['learning', 'jobs'],
        ], $overrides);
    }

    public function test_a_single_terms_checkbox_satisfies_both_agreements(): void
    {
        $response = $this->post(route('register.store'), $this->payload());

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'sarah.consent@gmail.com']);
    }

    public function test_interests_are_saved_to_the_profile(): void
    {
        $this->post(route('register.store'), $this->payload());

        $user = User::where('email', 'sarah.consent@gmail.com')->firstOrFail();
        $this->assertSame(['learning', 'jobs'], $user->profile->metadata['interests']);
    }

    public function test_registration_requires_the_terms_checkbox(): void
    {
        $response = $this->post(route('register.store'), $this->payload(['terms' => null]));

        $response->assertSessionHasErrors(['terms', 'privacy_policy']);
        $this->assertDatabaseMissing('users', ['email' => 'sarah.consent@gmail.com']);
    }

    public function test_an_unknown_interest_is_rejected(): void
    {
        $response = $this->post(route('register.store'), $this->payload(['interests' => ['hacking']]));

        $response->assertSessionHasErrors('interests.0');
    }
}
