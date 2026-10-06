<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Firebase Cloud Messaging (HTTP v1) client.
 *
 * Signs a service-account JWT with OpenSSL to get an OAuth access token, so
 * no extra Composer package is needed on the host. Configure with
 * FIREBASE_PROJECT_ID and FIREBASE_CREDENTIALS (path to the service-account
 * JSON, relative to the project root or absolute). Without them, isConfigured()
 * is false and nothing is sent.
 */
class FcmClient
{
    public const RESULT_SENT = 'sent';
    public const RESULT_INVALID_TOKEN = 'invalid_token';
    public const RESULT_FAILED = 'failed';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        return (bool) config('services.fcm.enabled', true)
            && filled($this->projectId())
            && $this->credentialsPath() !== null;
    }

    /**
     * Send one message to one device token.
     *
     * @param  array<string, scalar|null>  $data  values are sent as strings
     */
    public function send(string $token, string $title, ?string $body, array $data = []): string
    {
        $message = [
            'token' => $token,
            'notification' => array_filter(['title' => $title, 'body' => $body], fn ($v) => $v !== null && $v !== ''),
            'data' => array_map(fn ($v) => (string) $v, array_filter($data, fn ($v) => $v !== null)),
            'android' => [
                'priority' => 'HIGH',
                'notification' => ['channel_id' => config('services.fcm.android_channel', 'elevateher360_updates')],
            ],
            'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send", ['message' => $message]);

        if ($response->successful()) {
            return self::RESULT_SENT;
        }

        // The token is gone (app uninstalled / token rotated) or malformed.
        $errorCode = collect($response->json('error.details', []))->pluck('errorCode')->filter()->first();
        if ($response->status() === 404 || in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            return self::RESULT_INVALID_TOKEN;
        }

        if ($response->status() === 401) {
            Cache::forget($this->cacheKey());
        }

        report(new RuntimeException('FCM send failed: HTTP '.$response->status().' '.$response->body()));

        return self::RESULT_FAILED;
    }

    private function accessToken(): string
    {
        return Cache::remember($this->cacheKey(), now()->addMinutes(50), function () {
            $credentials = $this->credentials();
            $now = time();

            $jwt = $this->jwt([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], $credentials['private_key']);

            $response = Http::asForm()->timeout(10)->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('FCM auth failed: HTTP '.$response->status().' '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    private function jwt(array $claims, string $privateKey): string
    {
        $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('FCM: could not sign the service-account JWT (check the private key).');
        }

        return $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function credentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = $this->credentialsPath();
        $json = $path ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new RuntimeException('FCM: the service-account file is missing client_email or private_key.');
        }

        return $this->credentials = $json;
    }

    private function credentialsPath(): ?string
    {
        $path = (string) config('services.fcm.credentials');
        if ($path === '') {
            return null;
        }

        $resolved = preg_match('/^([A-Za-z]:[\\\\\/]|\/)/', $path) ? $path : base_path($path);

        return is_file($resolved) && is_readable($resolved) ? $resolved : null;
    }

    private function projectId(): ?string
    {
        return config('services.fcm.project_id');
    }

    private function cacheKey(): string
    {
        return 'fcm.access_token.'.md5((string) $this->projectId());
    }
}
