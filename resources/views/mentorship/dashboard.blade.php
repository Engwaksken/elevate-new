@extends('layouts.app')
@section('content')
<div class="card"><h1>My Mentorship</h1></div>
@if($mentorMatches->count())
<div class="card"><h2>My Mentees</h2>@foreach($mentorMatches as $match)<p>{{ $match->mentee->name }} · {{ $match->status }}</p>@endforeach</div>
@endif
@if($menteeMatches->count())
<div class="card"><h2>My Mentors</h2>@foreach($menteeMatches as $match)<p>{{ $match->mentor->name }} · {{ $match->status }}</p>@endforeach</div>
@endif
@endsection
