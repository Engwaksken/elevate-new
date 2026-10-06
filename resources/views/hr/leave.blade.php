@extends('layouts.app')
@section('content')
<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap"><h1>My Leave</h1><x-export-buttons /></div>
<form method="POST" action="{{ route('hr.leave.store') }}">@csrf
<label>Leave Type</label><select name="leave_type_id">@foreach($leaveTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
<label>Start Date</label><input type="date" name="start_date" required>
<label>End Date</label><input type="date" name="end_date" required>
<label>Reason</label><textarea name="reason"></textarea>
<button>Submit Leave Request</button>
</form></div>
@foreach($requests as $request)
<div class="card">{{ $request->leaveType->name }} · {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }} · {{ $request->status }}</div>
@endforeach
{{ $requests->links() }}
@endsection
