@extends('layouts.app')
@section('content')
<div class="card"><h1>Mentor Applications</h1>
@foreach($mentors as $mentor)
<div class="card">
<strong>{{ $mentor->user->name }}</strong><br>
{{ $mentor->organisation }} · {{ $mentor->job_title }} · {{ $mentor->status }}
@if($mentor->status==='pending')
<form method="POST" action="{{ route('admin.mentorship.mentors.approve',$mentor) }}" style="display:inline">@csrf<button>Approve</button></form>
<form method="POST" action="{{ route('admin.mentorship.mentors.reject',$mentor) }}" style="display:inline">@csrf<button>Reject</button></form>
@endif
</div>
@endforeach
{{ $mentors->links() }}
</div>
@endsection
