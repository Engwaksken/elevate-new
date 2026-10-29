<?php

namespace App\Services;

use App\Models\CareerActionItem;
use App\Models\InstructorAppointment;
use App\Models\MentorshipSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Reminders for calendar activities that had none: approved instructor appointments and
 * mentorship sessions (the day before and shortly before), and approved career steps
 * (on the morning they are due, and once if they become overdue).
 */
class ActivityReminderService
{
    public const DAY_BEFORE_HOURS = 24;
    public const SOON_MINUTES = 30;
    /** A "day before" reminder is skipped when the activity is this close; the "soon" one covers it. */
    public const DAY_REMINDER_MIN_HOURS = 2;
    public const DUE_REMINDER_HOUR = 8;
    public const OVERDUE_REMINDER_HOUR = 9;
    /** Older overdue steps are left alone so a first deploy does not flood people with old nudges. */
    public const OVERDUE_LOOKBACK_DAYS = 7;

    public function __construct(private readonly UserNotificationService $notifications)
    {
    }

    public function sendDue(?CarbonInterface $now = null): int
    {
        $now = ($now ?? now())->copy()->setTimezone(config('app.timezone'));

        return $this->appointments($now) + $this->mentorshipSessions($now) + $this->careerSteps($now);
    }

    private function appointments(CarbonInterface $now): int
    {
        $sent = 0;
        $appointments = InstructorAppointment::with(['participant', 'instructor', 'course:id,title'])
            ->where('status', InstructorAppointment::APPROVED)
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $now->copy()->addHours(self::DAY_BEFORE_HOURS))
            ->get();

        foreach ($appointments as $appointment) {
            $kind = $this->timedKind($now, $appointment->starts_at);
            if (! $kind) {
                continue;
            }
            $when = $this->whenText($now, $appointment->starts_at, $kind);
            $place = $appointment->mode === 'online' ? ($appointment->meeting_url ? ' Join from the link on your Appointments page.' : ' It is online.') : ($appointment->location ? ' Venue: '.$appointment->location.'.' : '');

            if ($appointment->participant) {
                $sent += $this->deliver($appointment, $appointment->participant, $kind, 'appointment_reminder', 'Appointment '.$when,
                    'Your appointment "'.$appointment->topic.'" with '.($appointment->instructor?->name ?? 'your instructor').' is '.$when.' at '.$this->time($appointment->starts_at).'.'.$place,
                    route('appointments.index'));
            }
            if ($appointment->instructor) {
                $sent += $this->deliver($appointment, $appointment->instructor, $kind, 'appointment_reminder', 'Appointment '.$when,
                    'Your appointment with '.($appointment->participant?->name ?? 'a participant').' about "'.$appointment->topic.'" is '.$when.' at '.$this->time($appointment->starts_at).'.'.$place,
                    route('instructor.appointments.index', ['tab' => 'upcoming']));
            }
        }

