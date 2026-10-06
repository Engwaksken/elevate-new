<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\Push\PushNotificationService;
use App\Support\NotificationPreferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserNotificationService
{
    public function send(User $user, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): ?UserNotification
    {
        if (! NotificationPreferences::allows($user, $type)) {
            return null;
        }

        $notification = UserNotification::create([
            'user_id'=>$user->id,
            'type'=>$type,
            'title'=>$title,
            'message'=>$message,
            'action_url'=>$actionUrl,
            'data'=>$data ?: null,
        ]);

        $this->push([$user->id], $type, $title, $message, $actionUrl, $notification->id);

        return $notification;
    }

    /**
     * Send the same in-app notification to many users with chunked bulk inserts.
     *
     * Accepts users or user ids. The current user (the actor) is skipped, as are
     * duplicates and empty ids. Returns the number of notifications written.
     */
    public function sendToMany(iterable $users, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = [], bool $skipActor = true): int
    {
        $actorId = $skipActor ? (int) auth()->id() : 0;

        $ids = Collection::make($users)
            ->map(fn ($user) => (int) ($user instanceof User ? $user->getKey() : $user))
            ->filter(fn (int $id) => $id > 0 && $id !== $actorId)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        // Honour each user's notification preferences before writing.
        $blocked = User::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'notification_preferences'])
            ->reject(fn (User $user) => NotificationPreferences::allows($user, $type))
            ->pluck('id');

        $ids = $ids->reject(fn (int $id) => $blocked->contains($id))->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();
        $payload = $data ? json_encode($data) : null;

        foreach ($ids->chunk(500) as $chunk) {
            DB::table('user_notifications')->insert($chunk->map(fn (int $id) => [
                'user_id'=>$id,
                'type'=>$type,
                'title'=>$title,
                'message'=>$message,
                'action_url'=>$actionUrl,
                'data'=>$payload,
                'created_at'=>$now,
                'updated_at'=>$now,
            ])->all());
        }

        $this->push($ids->all(), $type, $title, $message, $actionUrl);

        return $ids->count();
    }

    /** Mirror the in-app notice to the users' phones; never fails the caller. */
    private function push(array $userIds, string $type, string $title, ?string $message, ?string $actionUrl, ?int $notificationId = null): void
    {
        try {
            app(PushNotificationService::class)->sendLater($userIds, $type, $title, $message, $actionUrl, $notificationId);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Like sendToMany(), but a failure is reported instead of thrown so that a
     * notification problem never breaks the action that triggered it.
     */
    public function sendToManySafely(iterable $users, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): int
    {
        try {
            return $this->sendToMany($users, $type, $title, $message, $actionUrl, $data);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }
}
