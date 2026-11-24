@extends('layouts.admin')
@section('title','Employees | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Human Resources</span><h1>Employee Directory</h1><p>Manage employee records, placement, contracts and each person's HR documents and policy acknowledgements.</p></div><div class="admin-page-actions"><x-export-buttons />@if(auth()->user()->hasPermission('hr.manage'))<button type="button" class="btn btn-primary" data-modal-open="createEmployeeModal"><i class="fas fa-plus"></i> New Employee</button>@endif</div></div>
@include('admin.hr.partials.nav')

<div class="admin-stats-grid compact">
@foreach([['total','Total Employees','fa-users'],['active','Active','fa-circle-check'],['probation','Probation','fa-hourglass-half'],['on_leave','On Leave','fa-plane-departure']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" data-auto-filter>
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search name, email, number, department, position or location..."></div>
<select name="department" aria-label="Department"><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) request('department') === $d->id)>{{ $d->name }}</option>@endforeach</select>
<select name="status"><option value="">All statuses</option>@foreach(['active','probation','on_leave','suspended','exiting','exited'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@php
$bulkRoute = route('admin.hr.employees.bulk-destroy');
$bulkTableId = 'employeesGrid';
$canManageHr = auth()->user()->hasPermission('hr.manage');
$groups = $employees->getCollection()->toBase()->groupBy(fn ($e) => $departmentNames[$e->department_id] ?? 'No department')->sortKeys();
if ($groups->has('No department')) { $groups = $groups->except('No department')->put('No department', $groups['No department']); }
$statusAccent = ['active' => 'is-open', 'probation' => 'is-progress', 'on_leave' => 'is-draft', 'suspended' => 'is-overdue', 'exiting' => 'is-overdue', 'exited' => 'is-done'];
@endphp
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])

<div id="{{ $bulkTableId }}" data-emp-grid>
@if($employees->count())
<div class="ag-section-tools">
    @if($canManageHr)<label style="display:flex;gap:6px;align-items:center;font-weight:600;font-size:.8rem"><input type="checkbox" data-emp-select-all style="width:auto" aria-label="Select all employees on this page"> Select all</label>@endif
    <input type="search" class="ag-filter" placeholder="Quick filter on this page..." aria-label="Filter employees on this page" data-emp-filter>
    <button type="button" class="ag-toolbar-btn" data-emp-toggle="open">Expand all</button>
    <button type="button" class="ag-toolbar-btn" data-emp-toggle="close">Collapse all</button>
</div>
<p class="ag-empty-filter" data-emp-no-match hidden>No employees on this page match.</p>
@endif

@forelse($groups as $groupName => $members)
<details class="ag-section" open data-emp-section>
    <summary><h2>{{ $groupName }}</h2><span class="ag-count">{{ $members->count() }}</span>
        @php $out = $members->filter(fn ($e) => ($r = $compliance[$e->user_id] ?? null) && $r['documents'] + $r['policies'] > 0)->count(); @endphp
        @if($out)<span class="ag-hint">{{ $out }} with outstanding HR items</span>@endif
    </summary>
    <div class="ag-section-body">
    <div class="ag-grid">
    @foreach($members as $employee)
        @php
            $hrRow = $compliance[$employee->user_id] ?? null;
            $hrOut = $hrRow ? $hrRow['documents'] + $hrRow['policies'] : 0;
            $name = data_get($employee, 'user.name', 'Employee');
        @endphp
        <article class="ag-card {{ $statusAccent[$employee->status] ?? '' }}" data-emp-card data-search="{{ \Illuminate\Support\Str::lower($name.' '.data_get($employee, 'user.email').' '.$employee->employee_number.' '.($positionNames[$employee->position_id] ?? '').' '.$employee->work_location) }}">
            <div style="display:flex;gap:8px;align-items:flex-start">
                @if($canManageHr)<input type="checkbox" data-row-select value="{{ $employee->id }}" aria-label="Select {{ $name }}" style="width:auto;margin-top:3px">@endif
                <div style="min-width:0">
                    <h3><a href="{{ route('admin.hr.employees.show', $employee) }}">{{ $name }}</a></h3>
                    <small class="admin-cell-hint" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block">{{ data_get($employee, 'user.email') }}</small>
                </div>
            </div>
            <div class="ag-chips">
                <span class="status-chip {{ $employee->status }}">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
                @if(! $hrRow)
                @elseif($hrOut)<span class="status-chip on_hold">{{ $hrOut }} HR item{{ $hrOut === 1 ? '' : 's' }} outstanding</span>
                @else<span class="status-chip active">HR file complete</span>@endif
            </div>
            <div class="ag-meta">
                <div><i class="fas fa-briefcase" aria-hidden="true"></i><strong>{{ $positionNames[$employee->position_id] ?? 'No position' }}</strong></div>
                <div><i class="fas fa-hashtag" aria-hidden="true"></i>{{ $employee->employee_number }}{{ $employee->employment_type ? ' · '.$employee->employment_type : '' }}</div>
                <div><i class="fas fa-calendar" aria-hidden="true"></i>Started {{ optional($employee->start_date)->format('d M Y') ?: '-' }}</div>
            </div>
            <details class="ag-more" data-emp-more>
                <summary>Details &amp; actions</summary>
                <div class="ag-meta">
                    <div><i class="fas fa-user-tie" aria-hidden="true"></i>Supervisor: {{ $employee->supervisor->name ?? '-' }}</div>
                    <div><i class="fas fa-location-dot" aria-hidden="true"></i>{{ $employee->work_location ?: 'No location set' }}</div>
                    <div><i class="fas fa-hourglass-half" aria-hidden="true"></i>Probation ends {{ optional($employee->probation_end_date)->format('d M Y') ?: '-' }}</div>
                    <div><i class="fas fa-phone" aria-hidden="true"></i>Emergency: {{ collect([$employee->emergency_contact_name, $employee->emergency_contact_phone])->filter()->join(' · ') ?: '-' }}</div>
                    @if($hrOut)
                        @if($hrRow['missing'])<div><i class="fas fa-file-circle-exclamation" aria-hidden="true"></i>Missing: {{ implode(', ', $hrRow['missing']) }}</div>@endif
                        @if($hrRow['pending_policies'])<div><i class="fas fa-scale-balanced" aria-hidden="true"></i>To acknowledge: {{ implode(', ', $hrRow['pending_policies']) }}</div>@endif
                    @endif
                </div>
                <div class="ag-card-actions">
                    <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline btn-sm">Profile</a>
                    @if($canManageHr)
                        <button type="button" class="btn btn-outline btn-sm" data-modal-open="contract{{ $employee->id }}"><i class="fas fa-file-signature" aria-hidden="true"></i> Contract</button>
                        @if($hrOut)
                            <form method="POST" action="{{ route('admin.hr.compliance.remind') }}">@csrf<input type="hidden" name="scope" value="all"><input type="hidden" name="user_ids[]" value="{{ $employee->user_id }}"><button class="btn btn-outline btn-sm"><i class="fas fa-bell" aria-hidden="true"></i> Remind</button></form>
                        @endif
                    @endif
                </div>
            </details>
        </article>
    @endforeach
    </div>
    </div>
</details>
@empty
<div class="admin-empty">No employees found.</div>
@endforelse
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-emp-grid]');
    if (!root) return;
    var boxes = root.querySelectorAll('[data-row-select]');
    var bar = document.getElementById(root.id + '-bar');
    var all = root.querySelector('[data-emp-select-all]');
    function sync() {
        var n = root.querySelectorAll('[data-row-select]:checked').length;
        if (bar) { bar.classList.toggle('is-visible', n > 0); var c = bar.querySelector('[data-selected-count]'); if (c) c.textContent = n; }
        if (all) all.checked = n > 0 && n === boxes.length;
    }
    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    if (all) all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); sync(); });
    root.querySelectorAll('[data-emp-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var open = btn.dataset.empToggle === 'open';
            root.querySelectorAll('[data-emp-section]').forEach(function (d) { d.open = true; });
            root.querySelectorAll('[data-emp-more]').forEach(function (d) { d.open = open; });
            if (!open) root.querySelectorAll('[data-emp-section]').forEach(function (d) { d.open = false; });
        });
    });
    var filter = root.querySelector('[data-emp-filter]');
    var none = root.querySelector('[data-emp-no-match]');
    if (filter) filter.addEventListener('input', function () {
        var q = filter.value.trim().toLowerCase(), shown = 0;
        root.querySelectorAll('[data-emp-section]').forEach(function (section) {
            var visible = 0;
            section.querySelectorAll('[data-emp-card]').forEach(function (card) {
                card.hidden = q !== '' && card.dataset.search.indexOf(q) === -1;
                if (!card.hidden) visible++;
            });
            section.hidden = visible === 0;
            if (q && visible) section.open = true;
            shown += visible;
        });
        if (none) none.hidden = shown > 0;
    });
    sync();
});
</script>
<div class="admin-pagination">{{ $employees->links() }}</div>
</div>

