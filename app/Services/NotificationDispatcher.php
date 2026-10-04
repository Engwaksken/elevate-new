<?php

namespace App\Services;

use App\Mail\SystemMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the same notice in-app (user_notifications, via UserNotificationService)
 * and by email. Failures are swallowed and reported so a notification problem
 * never breaks the action that triggered it.
 */
class NotificationDispatcher
{
    public function __construct(private readonly UserNotificationService $inApp)
    {
    }

    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        array $data = [],
        bool $email = true,
    ): void {
        $notification = null;

        try {
            $notification = $this->inApp->send($user, $type, $title, $message, $actionUrl, $data);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($email) {
            $this->email($user, $title, $message, $actionUrl, $notification?->tracking_token);
        }
    }

    /**
     * @param  iterable<User|int>  $users
     */
    public function notifyMany(
        iterable $users,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        array $data = [],
        bool $email = true,
    ): int {
        $count = 0;

        foreach ($users as $user) {
            $model = $user instanceof User ? $user : User::find($user);
            if (! $model) {
                continue;
            }
            $this->notify($model, $type, $title, $message, $actionUrl, $data, $email);
            $count++;
        }

        return $count;
    }

    public function email(User $user, string $subject, ?string $message, ?string $actionUrl = null, ?string $trackingToken = null): void
    {
        if (blank($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new SystemMail(
                subjectLine: $subject,
                heading: $subject,
                lines: array_values(array_filter([$message])),
                actionUrl: $this->absolute($actionUrl),
                actionLabel: 'Open ElevateHer360',
                greeting: 'Hello '.strtok((string) $user->name, ' '),
                trackingUrl: $trackingToken ? route('email.track', $trackingToken) : null,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_starts_with($url, 'http') ? $url : url($url);
    }
}
