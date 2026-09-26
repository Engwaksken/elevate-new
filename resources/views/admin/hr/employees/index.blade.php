@extends('layouts.admin')
@section('title','Employees | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Human Resources</span><h1>Employees</h1><p>Manage employee records, employment status and contracts.</p></div><div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createEmployeeModal"><i class="fas fa-plus"></i> New Employee</button></div></div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Employees','fa-users'],['active','Active','fa-circle-check'],['probation','Probation','fa-hourglass-half'],['on_leave','On Leave','fa-plane-departure']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search name, email, employee number, type or location..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['active','probation','on_leave','suspended','exiting','exited'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Employee</th><th>No.</th><th>Type</th><th>Location</th><th>Start Date</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($employees as $employee)
<tr>
<td><strong>{{ data_get($employee,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($employee,'user.email','') }}</small></td>
<td>{{ $employee->employee_number }}</td>
<td>{{ $employee->employment_type ?: '—' }}</td>
<td>{{ $employee->work_location ?: '—' }}</td>
<td>{{ optional($employee->start_date)->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $employee->status }}">{{ ucfirst(str_replace('_',' ',$employee->status)) }}</span></td>
<td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="contract{{ $employee->id }}"><i class="fas fa-file-signature"></i> Add Contract</button></td>
</tr>
@empty<tr><td colspan="7"><div class="admin-empty">No employees found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $employees->links() }}</div>
</div>

<div class="eh-modal" id="createEmployeeModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Employee</h2><p>Create an employee record for an existing staff user.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.employees.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Staff User *</label><select name="user_id" required><option value="">Select staff user</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>@endforeach</select></div>
<div class="form-group"><label>Employee Number *</label><input name="employee_number" required></div>
<div class="form-group"><label>Department</label><select name="department_id"><option value="">None</option>@foreach($departments as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Position</label><select name="position_id"><option value="">None</option>@foreach($positions as $x)<option value="{{ $x->id }}">{{ $x->title }}</option>@endforeach</select></div>
<div class="form-group"><label>Supervisor</label><select name="supervisor_user_id"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Employment Type</label><input name="employment_type" placeholder="Permanent, Contract, Consultant..."></div>
<div class="form-group"><label>Work Location</label><input name="work_location"></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>Probation End Date</label><input type="date" name="probation_end_date"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['active','probation','on_leave','suspended'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Employee</button></div>
</form></div></div>

@foreach($employees as $employee)
<div class="eh-modal" id="contract{{ $employee->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Contract</h2><p>{{ data_get($employee,'user.name','Employee') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.hr.contracts.store',$employee) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Contract Type</label><input name="contract_type"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','active','expired','terminated'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group"><label>Start Date *</label><input type="date" name="start_date" required></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
<div class="form-group"><label>Gross Salary</label><input type="number" step=".01" min="0" name="gross_salary"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Contract</button></div>
</form></div></div>
@endforeach
@endsection
