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
            'phone' => '+256700000000',
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

    public function test_a_phone_number_is_required(): void
    {
        $response = $this->post(route('register.store'), $this->payload(['phone' => '']));

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'sarah.consent@gmail.com']);
    }

    public function test_a_listed_education_level_is_saved(): void
    {
        $this->post(route('register.store'), $this->payload(['education_level' => 'Diploma']));

        $user = User::where('email', 'sarah.consent@gmail.com')->firstOrFail();
        $this->assertSame('Diploma', $user->profile->education_level);
    }

    public function test_other_education_level_saves_the_typed_value(): void
    {
        $this->post(route('register.store'), $this->payload([
            'education_level' => 'Other',
            'education_level_other' => 'Vocational training',
        ]));

        $user = User::where('email', 'sarah.consent@gmail.com')->firstOrFail();
        $this->assertSame('Vocational training', $user->profile->education_level);
    }

    public function test_other_education_level_requires_a_typed_value(): void
    {
        $response = $this->post(route('register.store'), $this->payload(['education_level' => 'Other']));

        $response->assertSessionHasErrors('education_level');
        $this->assertDatabaseMissing('users', ['email' => 'sarah.consent@gmail.com']);
    }

    public function test_preferred_language_is_always_english(): void
    {
        $this->post(route('register.store'), $this->payload(['preferred_language' => 'sw']));

        $user = User::where('email', 'sarah.consent@gmail.com')->firstOrFail();
        $this->assertSame('en', $user->profile->preferred_language);
    }
}
