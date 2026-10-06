@extends('layouts.admin')
@section('title','Leave Approvals | ElevateHer360 Administration')
@section('content')
@use('App\Support\LeaveApprovalAccess', 'Access')
@use('App\Http\Controllers\Admin\HR\LeaveApprovalController', 'Leave')
@php($me = auth()->user())
@php($chip = ['pending' => 'pending', 'submitted' => 'pending', 'supervisor_approved' => 'in_progress', 'hr_approved' => 'approved', 'rejected' => 'rejected', 'cancelled' => 'cancelled'])
<div class="admin-page-header"><div><span class="admin-eyebrow">Human Resources</span><h1>Leave Approvals</h1><p>{{ $isHr ? 'Review every staff leave request: supervisors approve first, then HR gives the final approval.' : 'Review leave requests from the staff you supervise. HR gives the final approval.' }}</p></div><div class="admin-page-actions"><x-export-buttons /></div></div>
<div class="admin-stats-grid compact">@foreach([['total','Total requests','fa-calendar-days'],['awaiting_supervisor','Awaiting supervisor','fa-clock'],['awaiting_hr','Awaiting HR','fa-user-check'],['approved','Approved','fa-circle-check']] as [$key,$label,$icon])<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>@endforeach</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar"><div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search employee name or email..." aria-label="Search employee"></div><select name="status" aria-label="Status"><option value="">All statuses</option>@foreach(['submitted' => 'Awaiting supervisor','supervisor_approved' => 'Awaiting HR','hr_approved' => 'Approved','rejected' => 'Rejected','cancelled' => 'Cancelled'] as $s => $label)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $label }}</option>@endforeach</select><select name="per_page" aria-label="Rows per page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select><button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.leave.index') }}" class="btn btn-outline btn-sm">Reset</a></form>
@php($bulkTableId = 'leaveRequestsTable')
@if($isHr)
@include('partials.admin-bulk-bar', ['bulkRoute' => route('admin.hr.leave.bulk-destroy'), 'bulkTableId' => $bulkTableId])
@endif
<div class="admin-table-wrap"><table class="admin-table" id="{{ $bulkTableId }}"><thead><tr>@if($isHr)<th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th>@endif<th>Employee</th><th>Leave Type</th><th>Dates</th><th>Days</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($requests as $leave)
<tr>@if($isHr)<td><input type="checkbox" data-row-select value="{{ $leave->id }}" aria-label="Select {{ data_get($leave,'employee.user.name','leave request') }}"></td>@endif
<td><strong>{{ data_get($leave,'employee.user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($leave,'employee.user.email','') }}@if($isHr && $leave->employee?->supervisor) · Supervisor: {{ $leave->employee->supervisor->name }}@endif</small></td>
<td>{{ data_get($leave,'leaveType.name','—') }}@if($leave->reason)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($leave->reason, 70) }}</small>@endif</td>
<td>{{ optional($leave->start_date)->format('d M Y') ?: '—' }} — {{ optional($leave->end_date)->format('d M Y') ?: '—' }}</td>
<td>{{ rtrim(rtrim(number_format((float) $leave->days_requested, 1), '0'), '.') ?: '—' }}</td>
<td><span class="status-chip {{ $chip[$leave->status] ?? $leave->status }}">{{ Leave::statusLabel($leave->status) }}</span>@if($leave->decision_notes)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($leave->decision_notes, 60) }}</small>@endif</td>
<td class="table-actions"><div class="action-group">
@if(Access::canSupervisorApprove($me, $leave))<button type="button" class="btn-icon" data-modal-open="supervisor{{ $leave->id }}" title="Supervisor approve" aria-label="Supervisor approve"><i class="fas fa-user-check"></i></button>@endif
@if(Access::canHrApprove($me, $leave))<button type="button" class="btn-icon" data-modal-open="hrApprove{{ $leave->id }}" title="Final approval" aria-label="Final approval"><i class="fas fa-check-double"></i></button>@endif
@if(Access::canEdit($me, $leave))<button type="button" class="btn-icon" data-modal-open="edit{{ $leave->id }}" title="Edit" aria-label="Edit"><i class="fas fa-pen"></i></button>@endif
@if(Access::canReject($me, $leave))<button type="button" class="btn-icon danger" data-modal-open="reject{{ $leave->id }}" title="Reject" aria-label="Reject"><i class="fas fa-xmark"></i></button>@endif
@if(Access::canCancel($me, $leave))<button type="button" class="btn-icon danger" data-modal-open="cancel{{ $leave->id }}" title="Cancel approved leave" aria-label="Cancel approved leave"><i class="fas fa-ban"></i></button>@endif
</div></td></tr>
@empty<tr><td colspan="{{ $isHr ? 7 : 6 }}"><div class="admin-empty">No leave requests found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $requests->links() }}</div></div>

