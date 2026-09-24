@extends('layouts.admin')
@section('content')
<div class="card"><h1>Tasks</h1></div>
@foreach($tasks as $task)
<div class="card"><strong>{{ $task->title }}</strong><br>{{ $task->status }} · {{ $task->progress_percent }}%</div>
@endforeach
{{ $tasks->links() }}
@endsection
