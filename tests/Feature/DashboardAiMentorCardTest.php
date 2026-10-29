<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\CareerActionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAiMentorCardTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_dashboard_leads_with_the_ai_mentor_when_ai_is_on(): void
    {
        $ai = AiIntegration::forProvider('openai');
        $ai->fill(['provider' => 'openai', 'model' => 'test-model', 'enabled' => true]);
        $ai->setApiKey('key');
        $ai->save();

        $user = $this->participant();
        CareerActionItem::create(['user_id' => $user->id, 'title' => 'Polish my CV', 'due_on' => now()->addDays(3)->toDateString(), 'status' => 'approved']);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Your AI Career Mentor')
            ->assertSee('Ask my mentor')
            ->assertSee(route('mentorship.dashboard', ['ask' => 'How do I choose a career path?']), false)
            ->assertSee('Your next step:')->assertSee('Polish my CV')
            ->getContent();

        // The card comes before the statistics, i.e. first after the welcome header.
        $this->assertLessThan(strpos($html, 'class="eh-stats"'), strpos($html, 'class="ai-hero"'));
        // Sidebar link straight to the mentor tab.
        $this->assertStringContainsString(route('mentorship.dashboard').'#ai', $html);
    }

    public function test_card_is_hidden_when_ai_is_off(): void
    {
        $this->actingAs($this->participant())->get(route('dashboard'))->assertOk()->assertDontSee('Your AI Career Mentor');
    }
}
