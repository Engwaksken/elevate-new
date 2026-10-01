<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserNotificationService
{
    public function send(User $user, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): UserNotification
    {
        return UserNotification::create([
            'user_id'=>$user->id,
            'type'=>$type,
            'title'=>$title,
            'message'=>$message,
            'action_url'=>$actionUrl,
            'data'=>$data ?: null,
        ]);
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

        return $ids->count();
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
