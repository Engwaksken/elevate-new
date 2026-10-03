<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_participant_is_sent_to_the_verify_page(): void
    {
        $user = User::factory()->unverified()->create([
            'user_type' => 'participant',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_verified_participant_can_open_the_dashboard(): void
    {
        $user = User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_the_verify_page_shows_the_resend_action(): void
    {
        $user = User::factory()->unverified()->create([
            'user_type' => 'participant',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Resend verification email')
            ->assertSee($user->email);
    }
}
