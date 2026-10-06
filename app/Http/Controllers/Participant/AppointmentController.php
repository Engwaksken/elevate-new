<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\InstructorAppointment as Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Participant side of instructor appointments: book, review proposals, cancel.
 */
class AppointmentController extends Controller
{
    use ExportsTables;

    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    public function index(Request $request): View|Response
    {
        $user = $request->user();

        $base = Appointment::query()
            ->with(['instructor:id,name,email', 'course:id,title'])
            ->where('participant_user_id', $user->id);

        if ($status = $request->query('status')) {
            if (in_array($status, Appointment::STATUSES, true)) {
                $base->where('status', $status);
            }
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My Instructor Appointments', (clone $base)->orderByDesc('starts_at'), $this->exportColumns(), null, ['status' => 'Status']);
        }

        $upcoming = (clone $base)
            ->whereIn('status', Appointment::OPEN_STATUSES)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->get();

        $past = (clone $base)
            ->whereNotIn('id', $upcoming->pluck('id'))
            ->orderByDesc('starts_at')
            ->paginate(10)
            ->withQueryString();

        $all = Appointment::query()->where('participant_user_id', $user->id);

        return view('appointments.index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'instructorOptions' => $this->appointments->bookableInstructors($user),
            'service' => $this->appointments,
            'stats' => [
                'pending' => (clone $all)->where('status', Appointment::PENDING)->count(),
                'proposals' => (clone $all)->where('status', Appointment::PROPOSED)->count(),
                'upcoming' => (clone $all)->where('status', Appointment::APPROVED)->where('ends_at', '>=', now())->count(),
                'completed' => (clone $all)->where('status', Appointment::COMPLETED)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'instructor_user_id' => ['required', 'integer'],
            'course_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', Rule::in(Appointment::DURATIONS)],
            'mode' => ['required', Rule::in(array_keys(Appointment::MODES))],
            'topic' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'instructor_user_id' => 'instructor',
            'course_id' => 'course',
            'duration_minutes' => 'duration',
        ]);

        $this->appointments->request($request->user(), $data);

        return redirect()->route('appointments.index')
            ->with('success', 'Your appointment request was sent. You will be notified when the instructor responds.');
    }

    public function accept(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        $this->appointments->acceptProposal($appointment, $request->user());

        return back()->with('success', 'You accepted the new time. Your appointment is confirmed.');
    }

    public function declineProposal(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        $this->appointments->declineProposal($appointment, $request->user());

        return back()->with('success', 'You declined the proposed time.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $this->appointments->cancel($appointment, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Appointment cancelled.');
    }

    private function authorizeOwner(Request $request, Appointment $appointment): void
    {
        abort_unless($appointment->participant_user_id === $request->user()->id, 403);
    }

    private function exportColumns(): array
    {
        return [
            'Date' => fn (Appointment $a) => $a->starts_at->format('Y-m-d'),
            'Time' => fn (Appointment $a) => $a->starts_at->format('H:i').' - '.$a->ends_at->format('H:i'),
            'Instructor' => fn (Appointment $a) => $a->instructor?->name,
            'Course' => fn (Appointment $a) => $a->course?->title,
            'Topic' => 'topic',
            'Mode' => fn (Appointment $a) => $a->modeLabel(),
            'Status' => fn (Appointment $a) => $a->statusLabel(),
            'Proposed time' => fn (Appointment $a) => $a->proposed_starts_at?->format('Y-m-d H:i'),
            'Venue / link' => fn (Appointment $a) => $a->location ?: $a->meeting_url,
            'Notes' => 'decision_reason',
        ];
    }
}
