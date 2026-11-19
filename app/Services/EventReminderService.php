<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Automatic event reminders for everyone registered for or invited to an
 * event (staff and participants).
 *
 * Every new event gets DEFAULTS (1 day and 1 hour before) which staff can
 * change or remove on the event page. A reminder is sent once per person and
 * channel (event_reminder_deliveries) once it is due, even if the scheduler
 * missed its exact minute, but only the most imminent due reminder is sent so
 * people never get "1 day" and "1 hour" notices together. People who were
 * invited or registered after a reminder fell due skip that reminder.
 */
class EventReminderService
{
    /** minutes before start => delivery method */
    public const DEFAULTS = [1440 => 'both', 60 => 'both'];

    public function __construct(
        private readonly UserNotificationService $inApp,
        private readonly NotificationDispatcher $dispatcher,
        private readonly EventInvitationService $invitations,
    ) {
    }

    public function ensureDefaults(Event $event): void
    {
        foreach (self::DEFAULTS as $minutes => $method) {
            EventReminder::firstOrCreate(
                ['event_id' => $event->id, 'minutes_before' => $minutes],
                ['delivery_method' => $method, 'is_active' => true]
            );
        }
    }

    /** Send every due reminder. Returns the number of deliveries recorded. */
    public function sendDue(?Carbon $now = null): int
    {
        $now ??= now();
        $sent = 0;

        $events = Event::query()
            ->where('starts_at', '>', $now)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereHas('invitations'))
            ->whereHas('reminders', fn ($q) => $q->where('is_active', true))
            ->with(['reminders' => fn ($q) => $q->where('is_active', true)])
            ->get();

        foreach ($events as $event) {
            // The most imminent reminder whose time has come.
            $reminder = $event->reminders
                ->filter(fn (EventReminder $r) => $now->gte($event->starts_at->copy()->subMinutes($r->minutes_before)))
                ->sortBy('minutes_before')
                ->first();
            if (! $reminder) {
                continue;
            }

            $dueAt = $event->starts_at->copy()->subMinutes($reminder->minutes_before);
            foreach ($this->recipients($event) as $row) {
                if ($row['joined_at'] && $row['joined_at']->gt($dueAt)) {
                    continue;
                }
                $sent += $this->deliver($event, $reminder, $row['user'], $row['registered']);
            }

            $reminder->update(['last_processed_at' => $now]);
        }

        return $sent;
    }

    /**
     * Registered and invited people, once each, active accounts only.
     *
     * @return Collection<int, array{user:User,registered:bool,joined_at:?Carbon}>
     */
    public function recipients(Event $event): Collection
    {
        return $this->invitations->people($event)
            ->filter(fn ($row) => $row['user']->status === 'active')
            ->map(fn ($row) => [
                'user' => $row['user'],
                'registered' => (bool) $row['registration'],
                // The earliest moment the person was connected to the event.
                'joined_at' => collect([$row['registration']?->registered_at ?? $row['registration']?->created_at, $row['invitation']?->created_at])
                    ->filter()->map(fn ($at) => Carbon::parse($at))->sort()->first(),
            ])
            ->values();
    }

    private function deliver(Event $event, EventReminder $reminder, User $user, bool $registered): int
    {
        $count = 0;
        $title = 'Event Reminder: '.$event->title;
        $message = $this->message($event, $user, $registered);
        $link = $this->invitations->linkFor($user, $event);

        foreach ($reminder->delivery_method === 'both' ? ['email', 'system'] : [$reminder->delivery_method] as $channel) {
            $already = DB::table('event_reminder_deliveries')
                ->where('event_reminder_id', $reminder->id)->where('user_id', $user->id)->where('channel', $channel)->exists();
            if ($already) {
                continue;
            }

            $error = null;
            try {
                if ($channel === 'email') {
                    $this->dispatcher->email($user, $title, $message, $link);
                } else {
                    $this->inApp->send($user, 'event_reminder', $title, $message, $link, ['event_id' => $event->id, 'reminder_id' => $reminder->id]);
                }
            } catch (\Throwable $e) {
                report($e);
                $error = $e->getMessage();
            }

            DB::table('event_reminder_deliveries')->insert([
                'event_reminder_id' => $reminder->id,
                'user_id' => $user->id,
                'channel' => $channel,
                'sent_at' => now(),
                'error_message' => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    private function message(Event $event, User $user, bool $registered): string
    {
        $where = $event->venue ?: ucfirst((string) $event->delivery_mode);
        $when = $event->starts_at->format('l j F Y').' at '.$event->starts_at->format('H:i');
        $text = $event->title.' starts '.$event->starts_at->diffForHumans(['parts' => 2]).", on {$when} ({$where}).";
        if ($event->meeting_url && $event->delivery_mode !== 'physical') {
            $text .= "\nJoin online: {$event->meeting_url}";
        }
        if (! $registered && $event->registration_required && ! ChatDirectory::isStaffSide($user)) {
            $text .= "\nYou were invited but have not registered yet. Open the event to register.";
        }

        return $text;
    }
}
