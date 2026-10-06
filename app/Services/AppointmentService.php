<?php

namespace App\Services;

use App\Models\Course;
use App\Models\InstructorAppointment as Appointment;
use App\Models\Task;
use App\Models\User;
use App\Support\NotificationPreferences;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business rules for participant <-> instructor appointments.
 *
 * A participant may book any active staff member assigned (course_instructors)
 * to a course the participant is actively enrolled in. The instructor then
 * approves, declines or proposes another time; the participant can accept or
 * decline a proposal and cancel their own bookings.
 */
class AppointmentService
{
    /** Open (pending or proposal-awaiting) requests a participant may hold at once. */
    public const MAX_OPEN_REQUESTS = 3;

    /** Participants cannot cancel an approved appointment closer than this to its start. */
    public const CANCEL_CUTOFF_HOURS = 2;

    /** How far ahead an appointment can be requested. */
    public const MAX_DAYS_AHEAD = 180;

    /** Enrolment statuses that count as "taking the course". */
    public const ENROLMENT_STATUSES = ['enrolled', 'active', 'in_progress', 'started', 'completed'];

    public function __construct(private readonly NotificationDispatcher $notifier)
    {
    }

    /**
     * Instructors the participant may book, each with the courses they share.
     *
     * @return Collection<int, array{instructor: User, courses: Collection<int, Course>}>
     */
    public function bookableInstructors(User $participant): Collection
    {
        $courses = Course::query()
            ->whereHas('enrolments', fn ($q) => $q->where('user_id', $participant->id)
                ->whereIn('status', self::ENROLMENT_STATUSES))
            ->with(['instructors' => fn ($q) => $q->where('users.user_type', 'staff')
                ->where('users.status', 'active')
                ->select('users.id', 'users.name', 'users.email')])
            ->orderBy('title')
            ->get(['courses.id', 'courses.title']);

        $options = [];
        foreach ($courses as $course) {
            foreach ($course->instructors as $instructor) {
                $options[$instructor->id] ??= ['instructor' => $instructor, 'courses' => collect()];
                $options[$instructor->id]['courses']->push($course->withoutRelations());
            }
        }

        return collect($options)->sortBy(fn ($row) => $row['instructor']->name)->values();
    }

