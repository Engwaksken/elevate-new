<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Services\CareerAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiConnectionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function config(string $provider, string $model, ?string $endpoint = null, array $settings = []): AiIntegration
    {
        $config = new AiIntegration(['provider' => $provider, 'model' => $model, 'endpoint' => $endpoint, 'settings' => $settings]);
        $config->setApiKey('test-key');

        return $config;
    }

    public function test_base_urls_are_completed_with_the_provider_api_path(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'OK']]]]),
            'api.anthropic.com/v1/messages' => Http::response(['content' => [['type' => 'text', 'text' => 'OK']]]),
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]]]),
            '*' => Http::response('', 404),
        ]);

        $ai = app(CareerAiService::class);

        $this->assertTrue($ai->testConnection($this->config('openai', 'gpt-4o-mini', 'https://api.openai.com/v1/'))['ok']);
        $this->assertTrue($ai->testConnection($this->config('anthropic', 'claude-haiku-5-5', 'https://api.anthropic.com'))['ok']);
        $this->assertTrue($ai->testConnection($this->config('gemini', 'gemini-2.5-flash', 'https://generativelanguage.googleapis.com/v1beta'))['ok']);
    }

    public function test_a_404_names_the_model_and_url_that_were_not_found(): void
    {
        Http::fake(['*' => Http::response('', 404)]);

        $result = app(CareerAiService::class)->testConnection($this->config('anthropic', 'claude-3-5-haiku-latest'));

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('"claude-3-5-haiku-latest"', $result['message']);
        $this->assertStringContainsString('https://api.anthropic.com/v1/messages', $result['message']);
    }

    public function test_claude_replies_after_thinking_blocks_are_read_and_temperature_is_not_sent(): void
    {
        $ai = AiIntegration::firstOrNew(['feature' => 'system_ai']);
        $ai->fill(['provider' => 'anthropic', 'model' => 'claude-opus-5-5', 'endpoint' => null, 'enabled' => true, 'settings' => ['temperature' => 0.4]]);
        $ai->setApiKey('test-key');
        $ai->save();

        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [
            ['type' => 'thinking', 'thinking' => ''],
            ['type' => 'text', 'text' => 'Career advice'],
        ]])]);

        $result = app(CareerAiService::class)->generate('test', 'system', 'question');

        $this->assertSame('Career advice', $result['text']);
        Http::assertSent(fn (Request $request) => ! array_key_exists('temperature', $request->data()));
    }
}
