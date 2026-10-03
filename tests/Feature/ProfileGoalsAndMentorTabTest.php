<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileGoalsAndMentorTabTest extends TestCase
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

    private function enableAi(): void
    {
        $config = AiIntegration::firstOrNew(['feature' => 'system_ai']);
        $config->fill([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'enabled' => true,
        ]);
        $config->setApiKey('sk-test-key');
        $config->save();
    }

    public function test_profile_page_no_longer_has_a_goals_tab(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('data-form-tab="goals"', false);
    }

    public function test_mentorship_dashboard_shows_the_ai_section_only_when_ai_is_active(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('mentorship.dashboard'))
            ->assertOk()
            ->assertDontSee('AI Career Mentor');

        $this->enableAi();

        $this->get(route('mentorship.dashboard'))
            ->assertOk()
            ->assertSee('AI Career Mentor');
    }

    public function test_participant_can_manage_goals_from_the_mentorship_page(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('mentorship.dashboard'))
            ->assertOk()
            ->assertSee('Add a new goal')
            ->assertSee('Total Goals');
    }
}