        return $sent;
    }

    private function mentorshipSessions(CarbonInterface $now): int
    {
        $sent = 0;
        $sessions = MentorshipSession::with(['match.mentor', 'match.mentee'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $now->copy()->addHours(self::DAY_BEFORE_HOURS))
            ->whereHas('match', fn ($q) => $q->whereIn('status', ['active', 'pending']))
            ->get();

        foreach ($sessions as $session) {
            $kind = $this->timedKind($now, $session->scheduled_at);
            if (! $kind) {
                continue;
            }
            $when = $this->whenText($now, $session->scheduled_at, $kind);
            $title = $session->title ?: 'Mentorship session';
            $place = $session->meeting_link ? ' Meeting link: '.$session->meeting_link : ($session->venue ? ' Venue: '.$session->venue.'.' : '');

            foreach ([[$session->match?->mentee, $session->match?->mentor], [$session->match?->mentor, $session->match?->mentee]] as [$user, $other]) {
                if (! $user) {
                    continue;
                }
                $sent += $this->deliver($session, $user, $kind, 'mentorship_session_reminder', 'Mentorship session '.$when,
                    '"'.$title.'"'.($other ? ' with '.$other->name : '').' is '.$when.' at '.$this->time($session->scheduled_at).'.'.$place,
                    route('mentorship.dashboard').'#sessions');
            }
        }

        return $sent;
    }

    private function careerSteps(CarbonInterface $now): int
    {
        $sent = 0;
        $today = $now->copy()->startOfDay();

        if ($now->hour >= self::DUE_REMINDER_HOUR) {
            $due = CareerActionItem::with('user', 'goal')->where('status', CareerActionItem::APPROVED)->whereDate('due_on', $today->toDateString())->get();
            foreach ($due as $step) {
                $sent += $this->deliver($step, $step->user, 'due', 'reminder_career_step', 'Career step due today',
                    'Today is the day for: "'.$step->title.'".'.($step->goal ? ' It helps with your goal "'.$step->goal->title.'".' : '').' Mark it done in My action plan when you finish.',
                    route('mentorship.dashboard').'#action-plan');
            }
        }

        if ($now->hour >= self::OVERDUE_REMINDER_HOUR) {
            $overdue = CareerActionItem::with('user', 'goal')->where('status', CareerActionItem::APPROVED)
                ->whereDate('due_on', '<', $today->toDateString())
                ->whereDate('due_on', '>=', $today->copy()->subDays(self::OVERDUE_LOOKBACK_DAYS)->toDateString())
                ->get();
            foreach ($overdue as $step) {
                $sent += $this->deliver($step, $step->user, 'overdue', 'reminder_career_step', 'A career step is waiting',
                    '"'.$step->title.'" was planned for '.$step->due_on->format('D j M').'. If you have done it, mark it done. If not, it is fine to pick a new day and keep going.',
                    route('mentorship.dashboard').'#action-plan');
            }
        }

        return $sent;
    }

    /** "day" when the activity is within 24 hours (but not imminent), "soon" within 30 minutes. */
    private function timedKind(CarbonInterface $now, CarbonInterface $startsAt): ?string
    {
        $minutes = $now->diffInMinutes($startsAt, false);

        return match (true) {
            $minutes > 0 && $minutes <= self::SOON_MINUTES => 'soon',
            $minutes >= self::DAY_REMINDER_MIN_HOURS * 60 && $minutes <= self::DAY_BEFORE_HOURS * 60 => 'day',
            default => null,
        };
    }

    private function whenText(CarbonInterface $now, CarbonInterface $startsAt, string $kind): string
    {
        if ($kind === 'soon') {
            return 'starts soon';
        }
        $local = $startsAt->copy()->setTimezone(config('app.timezone'));

        return $local->isSameDay($now) ? 'later today' : ($local->isSameDay($now->copy()->addDay()) ? 'tomorrow' : 'on '.$local->format('D j M'));
    }

    private function time(CarbonInterface $at): string
    {
        return $at->copy()->setTimezone(config('app.timezone'))->format('H:i');
    }

    /** Claim the reminder first so overlapping runs never send it twice, then notify. */
    private function deliver(object $remindable, ?User $user, string $kind, string $type, string $title, string $message, string $url): int
    {
        if (! $user || ($user->status !== null && $user->status !== 'active')) {
            return 0;
        }

        $claimed = DB::table('activity_reminder_deliveries')->insertOrIgnore([
            'remindable_type' => $remindable->getMorphClass(),
            'remindable_id' => $remindable->getKey(),
            'user_id' => $user->id,
            'kind' => $kind,
            'sent_at' => now(),
        ]);
        if (! $claimed) {
            return 0;
        }

        $this->notifications->send($user, $type, $title, $message, $url, [
            'reminder' => $kind,
            'source' => class_basename($remindable),
            'source_id' => $remindable->getKey(),
        ]);

        return 1;
    }
}
