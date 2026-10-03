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

    public function test_profile_page_has_a_goals_tab(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My Goals');
    }

    public function test_ai_career_mentor_tab_only_shows_when_ai_is_active(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('AI Career Mentor');

        $this->enableAi();

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('AI Career Mentor');
    }

    public function test_mentorship_dashboard_hides_the_ai_section_when_ai_is_off(): void
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
}
