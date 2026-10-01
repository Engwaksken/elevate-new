<?php

namespace App\Observers;

use App\Models\Appraisal;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * In-app notifications for staff HR workflows: leave requests and appraisals.
 *
 * Each status change is routed to whoever has to act next (supervisor, HR
 * approvers or the employee). Runs after commit so status history written in
 * the same transaction is available, and the actor is never notified.
 */
class HrNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly UserNotificationService $notifications)
    {
    }

    public function created(Model $model): void
    {
        $this->handle($model, 'created');
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged('status')) {
            return;
        }

        $this->handle($model, 'updated');
    }

    private function handle(Model $model, string $event): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        try {
            match (true) {
                $model instanceof LeaveRequest => $this->leave($model, $event),
                $model instanceof Appraisal => $this->appraisal($model, $event),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ---------------------------------------------------------------- Leave */

    private function leave(LeaveRequest $leave, string $event): void
    {
        $employee = Employee::query()->with('user')->find($leave->employee_id);
        $employeeUserId = $employee?->user_id;
        $name = $employee?->user?->name ?? 'An employee';
        $type = $leave->leaveType()->value('name') ?? 'Leave';
        $period = $leave->start_date && $leave->end_date
            ? $leave->start_date->format('d M Y').' to '.$leave->end_date->format('d M Y')
            : 'the requested dates';
        $days = $leave->days_requested !== null ? ' ('.rtrim(rtrim((string) $leave->days_requested, '0'), '.').' day(s))' : '';
        $data = ['leave_request_id' => $leave->id, 'employee_id' => $leave->employee_id, 'status' => $leave->status];
        $approvalUrl = $this->route('admin.hr.leave.index');
        $ownUrl = $this->route('hr.leave.index');

        if ($event === 'created') {
            $approvers = $employee?->supervisor_user_id
                ? [$employee->supervisor_user_id]
                : $this->leaveApproverIds();

            $this->notifications->sendToMany($approvers, 'leave', 'Leave request to review',
                "{$name} requested {$type}{$days} for {$period}.", $approvalUrl, $data);

            if ($leave->handover_user_id) {
                $this->notifications->sendToMany([$leave->handover_user_id], 'leave', 'Handover during leave',
                    "{$name} named you as handover contact for {$type} from {$period}.", $ownUrl, $data);
            }

            return;
        }

        $notes = filled($leave->decision_notes) ? ' Notes: '.Str::limit((string) $leave->decision_notes, 160) : '';

        switch ($leave->status) {
            case 'supervisor_approved':
                $this->notifications->sendToMany([$employeeUserId], 'leave', 'Leave approved by supervisor',
                    "Your {$type} request for {$period} was approved by your supervisor and is awaiting HR approval.", $ownUrl, $data);
                $this->notifications->sendToMany(
                    array_diff($this->leaveApproverIds(), [(int) $employeeUserId]),
                    'leave', 'Leave awaiting HR approval',
                    "{$name}'s {$type} request for {$period} was approved by the supervisor and needs HR approval.", $approvalUrl, $data);
                break;
            case 'hr_approved':
            case 'approved':
                $this->notifications->sendToMany([$employeeUserId], 'leave', 'Leave approved',
                    "Your {$type} request for {$period} has been approved.{$notes}", $ownUrl, $data);
                break;
            case 'rejected':
                $this->notifications->sendToMany([$employeeUserId], 'leave', 'Leave request rejected',
                    "Your {$type} request for {$period} was rejected.{$notes}", $ownUrl, $data);
                break;
            default:
                $this->notifications->sendToMany([$employeeUserId], 'leave', 'Leave request updated',
                    "Your {$type} request for {$period} is now ".str_replace('_', ' ', (string) $leave->status).'.', $ownUrl, $data);
        }
    }

    /** Active users whose role grants leave approval (super admins are not included). */
    private function leaveApproverIds(): array
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'leave.approve')->orWhere('name', 'leave.approve'))
            ->pluck('id')
            ->all();
    }

    /* ---------------------------------------------------------------- Appraisals */

    private function appraisal(Appraisal $appraisal, string $event): void
    {
        $employee = Employee::query()->with('user')->find($appraisal->employee_id);
        $employeeUserId = $employee?->user_id;
        $supervisorId = $appraisal->manager_user_id ?: $employee?->supervisor_user_id;
        $name = $employee?->user?->name ?? 'The employee';
        $cycle = $appraisal->cycle()->value('name');
        $label = $cycle ? "appraisal ({$cycle})" : 'appraisal';
        $url = $this->route('staff.performance.show', $appraisal);
        $data = ['appraisal_id' => $appraisal->id, 'employee_id' => $appraisal->employee_id, 'status' => $appraisal->status];

        if ($event === 'created') {
            $this->notifications->sendToMany([$employeeUserId], 'appraisal', 'New appraisal assigned',
                "Your {$label} has been set up. Open it to start your self-assessment.", $url, $data);

            return;
        }

        $comment = $this->latestComment($appraisal);

        // [recipients, title, message]
        $routes = match ($appraisal->status) {
            'submitted', 'manager_review' => [
                [[$supervisorId], 'Appraisal submitted for review', "{$name} submitted their {$label} for your review."],
            ],
            'supervisor_review' => [
                [[$employeeUserId], 'Appraisal under review', "Your supervisor has started reviewing your {$label}."],
            ],
            'returned_for_revision' => [
                [[$employeeUserId], 'Appraisal returned for revision', "Your {$label} was returned for revision.".($comment ? ' '.$comment : '')],
            ],
            'meeting_pending' => [
                [[$employeeUserId], 'Appraisal meeting pending', "Your supervisor completed the review of your {$label}. An appraisal meeting is next."],
            ],
            'meeting_completed' => [
                [[$employeeUserId], 'Confirm your appraisal', "The appraisal meeting and agreed scores for your {$label} were recorded. Please review and confirm."],
            ],
            'employee_confirmation' => [
                [[$supervisorId], 'Appraisal confirmed by employee', "{$name} confirmed their {$label}. Please add your confirmation to complete it."],
            ],
            'hr_review' => [
                [[$employeeUserId], 'Appraisal with HR', "Your {$label} has been reviewed by your manager and sent to HR."],
            ],
            'awaiting_acknowledgement' => [
                [[$employeeUserId], 'Acknowledge your appraisal', "HR finalised your {$label}. Please review and acknowledge it."],
            ],
            'completed' => [
                [[$employeeUserId], 'Appraisal completed', "Your {$label} is complete."],
                [[$supervisorId], 'Appraisal completed', "The {$label} for {$name} is complete."],
            ],
            // Transient status set immediately before "completed"; one notice is enough.
            'supervisor_confirmation' => [],
            default => [
                [[$employeeUserId], 'Appraisal updated', "Your {$label} is now ".str_replace('_', ' ', (string) $appraisal->status).'.'],
            ],
        };

        $sent = [];

        foreach ($routes as [$recipients, $title, $message]) {
            $recipients = array_diff(array_filter($recipients), $sent);
            $this->notifications->sendToMany($recipients, 'appraisal', $title, $message, $url, $data);
            $sent = array_merge($sent, $recipients);
        }
    }

    private function latestComment(Appraisal $appraisal): ?string
    {
        $comment = $appraisal->statusHistory()
            ->where('to_status', $appraisal->status)
            ->latest('id')
            ->value('comment');

        return $comment ? Str::limit((string) $comment, 200) : null;
    }

    private function route(string $name, mixed $parameters = []): ?string
    {
        try {
            return route($name, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }
}