<div class="eh-modal" id="createEmployeeModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Employee</h2><p>Create an employee record for an existing staff user.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.employees.store') }}">@csrf
@php
$employeeFormTabs = [
    'employee' => ['label' => 'Employee', 'icon' => 'fa-id-badge', 'fields' => ['user_id', 'employee_number', 'employment_type', 'status']],
    'placement' => ['label' => 'Placement', 'icon' => 'fa-sitemap', 'fields' => ['department_id', 'position_id', 'supervisor_user_id', 'work_location']],
    'dates' => ['label' => 'Dates', 'icon' => 'fa-calendar-days', 'fields' => ['start_date', 'probation_end_date']],
];
@endphp
<div class="eh-modal-body">
<x-form-tabs id="employee-create" label="Employee details" :tabs="$employeeFormTabs">
<x-form-tab name="employee"><div class="modal-grid">
<div class="form-group"><label>Staff User *</label><select name="user_id" required><option value="">Select staff user</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} - {{ $u->email }}</option>@endforeach</select></div>
<div class="form-group"><label>Employee Number *</label><input name="employee_number" required></div>
<div class="form-group"><label>Employment Type</label><input name="employment_type" placeholder="Permanent, Contract, Consultant..."></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['active','probation','on_leave','suspended'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
</div></x-form-tab>
<x-form-tab name="placement"><div class="modal-grid">
<div class="form-group"><label for="employee-department">Department</label><select id="employee-department" name="department_id"><option value="">None</option>@foreach($departments as $x)<option value="{{ $x->id }}" @selected((int) old('department_id') === $x->id)>{{ $x->name }}</option>@endforeach</select>@if(\App\Support\MasterListAccess::canManageDepartments(auth()->user()))<small class="form-hint">@if($departments->isEmpty())No departments yet. @endif<a href="{{ route('admin.departments.index') }}">Manage departments</a></small>@endif</div>
<div class="form-group"><label>Position</label><select name="position_id"><option value="">None</option>@foreach($positions as $x)<option value="{{ $x->id }}">{{ $x->title }}</option>@endforeach</select></div>
<div class="form-group"><label>Supervisor</label><select name="supervisor_user_id"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Work Location</label><input name="work_location"></div>
</div></x-form-tab>
<x-form-tab name="dates"><div class="modal-grid">
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>Probation End Date</label><input type="date" name="probation_end_date"></div>
</div></x-form-tab>
</x-form-tabs>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Employee</button></div>
</form></div></div>

@foreach($employees as $employee)
<div class="eh-modal" id="contract{{ $employee->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Contract</h2><p>{{ data_get($employee,'user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.contracts.store',$employee) }}" enctype="multipart/form-data">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Contract Type</label><input name="contract_type"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','active','expired','terminated'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group"><label>Start Date *</label><input type="date" name="start_date" required></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
<div class="form-group"><label>Gross Salary</label><input type="number" step=".01" min="0" name="gross_salary"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Contract file (PDF, DOC, DOCX or image, up to 10 MB)</label><input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"><small class="form-hint">Attach the signed contract document for record-keeping.</small></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Contract</button></div>
</form></div></div>
@endforeach
@endsection
