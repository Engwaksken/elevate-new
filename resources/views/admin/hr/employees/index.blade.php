@extends('layouts.app')
@section('content')
<div class="card"><h1>Employees</h1>
<form method="POST" action="{{ route('admin.hr.employees.store') }}">@csrf
<label>User</label><select name="user_id">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
<label>Employee Number</label><input name="employee_number" required>
<label>Department</label><select name="department_id"><option value="">None</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
<label>Position</label><select name="position_id"><option value="">None</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach</select>
<label>Employment Type</label><input name="employment_type">
<label>Start Date</label><input type="date" name="start_date">
<label>Status</label><select name="status"><option value="active">Active</option><option value="probation">Probation</option><option value="on_leave">On Leave</option><option value="suspended">Suspended</option></select>
<button>Create Employee</button>
</form></div>
@foreach($employees as $employee)
<div class="card"><strong>{{ $employee->user->name }}</strong><br>{{ $employee->employee_number }} · {{ $employee->status }}</div>
@endforeach
{{ $employees->links() }}
@endsection
