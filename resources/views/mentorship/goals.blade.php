@extends('layouts.app')
@section('content')
<div class="card"><h1>Mentorship Goals</h1>
<form method="POST" action="{{ route('mentorship.goals.store',$match) }}">@csrf
<label>Goal title</label><input name="title" required>
<label>Description</label><textarea name="description"></textarea>
<label>Target date</label><input type="date" name="target_date">
<button>Add Goal</button>
</form></div>
@foreach($match->goals as $goal)
<div class="card"><strong>{{ $goal->title }}</strong><br>{{ $goal->progress_percent }}% · {{ $goal->status }}</div>
@endforeach
@endsection
