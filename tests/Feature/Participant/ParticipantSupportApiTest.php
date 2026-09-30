<?php

namespace Tests\Feature\Participant;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantSupportApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsParticipant(): void
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Sanctum::actingAs($user, ['participant']);
    }

    public function test_support_returns_admin_configured_details(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('support.email', 'help@example.org', 'string', 'support', true);
        $settings->set('support.phone', '+256 700 000 001', 'string', 'support', true);
        $settings->set('support.whatsapp', '+256 (700) 000-002', 'string', 'support', true);
        $settings->set('support.hours', 'Mon–Fri, 8am–5pm', 'string', 'support', true);
        $settings->set('support.introduction', 'We are here to help.', 'string', 'support', true);

        $this->actingAsParticipant();

        $this->getJson('/api/v1/participant/support')
            ->assertOk()
            ->assertJsonPath('support.email', 'help@example.org')
            ->assertJsonPath('support.phone', '+256 700 000 001')
            ->assertJsonPath('support.whatsapp_url', 'https://wa.me/256700000002')
            ->assertJsonPath('support.hours', 'Mon–Fri, 8am–5pm')
            ->assertJsonPath('support.introduction', 'We are here to help.')
            ->assertJsonPath('support.alternate_email', null);
    }

    public function test_support_falls_back_to_defaults_when_not_configured(): void
    {
        config(['legal.support_email' => 'support@elevateher360.org']);
        $this->actingAsParticipant();

        $this->getJson('/api/v1/participant/support')
            ->assertOk()
            ->assertJsonPath('support.email', 'support@elevateher360.org')
            ->assertJsonPath('support.whatsapp_url', null)
            ->assertJsonPath('support.introduction', 'Contact the ElevateHer360 support team if you need assistance.');
    }

    public function test_support_requires_authentication(): void
    {
        $this->getJson('/api/v1/participant/support')->assertUnauthorized();
    }
}
