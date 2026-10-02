<?php

namespace App\Services;

class AiProviderRegistry
{
    /**
     * Supported AI providers.
     *
     * api:    request format ('openai', 'anthropic' or 'gemini')
     * auth:   how the API key is transmitted ('bearer', 'x-api-key', 'api-key', 'x-goog-api-key' or 'none')
     * endpoint: default endpoint; null means either derived from the model (gemini) or user-supplied
     * endpoint_required: whether a user-supplied endpoint is mandatory
     * models: suggested model IDs shown as autocomplete options
     */
    public const PROVIDERS = [
        'openai' => [
            'label' => 'OpenAI',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'o3-mini', 'gpt-3.5-turbo'],
        ],
        'gemini' => [
            'label' => 'Google Gemini',
            'api' => 'gemini',
            'auth' => 'x-goog-api-key',
            'endpoint' => null,
            'endpoint_required' => false,
            'models' => ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash', 'gemini-1.5-pro'],
        ],
        'anthropic' => [
            'label' => 'Anthropic Claude',
            'api' => 'anthropic',
            'auth' => 'x-api-key',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'endpoint_required' => false,
            'models' => ['claude-opus-4-1-20250805', 'claude-sonnet-4-20250514', 'claude-3-7-sonnet-latest', 'claude-3-5-haiku-latest'],
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.deepseek.com/chat/completions',
            'endpoint_required' => false,
            'models' => ['deepseek-chat', 'deepseek-reasoner'],
        ],
        'groq' => [
            'label' => 'Groq',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'mixtral-8x7b-32768', 'gemma2-9b-it'],
        ],
        'mistral' => [
            'label' => 'Mistral AI',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.mistral.ai/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['mistral-large-latest', 'mistral-small-latest', 'codestral-latest'],
        ],
        'together' => [
            'label' => 'Together AI',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.together.xyz/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['meta-llama/Llama-3.3-70B-Instruct-Turbo', 'meta-llama/Llama-3.1-8B-Instruct-Turbo', 'mistralai/Mistral-7B-Instruct-v0.3'],
        ],
        'openrouter' => [
            'label' => 'OpenRouter',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['openai/gpt-4o', 'anthropic/claude-3.5-sonnet', 'meta-llama/llama-3.3-70b-instruct', 'google/gemini-2.0-flash-001'],
        ],
        'xai' => [
            'label' => 'xAI (Grok)',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => 'https://api.x.ai/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['grok-3', 'grok-3-mini', 'grok-2-1212'],
        ],
        'azure' => [
            'label' => 'Azure OpenAI',
            'api' => 'openai',
            'auth' => 'api-key',
            'endpoint' => null,
            'endpoint_required' => true,
            'models' => [],
        ],
        'ollama' => [
            'label' => 'Ollama (self-hosted)',
            'api' => 'openai',
            'auth' => 'none',
            'endpoint' => 'http://localhost:11434/v1/chat/completions',
            'endpoint_required' => false,
            'models' => ['llama3.2', 'qwen2.5', 'mistral'],
        ],
        'compatible' => [
            'label' => 'Custom (OpenAI-compatible)',
            'api' => 'openai',
            'auth' => 'bearer',
            'endpoint' => null,
            'endpoint_required' => true,
            'models' => [],
        ],
        'anthropic_compatible' => [
            'label' => 'Custom (Anthropic-compatible)',
            'api' => 'anthropic',
            'auth' => 'x-api-key',
            'endpoint' => null,
            'endpoint_required' => true,
            'models' => [],
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::PROVIDERS);
    }

    public static function labels(): array
    {
        return array_map(fn (array $p) => $p['label'], self::PROVIDERS);
    }

    public static function get(string $key): ?array
    {
        return self::PROVIDERS[$key] ?? null;
    }

    public static function resolve(string $provider): array
    {
        return self::get($provider) ?? ['label' => 'Custom (OpenAI-compatible)', 'api' => 'openai', 'auth' => 'bearer', 'endpoint' => null, 'endpoint_required' => true, 'models' => []];
    }
}
