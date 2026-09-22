@extends('layouts.app')
@section('content')
<div class="card"><h1>Staff Exit & Clearance</h1>
<form method="POST" action="{{ route('admin.hr.exits.store') }}">@csrf
<label>Employee</label><select name="employee_id">@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->user->name }}</option>@endforeach</select>
<label>Exit Type</label><select name="exit_type"><option value="resignation">Resignation</option><option value="end_of_contract">End of Contract</option><option value="termination">Termination</option><option value="retirement">Retirement</option><option value="new_organisation">New Organisation</option><option value="other">Other</option></select>
<label>Notice Date</label><input type="date" name="notice_date">
<label>Last Working Date</label><input type="date" name="last_working_date" required>
<label>Reason</label><textarea name="reason"></textarea>
<label>Destination Organisation</label><input name="destination_organisation">
<label>New Role</label><input name="new_role">
<button>Start Exit</button>
</form></div>

@foreach($exits as $exit)
<div class="card"><strong>{{ $exit->employee->user->name }}</strong><br>{{ $exit->exit_type }} · {{ $exit->last_working_date->format('d M Y') }} · {{ $exit->status }}</div>
@endforeach
{{ $exits->links() }}
@endsection
