@extends('layouts.admin')
@section('title','Performance Appraisals | ElevateHer360 Administration')

@section('content')
@php
    $appraisalAdminUser = auth()->user();
    $canAdministerAppraisals = $appraisalAdminUser
        && (
            $appraisalAdminUser->isSuperAdmin()
            || $appraisalAdminUser->hasAnyRole(['hr', 'HR'])
        );
    $canManageAppraisals = $appraisalAdminUser && $appraisalAdminUser->hasPermission('appraisals.manage');
    $workspaceStatuses = \App\Services\HR\AppraisalWorkflowService::WORKSPACE_STATUSES;
    $usesWorkspace = fn ($appraisal) => in_array($appraisal->status, $workspaceStatuses, true) || $appraisal->kras_count > 0;
@endphp
<div class="admin-page-header">
<div>
<span class="admin-eyebrow">Human Resources</span>
<h1>Performance Appraisals</h1>
<p>Create appraisal cycles, assign uploaded WITU templates and monitor staff appraisal progress.</p>
</div>

<div class="admin-page-actions">
@if($canAdministerAppraisals && Route::has('admin.hr.kpi-templates.index'))
<a href="{{ route('admin.hr.kpi-templates.index') }}" class="btn btn-outline"><i class="fas fa-file-excel"></i> KPI Templates</a>
@endif
@if($canAdministerAppraisals)
<button type="button" class="btn btn-outline" data-modal-open="assignAppraisal"><i class="fas fa-user-check"></i> Assign Appraisal</button>
@endif
<button type="button" class="btn btn-primary" data-modal-open="newAppraisalCycle"><i class="fas fa-plus"></i> New Cycle</button>
</div>
</div>


@php
$collection=$appraisals->getCollection();
$stats=[
'total'=>$appraisals->total(),
'self'=>$collection->where('status','self_assessment')->count(),
'manager'=>$collection->where('status','manager_review')->count(),
'complete'=>$collection->where('status','completed')->count(),
];
@endphp

<div class="admin-stats-grid compact">
@foreach([
['total','Total Appraisals','fa-clipboard-list'],
['self','Self Assessment','fa-user-pen'],
['manager','Manager Review','fa-user-tie'],
['complete','Completed','fa-circle-check'],
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
@php($bulkRoute = route('admin.hr.appraisals.bulk-destroy')) @php($bulkTableId = 'appraisalsTable')
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap">
<table class="admin-table" id="{{ $bulkTableId }}">
<thead>
<tr>
<th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th>
<th>Employee</th>
<th>Cycle / Template</th>
<th>Status</th>
<th>Completion</th>
<th>Performance</th>
<th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($appraisals as $appraisal)
<tr>
<td><input type="checkbox" data-row-select value="{{ $appraisal->id }}" aria-label="Select {{ $appraisal->employee?->user?->name ?: 'appraisal' }}"></td>
<td>
<strong>{{ $appraisal->employee?->user?->name ?: '—' }}</strong>
<small class="admin-cell-hint">{{ $appraisal->employee?->employee_number ?: '' }}</small>
</td>

<td>
{{ $appraisal->cycle?->name ?: '—' }}
<small class="admin-cell-hint">{{ $appraisal->kpiTemplate?->name ?: 'No template assigned' }}</small>
</td>

<td>
<span class="status-chip {{ $appraisal->status }}">{{ ucwords(str_replace('_',' ',$appraisal->status)) }}</span>
@if($appraisal->locked_at)<small class="admin-cell-hint"><i class="fas fa-lock"></i> Locked {{ $appraisal->locked_at->format('d M Y') }}</small>@endif
</td>

<td>
<div class="table-progress">
<span>{{ number_format((float)$appraisal->completion_percent,0) }}%</span>
<div class="progress-track"><i style="width:{{ min(100,(float)$appraisal->completion_percent) }}%"></i></div>
</div>
</td>

<td>
<strong>{{ $appraisal->performance_percent !== null ? number_format((float)$appraisal->performance_percent,1).'%' : '—' }}</strong>
</td>

<td class="table-actions">
<div class="action-group">
@if(Route::has('admin.hr.appraisals.kpis'))
<a class="btn-icon" href="{{ route('admin.hr.appraisals.kpis',$appraisal) }}" title="Admin KPI Form"><i class="fas fa-chart-line"></i></a>
@endif

@if($usesWorkspace($appraisal))
<a class="btn-icon" href="{{ route('staff.performance.show',$appraisal) }}" title="View Appraisal Workflow"><i class="fas fa-eye"></i></a>
@else
<a class="btn-icon" href="{{ route('staff.appraisals.show',$appraisal) }}" title="View Appraisal"><i class="fas fa-eye"></i></a>
@endif

@if($appraisal->employee_submitted_at)
<a class="btn-icon" href="{{ route('staff.appraisals.export.excel',[$appraisal,'preview'=>1]) }}" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $appraisal->employee?->user?->name }} · WITU appraisal workbook" title="Preview WITU Excel"><i class="fas fa-table"></i></a>
<a class="btn-icon" href="{{ route('staff.appraisals.export.excel',$appraisal) }}" title="Export WITU Excel"><i class="fas fa-file-excel"></i></a>
@endif

@if($canManageAppraisals && !$appraisal->locked_at)
<button type="button" class="btn-icon" data-modal-open="lockAppraisal{{ $appraisal->id }}" title="Lock Appraisal"><i class="fas fa-lock"></i></button>
@endif

