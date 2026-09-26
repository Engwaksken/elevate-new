@extends('layouts.admin')
@section('title','Leave Approvals | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Human Resources</span><h1>Leave Approvals</h1><p>Review and approve staff leave requests.</p></div></div>
<div class="admin-stats-grid compact">@foreach([['total','Total Requests','fa-calendar-days'],['pending','Pending','fa-clock'],['supervisor_approved','Supervisor Approved','fa-user-check'],['approved','HR Approved','fa-circle-check']] as [$key,$label,$icon])<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>@endforeach</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar"><div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search employee name or email..."></div><select name="status"><option value="">All statuses</option>@foreach(['pending','supervisor_approved','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select><select name="per_page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select><button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.leave.index') }}" class="btn btn-outline btn-sm">Reset</a></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Employee</th><th>Leave Type</th><th>Dates</th><th>Days</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($requests as $leave)
<tr><td><strong>{{ data_get($leave,'employee.user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($leave,'employee.user.email','') }}</small></td><td>{{ data_get($leave,'leaveType.name','—') }}</td><td>{{ optional($leave->start_date)->format('d M Y') ?: '—' }} — {{ optional($leave->end_date)->format('d M Y') ?: '—' }}</td><td>{{ $leave->days_requested ?? '—' }}</td><td><span class="status-chip {{ $leave->status }}">{{ ucfirst(str_replace('_',' ',$leave->status)) }}</span></td>
<td class="table-actions"><div class="action-group">
@if($leave->status==='pending')<button type="button" class="btn-icon" data-modal-open="supervisor{{ $leave->id }}" title="Supervisor approve"><i class="fas fa-user-check"></i></button>@endif
@if($leave->status==='supervisor_approved')<button type="button" class="btn-icon" data-modal-open="hrApprove{{ $leave->id }}" title="HR approve"><i class="fas fa-check"></i></button>@endif
@if(!in_array($leave->status,['approved','rejected'],true))<button type="button" class="btn-icon danger" data-modal-open="reject{{ $leave->id }}" title="Reject"><i class="fas fa-xmark"></i></button>@endif
</div></td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No leave requests found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $requests->links() }}</div></div>

@foreach($requests as $leave)
@if($leave->status==='pending')
<div class="eh-modal" id="supervisor{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Supervisor Approve?</h2><p>{{ data_get($leave,'employee.user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Record supervisor approval for this leave request?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.hr.leave.supervisor-approve',$leave) }}">@csrf<button class="btn btn-primary">Approve</button></form></div></div></div>
@endif
@if($leave->status==='supervisor_approved')
<div class="eh-modal" id="hrApprove{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>HR Approve?</h2><p>{{ data_get($leave,'employee.user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Give final HR approval for this leave request?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.hr.leave.hr-approve',$leave) }}">@csrf<button class="btn btn-primary">Approve Leave</button></form></div></div></div>
@endif
@if(!in_array($leave->status,['approved','rejected'],true))
<div class="eh-modal" id="reject{{ $leave->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Reject Leave?</h2><p>{{ data_get($leave,'employee.user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.hr.leave.reject',$leave) }}">@csrf<div class="eh-modal-body"><div class="form-group"><label>Decision Notes</label><textarea name="decision_notes" placeholder="Reason for rejection..."></textarea></div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-danger">Reject</button></div></form></div></div>
@endif
@endforeach
@endsection
