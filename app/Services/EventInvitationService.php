<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Event invitations (staff, participants, mentors and employers) and appreciation messages
 * sent to people connected to an event.
 */
class EventInvitationService
{
    public function __construct(private readonly NotificationDispatcher $dispatcher)
    {
    }

    /** User types that can be invited, with their "select all" label. */
    public const TYPE_GROUPS = [
        'staff' => 'All staff',
        'participant' => 'All participants',
        'mentor' => 'All mentors',
        'employer' => 'All employers',
    ];

    /** Active staff, participants, mentors and employers who can be invited to events. */
    public function inviteable(): Builder
    {
        return User::query()->where('status', 'active')->whereIn('user_type', array_keys(self::TYPE_GROUPS));
    }

    /**
     * People-picker search. Groups: one per user type (see TYPE_GROUPS), and for an existing event with a
     * course or cohort, "course" / "cohort" (participants enrolled there).
     */
    public function search(?string $term, ?string $group, ?Event $event, array $exclude = []): Collection
    {
        $query = $this->inviteable()->whereNotIn('id', array_map('intval', $exclude));
        $limit = 30;

        if ($group) {
            $limit = 5000;
            match (true) {
                array_key_exists($group, self::TYPE_GROUPS) => $query->where('user_type', $group),
                $group === 'course' && $event?->course_id => $query->where('user_type', 'participant')
                    ->whereHas('enrolments', fn ($e) => $e->where('course_id', $event->course_id)->where('status', '!=', 'withdrawn')),
                $group === 'cohort' && $event?->cohort_id => $query->where('user_type', 'participant')
                    ->whereHas('enrolments', fn ($e) => $e->where('cohort_id', $event->cohort_id)->where('status', '!=', 'withdrawn')),
                default => $query->whereRaw('1 = 0'),
            };
        }

        if ($term = trim((string) $term)) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }

        return $query->orderBy('user_type', 'desc')->orderBy('name')->limit($limit)->get(['id', 'name', 'user_type'])
            ->map(fn (User $user) => ChatDirectory::row($user))->values();
    }

    /** @return array<string, string> */
    public function groups(?Event $event): array
    {
        $groups = self::TYPE_GROUPS;
        if ($event?->course_id) {
            $groups['course'] = 'Participants in '.($event->course?->title ?? 'the event course');
        }
        if ($event?->cohort_id) {
            $groups['cohort'] = 'Participants in '.($event->cohort?->name ?? 'the event cohort');
        }

        return $groups;
    }

    /**
     * Invite people who are not yet invited. Returns how many were newly invited.
     */
    public function invite(Event $event, iterable $userIds, User $by, ?string $note = null): int
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique();
        if ($ids->isEmpty()) {
            return 0;
        }

        $already = $event->invitations()->whereIn('user_id', $ids)->pluck('user_id')->map(fn ($id) => (int) $id);
        $people = $this->inviteable()->whereIn('id', $ids->diff($already))->get();
        $registered = $event->registrations()->whereIn('user_id', $people->pluck('id'))->pluck('user_id')->map(fn ($id) => (int) $id);
        $note = filled($note) ? trim($note) : null;

        foreach ($people as $person) {
            EventInvitation::create([
                'event_id' => $event->id,
                'user_id' => $person->id,
                'invited_by' => $by->id,
                'status' => $registered->contains((int) $person->id) ? 'registered' : 'invited',
                'note' => $note,
            ]);

            $this->dispatcher->notify(
                $person,
                'event_invitation',
                "You're invited: {$event->title}",
                $this->invitationText($event, $by, $note),
                $this->linkFor($person, $event),
                ['event_id' => $event->id, 'invited_by' => $by->id],
            );
        }

        return $people->count();
    }

    /**
     * Everyone connected to the event: registrants and invitees, one row each.
     *
     * @return Collection<int, array{user:User,registration:mixed,invitation:?EventInvitation,attendance:?string}>
     */
    public function people(Event $event): Collection
    {
        $event->loadMissing(['registrations.user', 'invitations.user', 'attendanceRecords']);
        $attendance = $event->attendanceRecords->keyBy('user_id');
        $rows = collect();

        foreach ($event->registrations as $registration) {
            if (! $registration->user) {
                continue;
            }
            $rows[$registration->user_id] = [
                'user' => $registration->user,
                'registration' => $registration,
                'invitation' => null,
                'attendance' => $attendance->get($registration->user_id)?->attendance_status,
            ];
        }

        foreach ($event->invitations as $invitation) {
            if (! $invitation->user) {
                continue;
            }
            $row = $rows[$invitation->user_id] ?? [
                'user' => $invitation->user,
                'registration' => null,
                'attendance' => $attendance->get($invitation->user_id)?->attendance_status,
            ];
            $row['invitation'] = $invitation;
            $rows[$invitation->user_id] = $row;
        }

        return $rows->sortBy(fn ($row) => mb_strtolower($row['user']->name))->values();
    }

    /**
     * Send an appreciation message to selected people connected to the event.
     * Ids outside the event's registrants and invitees are ignored.
     */
    public function appreciate(Event $event, iterable $userIds, User $by, string $title, string $message, bool $email = true): int
    {
        $allowed = $this->people($event)->keyBy(fn ($row) => (int) $row['user']->id);
        $recipients = collect($userIds)->map(fn ($id) => (int) $id)->unique()
            ->map(fn ($id) => $allowed->get($id)['user'] ?? null)
            ->filter(fn ($user) => $user && $user->status === 'active');

        foreach ($recipients as $user) {
            $this->dispatcher->notify(
                $user,
                'event_appreciation',
                $title,
                $message,
                $this->linkFor($user, $event),
                ['event_id' => $event->id, 'sent_by' => $by->id],
                $email,
            );
        }

        return $recipients->count();
    }

    public function linkFor(User $user, Event $event): ?string
    {
        if (ChatDirectory::isStaffSide($user)) {
            return route('admin.events.view', $event);
        }

        return route('events.show', $event);
    }

    private function invitationText(Event $event, User $by, ?string $note): string
    {
        $when = $event->starts_at?->format('l j F Y, H:i');
        $where = $event->venue ?: ucfirst((string) $event->delivery_mode);
        $text = "{$by->name} invited you to {$event->title}".($when ? " on {$when}" : '').($where ? " ({$where})" : '').'.';

        return $note ? $text."\n\n".$note : $text;
    }
}
