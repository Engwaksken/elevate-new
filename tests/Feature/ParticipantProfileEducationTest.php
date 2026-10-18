<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantProfileEducationTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function update(User $user, array $fields)
    {
        return $this->actingAs($user)->put(route('profile.update'), array_merge([
            'name' => $user->name,
            'email' => $user->email,
        ], $fields));
    }

    public function test_a_listed_education_level_is_saved(): void
    {
        $user = $this->participant();

        $this->update($user, ['education_level' => 'Diploma'])->assertSessionHasNoErrors();

        $this->assertSame('Diploma', $user->fresh()->profile->education_level);
    }

    public function test_other_education_level_saves_the_typed_value(): void
    {
        $user = $this->participant();

        $this->update($user, [
            'education_level' => 'Other',
            'education_level_other' => 'Vocational training',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Vocational training', $user->fresh()->profile->education_level);
    }

    public function test_other_education_level_requires_a_typed_value(): void
    {
        $user = $this->participant();

        $this->update($user, ['education_level' => 'Other'])->assertSessionHasErrors('education_level');
    }

    public function test_preferred_language_is_always_english(): void
    {
        $user = $this->participant();

        $this->update($user, ['preferred_language' => 'sw'])->assertSessionHasNoErrors();

        $this->assertSame('en', $user->fresh()->profile->preferred_language);
    }

    public function test_an_unlisted_saved_level_shows_as_other(): void
    {
        $user = $this->participant();
        $user->profile()->create(['education_level' => 'Vocational training']);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('<option value="Other" selected>Other</option>', false)
            ->assertSee('value="Vocational training"', false)
            ->assertDontSee('name="preferred_language"', false);
    }
}
