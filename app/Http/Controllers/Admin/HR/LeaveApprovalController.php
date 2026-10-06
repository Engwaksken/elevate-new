<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use App\Support\LeaveApprovalAccess as Access;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Leave approvals: supervisors see and decide on their own team's requests;
 * HR and administrators see everything and give final approval, reject,
 * edit and cancel (rules in App\Support\LeaveApprovalAccess).
 */
class LeaveApprovalController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    public const STATUSES = ['pending', 'submitted', 'supervisor_approved', 'hr_approved', 'rejected', 'cancelled'];

    protected function bulkDeleteModel(): string
    {
        return LeaveRequest::class;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(Access::canAccess($user), 403, 'Leave approvals are for supervisors, HR and administrators.');

        $visible = fn () => Access::scope(LeaveRequest::query(), $user);
        $query = $visible()->with(['employee.user', 'employee.supervisor', 'leaveType'])->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->whereHas('employee.user', fn ($u) => $u
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if (in_array($status = $request->get('status'), self::STATUSES, true)) {
            // "Awaiting supervisor" covers both stored values.
            in_array($status, Access::AWAITING_SUPERVISOR, true)
                ? $query->whereIn('status', Access::AWAITING_SUPERVISOR)
                : $query->where('status', $status);
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Leave Requests', $query, [
                'Employee' => 'employee.user.name',
                'Employee No.' => 'employee.employee_number',
                'Leave Type' => 'leaveType.name',
                'Start Date' => 'start_date',
                'End Date' => 'end_date',
                'Days' => 'days_requested',
                'Reason' => 'reason',
                'Status' => fn ($r) => self::statusLabel($r->status),
                'Supervisor Approved' => 'supervisor_approved_at',
                'HR Approved' => 'hr_approved_at',
                'Decision Notes' => 'decision_notes',
                'Submitted' => 'created_at',
            ]);
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50, 100], true) ? (int) $request->get('per_page') : 25;

        return view('admin.hr.leave.index', [
            'requests' => $query->paginate($perPage)->withQueryString(),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'isHr' => Access::isHr($user),
            'stats' => [
                'total' => $visible()->count(),
                'awaiting_supervisor' => $visible()->whereIn('status', Access::AWAITING_SUPERVISOR)->count(),
                'awaiting_hr' => $visible()->where('status', 'supervisor_approved')->count(),
                'approved' => $visible()->where('status', 'hr_approved')->count(),
            ],
        ]);
    }

    public function supervisorApprove(Request $request, LeaveRequest $leave)
    {
        abort_unless(Access::canSupervisorApprove($request->user(), $leave), 403);

        $leave->update([
            'status' => 'supervisor_approved',
            'supervisor_approved_by' => $request->user()->id,
            'supervisor_approved_at' => now(),
        ]);

        return back()->with('success', 'Supervisor approval recorded. HR gives the final approval.');
    }

    public function hrApprove(Request $request, LeaveRequest $leave, LeaveService $service)
    {
        abort_unless(Access::canHrApprove($request->user(), $leave), 403);

        try {
            $service->approveFinal($leave);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        abort_unless(Access::canReject($request->user(), $leave), 403);

        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:2000']]);

        $leave->update([
            'status' => 'rejected',
            'decision_notes' => $data['decision_notes'] ?? null,
        ]);

        return back()->with('success', 'Leave rejected.');
    }

    /** HR corrects type, dates, reason or notes before final approval. */
    public function update(Request $request, LeaveRequest $leave, LeaveService $service)
    {
        abort_unless(Access::canEdit($request->user(), $leave), 403);

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
            'leave_id' => ['nullable'],
        ]);
        unset($data['leave_id']);

        $data['days_requested'] = $service->workingDays($data['start_date'], $data['end_date']);
        if ($data['days_requested'] <= 0) {
            throw ValidationException::withMessages(['end_date' => 'The leave must include at least one working day.']);
        }

        $leave->update($data);

        return back()->with('success', 'Leave request updated.');
    }

    /** HR cancels an approved request; its days return to the balance. */
    public function cancel(Request $request, LeaveRequest $leave, LeaveService $service)
    {
        abort_unless(Access::canCancel($request->user(), $leave), 403);

        $data = $request->validate(['decision_notes' => ['nullable', 'string', 'max:2000']]);
        $service->cancelApproved($leave, $data['decision_notes'] ?? null);

        return back()->with('success', 'Approved leave cancelled and the days returned to the balance.');
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending', 'submitted' => 'Awaiting supervisor',
            'supervisor_approved' => 'Awaiting HR',
            'hr_approved' => 'Approved',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }
}
