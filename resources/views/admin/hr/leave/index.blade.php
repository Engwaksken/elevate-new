@extends('layouts.admin')
@section('content')
<div class="card"><h1>Leave Approvals</h1></div>
@foreach($requests as $leave)
<div class="card">
<strong>{{ $leave->employee->user->name }}</strong><br>{{ $leave->leaveType->name }} · {{ $leave->days_requested }} days · {{ $leave->status }}
@if($leave->status==='submitted')
<form method="POST" action="{{ route('admin.hr.leave.supervisor-approve',$leave) }}">@csrf<button>Supervisor Approve</button></form>
@endif
@if($leave->status==='supervisor_approved')
<form method="POST" action="{{ route('admin.hr.leave.hr-approve',$leave) }}">@csrf<button>HR Approve</button></form>
@endif
</div>
@endforeach
{{ $requests->links() }}
@endsection
