@extends('layouts.app')
@section('content')
<div class="card"><h1>Participant Outcomes</h1>
@foreach($outcomes as $outcome)
<div class="card"><strong>{{ $outcome->user->name }}</strong><br>{{ $outcome->outcome_type }} · {{ $outcome->organisation_name }} · {{ $outcome->verification_status }}
@if($outcome->verification_status==='submitted')
<form method="POST" action="{{ route('admin.jobs.outcomes.verify',$outcome) }}" style="display:inline">@csrf<button>Verify</button></form>
<form method="POST" action="{{ route('admin.jobs.outcomes.reject',$outcome) }}" style="display:inline">@csrf<button>Reject</button></form>
@endif
</div>
@endforeach
{{ $outcomes->links() }}
</div>
@endsection
