<?php

namespace App\Console\Commands;

use App\Models\ParticipantDeviceToken;
use App\Models\User;
use App\Services\Push\FcmClient;
use App\Services\Push\PushNotificationService;
use Illuminate\Console\Command;

class SendTestPush extends Command
{
    protected $signature = 'push:test {email : Account to send the test push to}';

    protected $description = 'Send a test push notification to every device registered for a user';

    public function handle(FcmClient $fcm, PushNotificationService $push): int
    {
        if (! $fcm->isConfigured()) {
            $this->error('Push is not configured. Set FIREBASE_PROJECT_ID and FIREBASE_CREDENTIALS (path to the service-account JSON) in .env, then run php artisan config:clear.');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        $devices = ParticipantDeviceToken::where('user_id', $user->id)->count();
        if ($devices === 0) {
            $this->warn("{$user->name} has no registered devices. Sign in to the mobile app with this account and allow notifications first.");

            return self::FAILURE;
        }

        $result = $push->sendNow([$user->id], 'test', 'ElevateHer360 test notification', 'Push notifications are working on this device.');

        $this->info("Devices: {$devices} · sent: {$result['sent']} · removed (expired tokens): {$result['removed']} · failed: {$result['failed']}");

        if ($result['failed'] > 0) {
            $this->warn('Some sends failed; see storage/logs/laravel.log for the Firebase error.');
        }

        return $result['sent'] > 0 ? self::SUCCESS : self::FAILURE;
    }
}
