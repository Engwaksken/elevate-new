<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\AiUsageLog;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAnswerFailuresTest extends TestCase
{
    use RefreshDatabase;

    private function activateGemini(array $settings = []): AiIntegration
    {
        $ai = AiIntegration::forProvider('gemini');
        $ai->fill(['provider' => 'gemini', 'model' => 'gemini-3.8-flash', 'enabled' => true, 'settings' => $settings]);
        $ai->setApiKey('gemini-key');
        $ai->save();

        return $ai;
    }

    public function test_gemini_replies_split_over_parts_with_thoughts_are_joined(): void
    {
        $this->activateGemini();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['text' => 'thinking...', 'thought' => true],
            ['text' => 'Start with '],
            ['text' => 'your strengths.'],
        ]], 'finishReason' => 'STOP']]])]);

        $this->assertSame('Start with your strengths.', app(CareerAiService::class)->generate('mentorship_assistant', 'system', 'How do I choose a career path?')['text']);
    }

    public function test_empty_or_blocked_answers_fail_with_a_recorded_reason(): void
    {
        $this->activateGemini();
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['candidates' => [['content' => ['parts' => []], 'finishReason' => 'MAX_TOKENS']]])
            ->push(['promptFeedback' => ['blockReason' => 'SAFETY']]);

        foreach (['finish reason: MAX_TOKENS', 'Gemini blocked the request (SAFETY)'] as $expected) {
            try {
                app(CareerAiService::class)->generate('mentorship_assistant', 'system', 'question');
                $this->fail('An empty answer must not count as success.');
            } catch (\RuntimeException $e) {
                $this->assertSame('AI_UNAVAILABLE', $e->getMessage());
            }
            $this->assertStringContainsString($expected, AiUsageLog::latest('id')->first()->metadata['error']);
        }
        $this->assertSame(0, AiUsageLog::where('status', 'success')->count());
    }

    public function test_connection_test_fails_when_the_model_returns_no_text_and_shows_the_answer_otherwise(): void
    {
        $config = new AiIntegration(['provider' => 'gemini', 'model' => 'gemini-3.8-flash', 'settings' => []]);
        $config->setApiKey('key');

        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['candidates' => [['content' => ['parts' => []], 'finishReason' => 'SAFETY']]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Follow what energises you.']]]]]]);

        $failed = app(CareerAiService::class)->testConnection($config);
        $this->assertFalse($failed['ok']);
        $this->assertStringContainsString('returned no text (finish reason: SAFETY)', $failed['message']);

        $passed = app(CareerAiService::class)->testConnection($config);
        $this->assertTrue($passed['ok']);
        $this->assertStringContainsString('Follow what energises you.', $passed['message']);
    }

    public function test_daily_limit_hits_are_recorded_and_shown_to_admins(): void
    {
        $this->activateGemini(['user_daily_limit' => 1]);
        $user = User::factory()->create();
        AiUsageLog::create(['user_id' => $user->id, 'feature' => 'mentorship_assistant', 'status' => 'success']);
        Http::fake();

        try {
            app(CareerAiService::class)->generate('mentorship_assistant', 'system', 'question', $user->id);
        } catch (\RuntimeException) {
        }

        Http::assertNothingSent();
        $this->assertSame('Daily request limit per user reached.', AiUsageLog::where('status', 'limited')->sole()->metadata['error']);

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->actingAs($admin)->get(route('admin.platform-settings.index'))
            ->assertOk()->assertSee('Recent AI problems')->assertSee('Daily request limit per user reached.');
    }
}
