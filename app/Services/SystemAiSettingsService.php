<?php

namespace App\Services;

use App\Models\AiIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SystemAiSettingsService
{
    public function save(Request $request): AiIntegration
    {
        if (is_string($key = $request->input('api_key'))) $request->merge(['api_key' => trim($key)]);
        $provider = (string) ($request->input('provider') ?? 'openai');
        $registry = AiProviderRegistry::resolve($provider);
        $requiredEndpoint = collect(AiProviderRegistry::PROVIDERS)
            ->filter(fn (array $p) => ($p['endpoint_required'] ?? false))
            ->keys()->all();
        $endpointRules = $registry['auth'] === 'none'
            ? ['nullable', 'url', 'max:255']
            : ['nullable', 'url:https', 'max:255'];
        $validator = Validator::make($request->all(), [
            'provider' => ['required', Rule::in(AiProviderRegistry::keys())], 'model' => ['required', 'string', 'max:190'],
            'endpoint' => ['nullable', Rule::requiredIf(in_array($provider, $requiredEndpoint, true)), ...$endpointRules],
            'api_key' => ['nullable', 'string', 'max:1000'], 'enabled' => ['nullable', 'boolean'],
            'daily_limit' => ['nullable', 'integer', 'min:1', 'max:100000'], 'user_daily_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
        ]);
        if ($validator->fails()) {
            $request->merge(['api_key' => null]);
            throw new ValidationException($validator);
        }
        $data = $validator->validated();
        return DB::transaction(function () use ($request, $data) {
            // Lock existing configuration rows so provider switches are serialized.
            AiIntegration::query()->lockForUpdate()->get();
            $config = AiIntegration::firstOrNew(['feature' => 'system_ai']);
            if ($request->boolean('enabled') && blank($data['api_key'] ?? null) && (! $config->apiKey() || ($config->exists && $config->provider !== $data['provider']))) {
                $request->merge(['api_key' => null]);
                throw ValidationException::withMessages(['api_key' => 'Enter an API key when activating a provider for the first time or switching providers.']);
            }
            if ($config->provider && $config->provider !== $data['provider']) {
                $config->encrypted_api_key = null;
                $config->endpoint = null;
            }
            $config->fill(collect($data)->only(['provider', 'model', 'endpoint'])->all());
            $config->enabled = $request->boolean('enabled');
            $config->settings = ['daily_limit' => $data['daily_limit'] ?? 1000, 'user_daily_limit' => $data['user_daily_limit'] ?? 20, 'temperature' => $data['temperature'] ?? null];
            $config->setApiKey($data['api_key'] ?? null);
            AiIntegration::where('feature', '!=', 'system_ai')->where('enabled', true)->update(['enabled' => false]);
            $config->save();
            return $config;
        });
    }

    public function test(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', Rule::in(AiProviderRegistry::keys())],
            'model' => ['required', 'string', 'max:190'],
            'endpoint' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'message' => 'Fill in the provider, model and API key before testing.'];
        }
        $data = $validator->validated();
        $apiKey = trim((string) ($data['api_key'] ?? ''));

        $config = new AiIntegration([
            'provider' => $data['provider'],
            'model' => $data['model'],
            'endpoint' => $data['endpoint'] ?? null,
            'settings' => ['temperature' => null],
        ]);

        if ($apiKey !== '') {
            $config->setApiKey($apiKey);
        } else {
            $saved = AiIntegration::where('feature', 'system_ai')->first();
            if (! $saved || ! $saved->apiKey()) {
                return ['ok' => false, 'message' => 'Enter an API key to test the connection.'];
            }
            $config->encrypted_api_key = $saved->encrypted_api_key;
        }

        return app(CareerAiService::class)->testConnection($config);
    }
}
