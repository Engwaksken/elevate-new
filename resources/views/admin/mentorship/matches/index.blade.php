@extends('layouts.admin')
@section('content')
<div class="card"><h1>Mentor Matching</h1>
<form method="POST" action="{{ route('admin.mentorship.matches.store') }}">@csrf
<label>Mentor</label><select name="mentor_user_id">@foreach($mentors as $mentor)<option value="{{ $mentor->user_id }}">{{ $mentor->user->name }}</option>@endforeach</select>
<label>Mentee</label><select name="mentee_user_id">@foreach($mentees as $mentee)<option value="{{ $mentee->user_id }}">{{ $mentee->user->name }}</option>@endforeach</select>
<label>Start date</label><input type="date" name="start_date">
<button>Create Match</button>
</form></div>
@foreach($matches as $match)
<div class="card">{{ $match->mentor->name }} → {{ $match->mentee->name }} · {{ $match->status }}</div>
@endforeach
{{ $matches->links() }}
@endsection
