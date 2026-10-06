<?php

namespace App\Services\Push;

use App\Models\ParticipantDeviceToken;
use Illuminate\Support\Collection;

/**
 * Delivers notifications to users' registered mobile devices.
 *
 * Callers (UserNotificationService) decide who should be notified and have
 * already applied notification preferences. Sending happens after the HTTP
 * response so a slow or failing push never delays or breaks the action.
 */
class PushNotificationService
{
    public function __construct(private readonly FcmClient $fcm) {}

    /**
     * Queue a push for the given users to go out once the response is sent.
     *
     * @param  iterable<int>  $userIds
     */
    public function sendLater(iterable $userIds, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?int $notificationId = null): void
    {
        $ids = Collection::make($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty() || ! $this->fcm->isConfigured()) {
            return;
        }

        // Skip the deferred work entirely when nobody has a device registered.
        if (! ParticipantDeviceToken::whereIn('user_id', $ids->all())->exists()) {
            return;
        }

        app()->terminating(fn () => $this->sendNow($ids->all(), $type, $title, $body, $actionUrl, $notificationId));
    }

    /**
     * Send immediately. Returns counts of sent / removed (invalid) / failed.
     *
     * @param  array<int>  $userIds
     * @return array{sent:int, removed:int, failed:int}
     */
    public function sendNow(array $userIds, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?int $notificationId = null): array
    {
        $counts = ['sent' => 0, 'removed' => 0, 'failed' => 0];

        if (! $this->fcm->isConfigured()) {
            return $counts;
        }

        $data = [
            'type' => $type,
            // The app routes taps by `destination` (falls back to `type`).
            'destination' => $type,
            'action_url' => $actionUrl,
            'notification_id' => $notificationId,
        ];

        ParticipantDeviceToken::whereIn('user_id', $userIds)
            ->orderBy('id')
            ->chunkById(200, function ($devices) use ($title, $body, $data, &$counts) {
                foreach ($devices as $device) {
                    try {
                        $result = $this->fcm->send($device->token, $title, $body ? mb_strimwidth($body, 0, 240, '…') : null, $data);
                    } catch (\Throwable $e) {
                        report($e);
                        $counts['failed']++;

                        continue;
                    }

                    if ($result === FcmClient::RESULT_SENT) {
                        $counts['sent']++;
                    } elseif ($result === FcmClient::RESULT_INVALID_TOKEN) {
                        $device->delete();
                        $counts['removed']++;
                    } else {
                        $counts['failed']++;
                    }
                }
            });

        return $counts;
    }
}