@foreach($requests as $leave)
@php($who = data_get($leave,'employee.user.name','Employee'))
@if(Access::canSupervisorApprove($me, $leave))
<div class="eh-modal" id="supervisor{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Supervisor approval</h2><p>{{ $who }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Approve {{ $who }}'s {{ data_get($leave,'leaveType.name','leave') }} from {{ optional($leave->start_date)->format('d M') }} to {{ optional($leave->end_date)->format('d M Y') }}? HR gives the final approval.</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.hr.leave.supervisor-approve',$leave) }}">@csrf<button class="btn btn-primary">Approve</button></form></div></div></div>
@endif
@if(Access::canHrApprove($me, $leave))
<div class="eh-modal" id="hrApprove{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Final approval</h2><p>{{ $who }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Give final approval for {{ rtrim(rtrim(number_format((float) $leave->days_requested, 1), '0'), '.') }} day(s) of {{ data_get($leave,'leaveType.name','leave') }}? The days are taken from the leave balance.</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.hr.leave.hr-approve',$leave) }}">@csrf<button class="btn btn-primary">Approve leave</button></form></div></div></div>
@endif
@if(Access::canEdit($me, $leave))
<div class="eh-modal" id="edit{{ $leave->id }}" aria-hidden="true" @if($errors->any() && (int) old('leave_id') === $leave->id) data-modal-autoopen @endif><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Edit leave request</h2><p>{{ $who }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.leave.update',$leave) }}">@csrf @method('PUT')<input type="hidden" name="leave_id" value="{{ $leave->id }}">
<div class="eh-modal-body">
@if($errors->any() && (int) old('leave_id') === $leave->id)<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
<div class="modal-grid">
<div class="form-group full"><label for="lt{{ $leave->id }}">Leave type *</label><select id="lt{{ $leave->id }}" name="leave_type_id" required>@foreach($leaveTypes as $type)<option value="{{ $type->id }}" @selected((int) old('leave_type_id', $leave->leave_type_id) === $type->id)>{{ $type->name }}</option>@endforeach</select></div>
<div class="form-group"><label for="ls{{ $leave->id }}">First day *</label><input id="ls{{ $leave->id }}" type="date" name="start_date" value="{{ old('start_date', optional($leave->start_date)->toDateString()) }}" required></div>
<div class="form-group"><label for="le{{ $leave->id }}">Last day *</label><input id="le{{ $leave->id }}" type="date" name="end_date" value="{{ old('end_date', optional($leave->end_date)->toDateString()) }}" required></div>
<div class="form-group full"><label for="lr{{ $leave->id }}">Reason</label><textarea id="lr{{ $leave->id }}" name="reason" rows="2" maxlength="2000">{{ old('reason', $leave->reason) }}</textarea></div>
<div class="form-group full"><label for="ln{{ $leave->id }}">HR notes</label><textarea id="ln{{ $leave->id }}" name="decision_notes" rows="2" maxlength="2000">{{ old('decision_notes', $leave->decision_notes) }}</textarea></div>
</div><p class="admin-cell-hint">Days are recalculated from the dates (weekends excluded). The request keeps its current approval stage.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save changes</button></div></form></div></div>
@endif
@if(Access::canReject($me, $leave))
<div class="eh-modal" id="reject{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Reject leave</h2><p>{{ $who }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.hr.leave.reject',$leave) }}">@csrf<div class="eh-modal-body"><div class="form-group"><label for="rj{{ $leave->id }}">Reason (shared with the employee)</label><textarea id="rj{{ $leave->id }}" name="decision_notes" placeholder="Reason for rejection..."></textarea></div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-danger">Reject</button></div></form></div></div>
@endif
@if(Access::canCancel($me, $leave))
<div class="eh-modal" id="cancel{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Cancel approved leave</h2><p>{{ $who }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.hr.leave.cancel',$leave) }}">@csrf<div class="eh-modal-body"><p>The {{ rtrim(rtrim(number_format((float) $leave->days_requested, 1), '0'), '.') }} day(s) go back to {{ $who }}'s leave balance.</p><div class="form-group"><label for="cn{{ $leave->id }}">Reason</label><textarea id="cn{{ $leave->id }}" name="decision_notes" placeholder="Why is this leave being cancelled?"></textarea></div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep leave</button><button class="btn btn-danger">Cancel leave</button></div></form></div></div>
@endif
@endforeach
@endsection
