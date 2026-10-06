<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\InstructorAppointment as Appointment;
use App\Services\AppointmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instructor side of participant appointments. Instructors act only on
 * appointments addressed to them; super admins may additionally view all.
 */
class AppointmentController extends Controller
{
    use ExportsTables;

    public const TABS = ['requests' => 'Requests', 'upcoming' => 'Upcoming', 'past' => 'Past'];

    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    public function index(Request $request): View|Response
    {
        $user = $request->user();
        $this->authorizeTeacher($request);

        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'requests';
        $showAll = $user->isSuperAdmin() && $request->query('scope') === 'all';

        $scoped = fn (): Builder => Appointment::query()
            ->when(! $showAll, fn ($q) => $q->where('instructor_user_id', $user->id));

        $query = $this->tabQuery($scoped(), $tab)
            ->with(['participant:id,name,email', 'instructor:id,name', 'course:id,title']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('topic', 'like', "%{$search}%")
                ->orWhereHas('participant', fn ($p) => $p->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")));
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Instructor Appointments - '.self::TABS[$tab], $query, $this->exportColumns($showAll), null, ['tab' => 'Tab', 'scope' => 'Scope']);
        }

        return view('instructor.appointments.index', [
            'appointments' => $query->paginate(15)->withQueryString(),
            'tab' => $tab,
            'tabs' => self::TABS,
            'showAll' => $showAll,
            'canViewAll' => $user->isSuperAdmin(),
            'counts' => collect(self::TABS)->map(fn ($label, $key) => $this->tabQuery($scoped(), $key)->count()),
            'stats' => [
                'pending' => $scoped()->where('status', Appointment::PENDING)->count(),
                'awaiting' => $scoped()->where('status', Appointment::PROPOSED)->count(),
                'upcoming' => $scoped()->where('status', Appointment::APPROVED)->where('ends_at', '>=', now())->count(),
                'completed' => $scoped()->where('status', Appointment::COMPLETED)->count(),
            ],
        ]);
    }

    public function approve(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeInstructor($request, $appointment);
        $data = $request->validate([
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
        $this->appointments->approve($appointment, $request->user(), array_filter($data));

        return back()->with('success', 'Appointment approved. The participant has been notified.');
    }

    public function decline(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeInstructor($request, $appointment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->appointments->decline($appointment, $request->user(), $data['reason']);

        return back()->with('success', 'Appointment request declined.');
    }

    public function propose(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeInstructor($request, $appointment);
        $data = $request->validate([
            'proposed_date' => ['required', 'date_format:Y-m-d'],
            'proposed_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['proposed_date' => 'date', 'proposed_time' => 'time']);

        $this->appointments->propose($appointment, $request->user(), [
            'date' => $data['proposed_date'],
            'start_time' => $data['proposed_time'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'New time proposed. The participant will be asked to accept or decline.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeInstructor($request, $appointment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->appointments->cancel($appointment, $request->user(), $data['reason']);

        return back()->with('success', 'Appointment cancelled. The participant has been notified.');
    }

    public function complete(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeInstructor($request, $appointment);
        $this->appointments->complete($appointment, $request->user());

        return back()->with('success', 'Appointment marked as completed.');
    }

    private function tabQuery(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'requests' => $query->whereIn('status', [Appointment::PENDING, Appointment::PROPOSED])->orderBy('starts_at'),
            'upcoming' => $query->where('status', Appointment::APPROVED)->where('ends_at', '>=', now())->orderBy('starts_at'),
            default => $query->where(fn ($q) => $q
                ->whereIn('status', [Appointment::DECLINED, Appointment::CANCELLED, Appointment::COMPLETED])
                ->orWhere(fn ($a) => $a->where('status', Appointment::APPROVED)->where('ends_at', '<', now())))
                ->orderByDesc('starts_at'),
        };
    }

    private function authorizeTeacher(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user->isStaff() && $user->isActive()
                && ($user->isSuperAdmin() || $user->hasAnyRole(['instructor', 'trainer'])),
            403
        );
    }

    private function authorizeInstructor(Request $request, Appointment $appointment): void
    {
        $this->authorizeTeacher($request);
        abort_unless($appointment->instructor_user_id === $request->user()->id, 403);
    }

    private function exportColumns(bool $withInstructor): array
    {
        $columns = [
            'Date' => fn (Appointment $a) => $a->starts_at->format('Y-m-d'),
            'Time' => fn (Appointment $a) => $a->starts_at->format('H:i').' - '.$a->ends_at->format('H:i'),
            'Participant' => fn (Appointment $a) => $a->participant?->name,
            'Email' => fn (Appointment $a) => $a->participant?->email,
        ];

        if ($withInstructor) {
            $columns['Instructor'] = fn (Appointment $a) => $a->instructor?->name;
        }

        return $columns + [
            'Course' => fn (Appointment $a) => $a->course?->title,
            'Topic' => 'topic',
            'Mode' => fn (Appointment $a) => $a->modeLabel(),
            'Status' => fn (Appointment $a) => $a->statusLabel(),
            'Proposed time' => fn (Appointment $a) => $a->proposed_starts_at?->format('Y-m-d H:i'),
            'Venue / link' => fn (Appointment $a) => $a->location ?: $a->meeting_url,
        ];
    }
}