    public function request(User $participant, array $data): Appointment
    {
        $option = $this->bookableInstructors($participant)
            ->first(fn ($row) => $row['instructor']->id === (int) $data['instructor_user_id']);

        if (! $option) {
            throw ValidationException::withMessages([
                'instructor_user_id' => 'You can only book instructors of courses you are enrolled in.',
            ]);
        }

        $course = $option['courses']->firstWhere('id', (int) $data['course_id']);
        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => 'Choose a course this instructor teaches you.',
            ]);
        }

        $duration = (int) $data['duration_minutes'];
        $start = $this->parseStart($data['date'], $data['start_time'], 'date');
        $end = $start->copy()->addMinutes($duration);

        return DB::transaction(function () use ($participant, $option, $course, $data, $duration, $start, $end) {
            $openCount = Appointment::query()
                ->where('participant_user_id', $participant->id)
                ->whereIn('status', [Appointment::PENDING, Appointment::PROPOSED])
                ->lockForUpdate()
                ->count();

            if ($openCount >= self::MAX_OPEN_REQUESTS) {
                throw ValidationException::withMessages([
                    'date' => 'You already have '.self::MAX_OPEN_REQUESTS.' requests awaiting a response. Wait for a reply or cancel one first.',
                ]);
            }

            $this->ensureParticipantFree($participant->id, $start, $end);
            $this->ensureInstructorFree($option['instructor']->id, $start, $end, null, 'date',
                'The instructor already has an appointment at that time. Please pick another slot.');

            $appointment = Appointment::create([
                'participant_user_id' => $participant->id,
                'instructor_user_id' => $option['instructor']->id,
                'course_id' => $course->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'duration_minutes' => $duration,
                'mode' => $data['mode'],
                'topic' => $data['topic'],
                'details' => $data['details'] ?? null,
                'status' => Appointment::PENDING,
            ]);

            // The option row only carries id/name/email; load the full user so
            // notification preferences are honoured.
            $this->notify(User::find($option['instructor']->id), 'appointment_requested', 'New appointment request',
                "{$participant->name} requested a {$duration}-minute appointment on {$this->when($start)} about \"{$appointment->topic}\".",
                $appointment, true);

            return $appointment;
        });
    }

    public function approve(Appointment $appointment, User $instructor, array $data = []): Appointment
    {
        $this->assertStatus($appointment, [Appointment::PENDING]);
        $this->assertFuture($appointment->starts_at, 'This appointment time has already passed. Propose a new time instead.');

        return DB::transaction(function () use ($appointment, $instructor, $data) {
            $this->ensureInstructorFree($instructor->id, $appointment->starts_at, $appointment->ends_at, $appointment->id);

            $appointment->update([
                'status' => Appointment::APPROVED,
                'meeting_url' => $data['meeting_url'] ?? $appointment->meeting_url,
                'location' => $data['location'] ?? $appointment->location,
                'decision_reason' => null,
                'decided_at' => now(),
            ]);

            $this->syncInstructorTask($appointment);

            $this->notify($appointment->participant, 'appointment_approved', 'Appointment approved',
                "{$instructor->name} approved your appointment on {$this->when($appointment->starts_at)}.",
                $appointment, false);

            return $appointment;
        });
    }

    public function decline(Appointment $appointment, User $instructor, string $reason): Appointment
    {
        $this->assertStatus($appointment, [Appointment::PENDING]);

        $appointment->update([
            'status' => Appointment::DECLINED,
            'decision_reason' => $reason,
            'decided_at' => now(),
        ]);

        $this->notify($appointment->participant, 'appointment_declined', 'Appointment request declined',
            "{$instructor->name} declined your appointment request for {$this->when($appointment->starts_at)}. Reason: {$reason}",
            $appointment, false);

        return $appointment;
    }

    public function propose(Appointment $appointment, User $instructor, array $data): Appointment
    {
        $this->assertStatus($appointment, [Appointment::PENDING]);

        $start = $this->parseStart($data['date'], $data['start_time'], 'proposed_date');
        $end = $start->copy()->addMinutes($appointment->duration_minutes);

        $this->ensureInstructorFree($instructor->id, $start, $end, $appointment->id, 'proposed_date');

        $appointment->update([
            'status' => Appointment::PROPOSED,
            'proposed_starts_at' => $start,
            'proposal_note' => $data['note'] ?? null,
            'decided_at' => now(),
        ]);

        $this->notify($appointment->participant, 'appointment_proposed', 'New appointment time proposed',
            "{$instructor->name} proposed {$this->when($start)} instead of {$this->when($appointment->starts_at)}. Please accept or decline.",
            $appointment, false);

        return $appointment;
    }

    public function acceptProposal(Appointment $appointment, User $participant): Appointment
    {
        $this->assertStatus($appointment, [Appointment::PROPOSED]);
        $this->assertFuture($appointment->proposed_starts_at, 'The proposed time has already passed. Please request a new appointment.');

        return DB::transaction(function () use ($appointment, $participant) {
            $start = $appointment->proposed_starts_at->copy();
            $end = $start->copy()->addMinutes($appointment->duration_minutes);

            $this->ensureParticipantFree($participant->id, $start, $end, $appointment->id);
            $this->ensureInstructorFree($appointment->instructor_user_id, $start, $end, $appointment->id, 'appointment',
                'The instructor is no longer free at the proposed time.');

            $appointment->update([
                'status' => Appointment::APPROVED,
                'starts_at' => $start,
                'ends_at' => $end,
                'proposed_starts_at' => null,
                'decided_at' => now(),
            ]);

            $this->syncInstructorTask($appointment);

            $this->notify($appointment->instructor, 'appointment_proposal_accepted', 'Proposed time accepted',
                "{$participant->name} accepted your proposed time of {$this->when($start)}. The appointment is confirmed.",
                $appointment, true);

            return $appointment;
        });
    }

    public function declineProposal(Appointment $appointment, User $participant): Appointment
    {
        $this->assertStatus($appointment, [Appointment::PROPOSED]);

        $proposed = $appointment->proposed_starts_at;
        $appointment->update([
            'status' => Appointment::DECLINED,
            'decision_reason' => 'The participant declined the proposed time.',
            'decided_at' => now(),
        ]);

        $this->notify($appointment->instructor, 'appointment_proposal_declined', 'Proposed time declined',
            "{$participant->name} declined your proposed time".($proposed ? " of {$this->when($proposed)}" : '').'.',
            $appointment, true);

        return $appointment;
    }

    public function cancel(Appointment $appointment, User $actor, ?string $reason = null): Appointment
    {
        $byParticipant = $actor->id === $appointment->participant_user_id;

        if ($byParticipant) {
            $this->assertStatus($appointment, Appointment::OPEN_STATUSES);

            if ($appointment->status === Appointment::APPROVED && ! $this->participantCanCancel($appointment)) {
                throw ValidationException::withMessages([
                    'appointment' => 'Approved appointments can only be cancelled up to '.self::CANCEL_CUTOFF_HOURS.' hours before they start.',
                ]);
            }
        } else {
            // Instructors decline pending requests; cancelling applies to confirmed ones.
            $this->assertStatus($appointment, [Appointment::APPROVED]);
        }

        $appointment->update([
            'status' => Appointment::CANCELLED,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'decision_reason' => $reason ?: $appointment->decision_reason,
        ]);

        $this->syncInstructorTask($appointment);

        $recipient = $byParticipant ? $appointment->instructor : $appointment->participant;
        $this->notify($recipient, 'appointment_cancelled', 'Appointment cancelled',
            "{$actor->name} cancelled the appointment on {$this->when($appointment->starts_at)}".($reason ? ". Reason: {$reason}" : '.'),
            $appointment, $byParticipant);

        return $appointment;
    }

    public function complete(Appointment $appointment, User $instructor): Appointment
    {
        $this->assertStatus($appointment, [Appointment::APPROVED]);

        if ($appointment->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'appointment' => 'An appointment can only be marked completed once it has started.',
            ]);
        }

        $appointment->update(['status' => Appointment::COMPLETED, 'completed_at' => now()]);

        $this->syncInstructorTask($appointment);

        return $appointment;
    }

    public function participantCanCancel(Appointment $appointment): bool
    {
        return match ($appointment->status) {
            Appointment::PENDING, Appointment::PROPOSED => true,
            Appointment::APPROVED => $appointment->starts_at->gt(now()->addHours(self::CANCEL_CUTOFF_HOURS)),
            default => false,
        };
    }

    public function pendingCountFor(User $instructor): int
    {
        return Appointment::query()
            ->where('instructor_user_id', $instructor->id)
            ->where('status', Appointment::PENDING)
            ->count();
    }

    // ---------------------------------------------------------------------

    private function parseStart(string $date, string $time, string $field): Carbon
    {
        try {
            $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, config('app.timezone'));
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'Enter a valid date and start time.']);
        }

        if (! $start || $start->lte(now())) {
            throw ValidationException::withMessages([$field => 'Choose a date and time in the future.']);
        }

        if ($start->gt(now()->addDays(self::MAX_DAYS_AHEAD))) {
            throw ValidationException::withMessages([$field => 'Appointments can be booked up to '.self::MAX_DAYS_AHEAD.' days ahead.']);
        }

        return $start->seconds(0);
    }

    private function ensureParticipantFree(int $participantId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): void
    {
        $clash = Appointment::query()
            ->where('participant_user_id', $participantId)
            ->whereIn('status', Appointment::OPEN_STATUSES)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->overlapping($start, $end)
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([
                'date' => 'You already have an appointment request that overlaps this time.',
            ]);
        }
    }

    private function ensureInstructorFree(
        int $instructorId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreId = null,
        string $field = 'appointment',
        string $message = 'This clashes with another approved appointment on your schedule.',
    ): void {
        $clash = Appointment::query()
            ->where('instructor_user_id', $instructorId)
            ->where('status', Appointment::APPROVED)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->overlapping($start, $end)
            ->lockForUpdate()
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function assertStatus(Appointment $appointment, array $allowed): void
    {
        if (! in_array($appointment->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment is '.strtolower($appointment->statusLabel()).' and can no longer be changed that way.',
            ]);
        }
    }

    private function assertFuture(?CarbonInterface $time, string $message): void
    {
        if (! $time || $time->lte(now())) {
            throw ValidationException::withMessages(['appointment' => $message]);
        }
    }

    /**
     * Keep the instructor's daily task in step with the appointment: an
     * approved appointment becomes a task on its date (My Tasks), a
     * completed one completes it, and a cancelled one removes it unless the
     * task was already done.
     */
    private function syncInstructorTask(Appointment $appointment): void
    {
        $task = Task::where('instructor_appointment_id', $appointment->id)->first();

        if ($appointment->status === Appointment::APPROVED) {
            $appointment->loadMissing(['participant:id,name', 'course:id,title']);
            $start = $appointment->starts_at->copy()->setTimezone(config('app.timezone'));
            $end = $appointment->ends_at?->copy()->setTimezone(config('app.timezone'));

            $details = array_filter([
                'Time: '.$start->format('D j M Y, H:i').($end ? '–'.$end->format('H:i') : ''),
                'Participant: '.($appointment->participant?->name ?? '—'),
                $appointment->course ? 'Course: '.$appointment->course->title : null,
                'Mode: '.($appointment->mode === 'in_person' ? 'In person' : 'Online'),
                $appointment->location ? 'Venue: '.$appointment->location : null,
                $appointment->meeting_url ? 'Meeting link: '.$appointment->meeting_url : null,
                $appointment->details ? PHP_EOL.$appointment->details : null,
            ]);

            Task::updateOrCreate(
                ['instructor_appointment_id' => $appointment->id],
                [
                    'title' => mb_strimwidth('Appointment: '.$appointment->topic.' — '.($appointment->participant?->name ?? 'participant'), 0, 255, '…'),
                    'description' => implode(PHP_EOL, $details),
                    'assigned_to' => $appointment->instructor_user_id,
                    'created_by' => $appointment->instructor_user_id,
                    'start_date' => $start->toDateString(),
                    'due_date' => $start->toDateString(),
                    'priority' => 'high',
                ] + ($task ? [] : ['status' => 'not_started', 'progress_percent' => 0])
            );

            return;
        }

        if (! $task) {
            return;
        }

        if ($appointment->status === Appointment::COMPLETED) {
            $task->update(['status' => 'completed', 'completed_at' => $appointment->completed_at ?? now(), 'progress_percent' => 100]);
        } elseif ($task->status !== 'completed') {
            $task->delete();
        }
    }

    private function when(CarbonInterface $time): string
    {
        return $time->copy()->setTimezone(config('app.timezone'))->format('D j M Y, H:i');
    }

    private function notify(?User $user, string $type, string $title, string $message, Appointment $appointment, bool $toInstructor): void
    {
        if (! $user) {
            return;
        }

        $url = $toInstructor
            ? route('instructor.appointments.index', ['tab' => match ($appointment->status) {
                Appointment::PENDING, Appointment::PROPOSED => 'requests',
                Appointment::APPROVED => 'upcoming',
                default => 'past',
            }])
            : route('appointments.index');

        $this->notifier->notify(
            $user,
            $type,
            $title,
            $message,
            $url,
            ['appointment_id' => $appointment->id],
            email: NotificationPreferences::allows($user, $type),
        );
    }
}
