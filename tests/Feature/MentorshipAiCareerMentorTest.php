<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\ParticipantGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MentorshipAiCareerMentorTest extends TestCase
{
    use RefreshDatabase;

    private function activateAi(string $provider = 'openai'): void
    {
        $ai = AiIntegration::firstOrNew(['feature' => 'system_ai']);
        $ai->fill(['provider' => $provider, 'model' => 'test-model', 'endpoint' => 'https://ai.example.test/v1/chat', 'enabled' => true]);
        $ai->setApiKey('test-key');
        $ai->save();
    }

    public function test_career_mentor_answers_with_active_ai_grounded_in_goals_and_history(): void
    {
        $this->activateAi();
        Http::fake(['ai.example.test/*' => Http::response(['choices' => [['message' => ['content' => 'Focus on your data analysis goal.']]]])]);

        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        ParticipantGoal::create(['user_id' => $user->id, 'title' => 'Become a data analyst', 'description' => 'Learn SQL and Excel',
            'category' => 'career', 'priority' => 'high', 'status' => 'in_progress', 'progress_percent' => 40, 'mentor_comment' => 'Build a portfolio project']);

        $this->actingAs($user)
            ->postJson(route('mentorship.assistant.message'), [
                'message' => 'What should I do next?',
                'history' => [
                    ['role' => 'user', 'content' => 'Hi'],
                    ['role' => 'assistant', 'content' => 'Hello! How can I help?'],
                ],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Focus on your data analysis goal.', 'source' => 'ai']);

        Http::assertSent(function (Request $request) {
            $messages = $request['messages'];

            return $request['model'] === 'test-model'
                && str_contains($messages[0]['content'], 'Become a data analyst')
                && str_contains($messages[0]['content'], 'Build a portfolio project')
                && $messages[1] === ['role' => 'user', 'content' => 'Hi']
                && $messages[2] === ['role' => 'assistant', 'content' => 'Hello! How can I help?']
                && end($messages) === ['role' => 'user', 'content' => 'What should I do next?'];
        });
    }

    public function test_career_mentor_falls_back_to_goal_guidance_when_ai_is_unavailable(): void
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        ParticipantGoal::create(['user_id' => $user->id, 'title' => 'Apply to 10 jobs', 'status' => 'in_progress', 'progress_percent' => 20]);

        $response = $this->actingAs($user)
            ->postJson(route('mentorship.assistant.message'), ['message' => 'How do I get hired?'])
            ->assertOk()
            ->assertJson(['source' => 'guidance']);

        $this->assertStringContainsString('Apply to 10 jobs', $response->json('message'));
    }

    public function test_chatbot_accepts_form_data_message(): void
    {
        $this->post(route('support.chatbot.message'), ['message' => 'How do I find jobs?'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['message', 'links']);
    }
}
