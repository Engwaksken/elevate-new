<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->actingAs($user);

        return $user;
    }

    public function test_each_provider_keeps_its_own_key_and_can_be_switched_from_the_table(): void
    {
        $this->admin();
        $this->put(route('admin.platform-settings.ai'), ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'api_key' => 'openai-key', 'enabled' => 1])->assertSessionHasNoErrors();
        $this->put(route('admin.platform-settings.ai'), ['provider' => 'gemini', 'model' => 'gemini-3.8-flash', 'api_key' => 'gemini-key', 'enabled' => 1])->assertSessionHasNoErrors();

        $openai = AiIntegration::where('feature', 'system_ai:openai')->sole();
        $gemini = AiIntegration::where('feature', 'system_ai:gemini')->sole();
        $this->assertSame('openai-key', $openai->apiKey());
        $this->assertFalse($openai->enabled);
        $this->assertTrue($gemini->enabled);

        $this->get(route('admin.platform-settings.index'))->assertOk()
            ->assertSee('Saved AI providers')->assertSee('Google Gemini')->assertSee('gemini-3.8-flash')
            ->assertDontSee('openai-key')->assertDontSee('gemini-key');

        $this->patch(route('admin.platform-settings.ai-toggle', $openai))->assertRedirect()->assertSessionHas('success', 'OpenAI is now the active AI provider.');
        $this->assertTrue($openai->fresh()->enabled);
        $this->assertFalse($gemini->fresh()->enabled);
        $this->assertSame('system_ai:openai', AiIntegration::activeSystem()->feature);

        $this->patch(route('admin.platform-settings.ai-toggle', $openai))->assertRedirect();
        $this->assertNull(AiIntegration::activeSystem());
    }

    public function test_a_provider_without_a_key_cannot_be_enabled_and_non_system_rows_cannot_be_toggled(): void
    {
        $this->admin();
        $this->put(route('admin.platform-settings.ai'), ['provider' => 'anthropic', 'model' => 'claude-opus-5-5'])->assertSessionHasNoErrors();
        $anthropic = AiIntegration::where('feature', 'system_ai:anthropic')->sole();

        $this->patch(route('admin.platform-settings.ai-toggle', $anthropic))->assertSessionHasErrors('ai_toggle');
        $this->assertFalse($anthropic->fresh()->enabled);

        $legacy = AiIntegration::create(['feature' => 'career_ai', 'provider' => 'openai', 'model' => 'x']);
        $this->patch(route('admin.platform-settings.ai-toggle', $legacy))->assertNotFound();

        $this->actingAs(User::factory()->create(['user_type' => 'staff', 'status' => 'active']))
            ->patch(route('admin.platform-settings.ai-toggle', $anthropic))->assertForbidden();
    }

    public function test_connection_failures_show_the_underlying_curl_reason(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 60: SSL certificate problem: unable to get local issuer certificate'));

        $config = new AiIntegration(['provider' => 'gemini', 'model' => 'gemini-3.8-flash', 'settings' => []]);
        $config->setApiKey('key');

        $message = app(CareerAiService::class)->testConnection($config)['message'];

        $this->assertStringContainsString('SSL certificate problem', $message);
        $this->assertStringContainsString('cacert.pem', $message);
    }
}
