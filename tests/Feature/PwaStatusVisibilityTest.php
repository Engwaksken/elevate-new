<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_status_shows_on_participant_dashboard_only(): void
    {
        $participant = User::factory()->create(['status' => 'active']);
        $this->assertFalse($participant->isStaff());

        $this->actingAs($participant)->get('/dashboard')
            ->assertOk()
            ->assertSee('data-pwa-shell', false)
            ->assertSee('Not synced yet');

        $this->actingAs($participant)->get('/library')
            ->assertOk()
            ->assertDontSee('data-pwa-shell', false)
            ->assertSee('data-pwa-update', false);
    }

    public function test_sync_status_is_hidden_for_staff(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($staff)->get('/staff/performance')
            ->assertOk()
            ->assertDontSee('data-pwa-shell', false)
            ->assertDontSee('Not synced yet');
    }
}
