<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\InstructorAppointment as Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Participant API for instructor appointments (mobile app).
 *
 * GET  /appointments?scope=upcoming|past|all
 * GET  /appointments/options
 * GET  /appointments/{appointment}
 * POST /appointments
 * POST /appointments/{appointment}/accept-proposal
 * POST /appointments/{appointment}/decline-proposal
 * POST /appointments/{appointment}/cancel
 *
 * Every business rule lives in AppointmentService; this controller only
 * validates input shape and serialises results. Times are returned as
 * ISO 8601 strings with the offset of the app timezone (Africa/Kampala).
 */
class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['upcoming', 'past', 'all'])],
        ]);
        $scope = $data['scope'] ?? 'all';
        $user = $request->user();

        $base = Appointment::query()
            ->with(['instructor:id,name', 'course:id,title'])
            ->where('participant_user_id', $user->id);

        // "Upcoming" mirrors the web page: open (pending / proposal / approved)
        // appointments that have not finished yet. Everything else is "past".
        $upcomingQuery = fn ($q) => $q->whereIn('status', Appointment::OPEN_STATUSES)
            ->where('ends_at', '>=', now());

        $upcoming = in_array($scope, ['upcoming', 'all'], true)
            ? (clone $base)->where($upcomingQuery)->orderBy('starts_at')->get()
            : collect();

        $past = in_array($scope, ['past', 'all'], true)
            ? (clone $base)->whereNot($upcomingQuery)->orderByDesc('starts_at')->limit(100)->get()
            : collect();

        $all = Appointment::query()->where('participant_user_id', $user->id);

        return response()->json([
            'scope' => $scope,
            'timezone' => config('app.timezone'),
            'upcoming' => $upcoming->map(fn (Appointment $a) => $this->payload($a))->values(),
            'past' => $past->map(fn (Appointment $a) => $this->payload($a))->values(),
            'summary' => [
                'pending' => (clone $all)->where('status', Appointment::PENDING)->count(),
                'proposals' => (clone $all)->where('status', Appointment::PROPOSED)->count(),
                'upcoming' => (clone $all)->where('status', Appointment::APPROVED)->where('ends_at', '>=', now())->count(),
                'completed' => (clone $all)->where('status', Appointment::COMPLETED)->count(),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $instructors = $this->appointments->bookableInstructors($request->user())
            ->map(fn (array $row) => [
                'id' => $row['instructor']->id,
                'name' => $row['instructor']->name,
                'courses' => $row['courses']->map(fn ($course) => [
                    'id' => $course->id,
                    'title' => $course->title,
                ])->values(),
            ])->values();

        return response()->json([
            'instructors' => $instructors,
            'durations' => Appointment::DURATIONS,
            'modes' => collect(Appointment::MODES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'timezone' => config('app.timezone'),
            'rules' => [
                'max_open_requests' => AppointmentService::MAX_OPEN_REQUESTS,
                'cancel_cutoff_hours' => AppointmentService::CANCEL_CUTOFF_HOURS,
                'max_days_ahead' => AppointmentService::MAX_DAYS_AHEAD,
                'topic_max' => 150,
                'details_max' => 2000,
            ],
        ]);
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeOwner($request, $appointment);
        $appointment->loadMissing(['instructor:id,name', 'course:id,title']);

        return response()->json(['appointment' => $this->payload($appointment)]);
    }

    /**
     * Accepts either `starts_at` (ISO 8601; an offset is honoured and the
     * value converted to the app timezone, no offset means app timezone) or
     * the web form's `date` (Y-m-d) + `start_time` (H:i) in the app timezone.
     */
    public function store(Request $request): JsonResponse
    {
        $usesStartsAt = $request->filled('starts_at') || ! $request->hasAny(['date', 'start_time']);

        $rules = [
            'instructor_user_id' => ['required', 'integer'],
            'course_id' => ['required', 'integer'],
            'duration_minutes' => ['required', 'integer', Rule::in(Appointment::DURATIONS)],
            'mode' => ['required', Rule::in(array_keys(Appointment::MODES))],
            'topic' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
        $rules += $usesStartsAt
            ? ['starts_at' => ['required', 'date']]
            : ['date' => ['required', 'date_format:Y-m-d'], 'start_time' => ['required', 'date_format:H:i']];

        $data = $request->validate($rules, [], [
            'instructor_user_id' => 'instructor',
            'course_id' => 'course',
            'duration_minutes' => 'duration',
            'starts_at' => 'start time',
        ]);

        if ($usesStartsAt) {
            $start = Carbon::parse($data['starts_at'], config('app.timezone'))->setTimezone(config('app.timezone'));
            $data['date'] = $start->format('Y-m-d');
            $data['start_time'] = $start->format('H:i');
            unset($data['starts_at']);
        }

        try {
            $appointment = $this->appointments->request($request->user(), $data);
        } catch (ValidationException $e) {
            // The service reports time problems on `date`; mirror them on the
            // field the client actually sent.
            throw $usesStartsAt ? $this->renameErrorKey($e, 'date', 'starts_at') : $e;
        }

        $appointment->load(['instructor:id,name', 'course:id,title']);

        return response()->json([
            'message' => 'Your appointment request was sent. You will be notified when the instructor responds.',
            'appointment' => $this->payload($appointment),
        ], 201);
    }

    public function acceptProposal(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeOwner($request, $appointment);
        $this->appointments->acceptProposal($appointment, $request->user());

        return $this->actionResponse($appointment, 'You accepted the new time. Your appointment is confirmed.');
    }

    public function declineProposal(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeOwner($request, $appointment);
        $this->appointments->declineProposal($appointment, $request->user());

        return $this->actionResponse($appointment, 'You declined the proposed time.');
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeOwner($request, $appointment);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $this->appointments->cancel($appointment, $request->user(), $data['reason'] ?? null);

        return $this->actionResponse($appointment, 'Appointment cancelled.');
    }

    // ---------------------------------------------------------------------

    private function authorizeOwner(Request $request, Appointment $appointment): void
    {
        abort_unless(
            (int) $appointment->participant_user_id === (int) $request->user()->id,
            403,
            'This appointment does not belong to you.'
        );
    }

    private function actionResponse(Appointment $appointment, string $message): JsonResponse
    {
        $appointment = $appointment->fresh(['instructor:id,name', 'course:id,title']);

        return response()->json(['message' => $message, 'appointment' => $this->payload($appointment)]);
    }

    private function renameErrorKey(ValidationException $e, string $from, string $to): ValidationException
    {
        $errors = $e->errors();
        if (! array_key_exists($from, $errors)) {
            return $e;
        }
        $errors[$to] = $errors[$from];
        unset($errors[$from]);

        return ValidationException::withMessages($errors);
    }

    private function iso(?\DateTimeInterface $time): ?string
    {
        return $time ? Carbon::instance($time)->setTimezone(config('app.timezone'))->toIso8601String() : null;
    }

    private function payload(Appointment $a): array
    {
        $proposedEnds = $a->proposedEndsAt();

        return [
            'id' => $a->id,
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'topic' => $a->topic,
            'details' => $a->details,
            'starts_at' => $this->iso($a->starts_at),
            'ends_at' => $this->iso($a->ends_at),
            'timezone' => config('app.timezone'),
            'duration_minutes' => (int) $a->duration_minutes,
            'mode' => $a->mode,
            'mode_label' => $a->modeLabel(),
            'location' => $a->location,
            'meeting_url' => $a->status === Appointment::APPROVED ? $a->meeting_url : null,
            'instructor' => $a->instructor ? ['id' => $a->instructor->id, 'name' => $a->instructor->name] : null,
            'course' => $a->course ? ['id' => $a->course->id, 'title' => $a->course->title] : null,
            'proposed_starts_at' => $this->iso($a->proposed_starts_at),
            'proposed_ends_at' => $this->iso($proposedEnds),
            'proposal_note' => $a->proposal_note,
            'decision_reason' => $a->decision_reason,
            'cancelled_by_me' => $a->cancelled_by !== null && (int) $a->cancelled_by === (int) $a->participant_user_id,
            'cancelled_at' => $this->iso($a->cancelled_at),
            'decided_at' => $this->iso($a->decided_at),
            'completed_at' => $this->iso($a->completed_at),
            'created_at' => $this->iso($a->created_at),
            'can_cancel' => $this->appointments->participantCanCancel($a),
            'can_respond_to_proposal' => $a->status === Appointment::PROPOSED,
            // A proposal whose time has passed can only be declined.
            'can_accept_proposal' => $a->status === Appointment::PROPOSED
                && $a->proposed_starts_at !== null
                && $a->proposed_starts_at->isFuture(),
        ];
    }
}
