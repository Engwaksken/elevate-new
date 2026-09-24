@extends('layouts.admin')
@section('content')
<div class="card"><h1>Mentor Recommendations for {{ $mentee->user->name }}</h1></div>
@foreach($recommendations as $mentor)
<div class="card"><strong>{{ $mentor->user->name }}</strong><br>
{{ $mentor->job_title }} · {{ $mentor->organisation }}<br>
Matching score: {{ $mentor->recommendation_score }}%
</div>
@endforeach
@endsection
