<?php

namespace Tests\Feature;

use App\Models\ParticipantDeviceToken;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\Push\PushNotificationService;
use App\Services\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Throwaway key generated for these tests only; it is not a real credential.
        $privateKey = file_get_contents(base_path('tests/Fixtures/fcm-test-private-key.pem'));

        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'fcm').'.json';
        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'client_email' => 'push@test-project.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));

        config([
            'services.fcm.enabled' => true,
            'services.fcm.project_id' => 'test-project',
            'services.fcm.credentials' => $this->credentialsPath,
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        parent::tearDown();
    }

    private function participantWithDevice(string $token = 'device-token-1'): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        ParticipantDeviceToken::create(['user_id' => $user->id, 'device_id' => 'dev-'.$token, 'token' => $token, 'platform' => 'android']);

        return $user;
    }

    private function fakeFirebase(array $sendResponses = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
            'fcm.googleapis.com/*' => $sendResponses
                ? Http::sequence($sendResponses)
                : Http::response(['name' => 'projects/test-project/messages/1']),
        ]);
    }

    public function test_sends_push_with_type_destination_and_channel(): void
    {
        $this->fakeFirebase();
        $user = $this->participantWithDevice();

        $result = app(PushNotificationService::class)->sendNow([$user->id], 'appointment_approved', 'Appointment approved', 'See you Thursday', '/appointments', 42);

        $this->assertSame(['sent' => 1, 'removed' => 0, 'failed' => 0], $result);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'fcm.googleapis.com/v1/projects/test-project/messages:send')) {
                return false;
            }
            $message = $request['message'];

            return $request->hasHeader('Authorization', 'Bearer ya29.test')
                && $message['token'] === 'device-token-1'
                && $message['notification']['title'] === 'Appointment approved'
                && $message['data']['type'] === 'appointment_approved'
                && $message['data']['destination'] === 'appointment_approved'
                && $message['data']['notification_id'] === '42'
                && $message['android']['notification']['channel_id'] === 'elevateher360_updates';
        });

        // The OAuth assertion is a signed RS256 JWT.
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'oauth2.googleapis.com')
            && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && count(explode('.', $request['assertion'])) === 3);
    }

    public function test_unregistered_tokens_are_removed(): void
    {
        $this->fakeFirebase([
            Http::response(['error' => ['code' => 404, 'status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);
        $user = $this->participantWithDevice('stale-token');

        $result = app(PushNotificationService::class)->sendNow([$user->id], 'test', 'Hello');

        $this->assertSame(1, $result['removed']);
        $this->assertDatabaseMissing('participant_device_tokens', ['token' => 'stale-token']);
    }

    public function test_in_app_notifications_are_pushed_after_the_response(): void
    {
        Mail::fake();
        $this->fakeFirebase();
        $user = $this->participantWithDevice();

        app(NotificationDispatcher::class)->notify($user, 'assignment_graded', 'Your assignment was graded', 'Score: 80%');
        Http::assertNothingSent();

        // Deferred work runs when the request/command finishes.
        app()->terminate();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'messages:send')
            && $request['message']['notification']['title'] === 'Your assignment was graded');
    }

    public function test_bulk_notifications_are_pushed_to_each_device(): void
    {
        $this->fakeFirebase();
        $a = $this->participantWithDevice('token-a');
        $b = $this->participantWithDevice('token-b');
        User::factory()->create(['user_type' => 'participant', 'status' => 'active']); // no device

        app(UserNotificationService::class)->sendToMany([$a->id, $b->id], 'announcement', 'New course announcement', null, null, [], false);
        app()->terminate();

        $sent = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'messages:send'));
        $this->assertCount(2, $sent);
    }

    public function test_muted_notification_types_are_not_pushed(): void
    {
        $this->fakeFirebase();
        $user = $this->participantWithDevice();
        $category = \App\Support\NotificationPreferences::categoryFor('appointment_approved');
        $user->forceFill(['notification_preferences' => ['disabled' => [$category]]])->save();

        $this->assertFalse(\App\Support\NotificationPreferences::allows($user->fresh(), 'appointment_approved'));
        app(UserNotificationService::class)->send($user->fresh(), 'appointment_approved', 'Approved');
        app()->terminate();

        Http::assertNothingSent();
    }

    public function test_nothing_is_sent_when_push_is_not_configured(): void
    {
        config(['services.fcm.credentials' => null]);
        Http::fake();
        $user = $this->participantWithDevice();

        app(UserNotificationService::class)->send($user, 'test', 'Hello');
        app()->terminate();

        Http::assertNothingSent();
    }

    public function test_push_test_command(): void
    {
        $this->fakeFirebase();
        $user = $this->participantWithDevice();

        $this->artisan('push:test', ['email' => $user->email])
            ->expectsOutputToContain('sent: 1')
            ->assertSuccessful();

        config(['services.fcm.credentials' => null]);
        $this->artisan('push:test', ['email' => $user->email])->assertFailed();
    }
}