@if($canManageAppraisals && ($appraisal->locked_at || $appraisal->status === 'completed'))
<button type="button" class="btn-icon" data-modal-open="reopenAppraisal{{ $appraisal->id }}" title="Reopen Appraisal"><i class="fas fa-lock-open"></i></button>
@endif

@if($appraisal->manager_submitted_at && !$appraisal->hr_finalised_at && !$usesWorkspace($appraisal))
<button type="button" class="btn-icon" data-modal-open="finaliseAppraisal{{ $appraisal->id }}" title="HR Finalise"><i class="fas fa-circle-check"></i></button>
@endif
</div>
</td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty">No appraisals found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $appraisals->links() }}</div>
</div>

<div class="eh-modal" id="newAppraisalCycle" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header">
<div><h2>New Appraisal Cycle</h2><p>Create the appraisal period.</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.hr.appraisal-cycles.store') }}">
@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Cycle Name *</label><input name="name" required placeholder="e.g. Q3 2026 Performance Appraisal"><small class="form-hint">Include the quarter/year where applicable.</small></div>
<div class="form-group"><label>Type *</label><select name="cycle_type"><option value="quarterly">Quarterly</option><option value="annual">Annual</option><option value="mid_year">Mid-Year</option><option value="probation">Probation</option><option value="special">Special</option></select></div>
<div class="form-group"><label>Start Date *</label><input type="date" name="start_date" required></div>
<div class="form-group"><label>End Date *</label><input type="date" name="end_date" required></div>
<div class="form-group"><label>Self Assessment Due</label><input type="date" name="self_assessment_due"></div>
<div class="form-group"><label>Manager Review Due</label><input type="date" name="manager_review_due"></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Cycle</button></div>
</form>
</div>
</div>

@if($canAdministerAppraisals)
<div class="eh-modal" id="assignAppraisal" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
<div><h2>Assign Staff Appraisal</h2><p>Choose employee, cycle, manager and uploaded WITU template.</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.hr.appraisals.assign') }}">
@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Employee *</label><select name="employee_id" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->user?->name }}{{ $employee->employee_number ? ' · '.$employee->employee_number : '' }}</option>@endforeach</select><small class="form-hint">The employee will complete the self assessment.</small></div>

<div class="form-group"><label>Appraisal Cycle *</label><select name="appraisal_cycle_id" required><option value="">Select cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->name }}</option>@endforeach</select></div>

<div class="form-group"><label>Manager / Supervisor</label><select name="manager_user_id"><option value="">Use employee supervisor</option>@foreach($managers as $manager)<option value="{{ $manager->id }}">{{ $manager->name }}</option>@endforeach</select><small class="form-hint">Leave blank to use the supervisor stored on the Employee profile.</small></div>

<div class="form-group"><label>KPI/Appraisal Template *</label><select name="hr_kpi_template_id" required><option value="">Select uploaded template</option>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }} · {{ ucwords(str_replace('_',' ',$template->template_type)) }}</option>@endforeach</select><small class="form-hint">The staff form will follow this uploaded WITU workbook structure.</small></div>
</div></div>

<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Assign Appraisal</button></div>
</form>
</div>
</div>
@endif

@foreach($appraisals as $appraisal)
@if($canManageAppraisals && !$appraisal->locked_at)
<div class="eh-modal" id="lockAppraisal{{ $appraisal->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Lock Appraisal?</h2><p>{{ $appraisal->employee?->user?->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.appraisals.lock',$appraisal) }}">
@csrf
<div class="eh-modal-body">
<p>Locking freezes the appraisal. The employee and supervisor can no longer edit, submit or confirm it until HR reopens it.</p>
<div class="form-group"><label>Reason</label><textarea name="reason" rows="2" placeholder="Optional, e.g. cycle closed"></textarea></div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Lock Appraisal</button></div>
</form>
</div>
</div>
@endif

@if($canManageAppraisals && ($appraisal->locked_at || $appraisal->status === 'completed'))
<div class="eh-modal" id="reopenAppraisal{{ $appraisal->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Reopen Appraisal?</h2><p>{{ $appraisal->employee?->user?->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.appraisals.reopen',$appraisal) }}">
@csrf
<div class="eh-modal-body">
<p>Reopening unlocks the appraisal, clears submissions and confirmations, and returns it to the employee for revision. Existing scores are kept.</p>
<div class="form-group"><label>Reason *</label><textarea name="reason" rows="2" required placeholder="Why is this appraisal being reopened?"></textarea></div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Reopen Appraisal</button></div>
</form>
</div>
</div>
@endif

@if($appraisal->manager_submitted_at && !$appraisal->hr_finalised_at && !$usesWorkspace($appraisal))
<div class="eh-modal" id="finaliseAppraisal{{ $appraisal->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Finalise Appraisal?</h2><p>{{ $appraisal->employee?->user?->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body">
<p>Performance: <strong>{{ $appraisal->performance_percent !== null ? number_format((float)$appraisal->performance_percent,1).'%' : 'Not rated' }}</strong></p>
<p>Finalising sends the appraisal to the employee for electronic acknowledgement.</p>
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<form method="POST" action="{{ route('admin.hr.appraisals.finalise',$appraisal) }}">@csrf<button class="btn btn-primary">Finalise Appraisal</button></form>
</div>
</div>
</div>
@endif
@endforeach
@endsection
