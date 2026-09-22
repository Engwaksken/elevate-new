@extends('layouts.app')
@section('content')
<div class="card">
<p><a href="{{ route('learning.course.show',$course) }}">← Back to course</a></p>
<h1>{{ $lesson->title }}</h1>
@if($lesson->video_url)<p><a href="{{ $lesson->video_url }}" target="_blank">Open video</a></p>@endif
@if($lesson->external_url)<p><a href="{{ $lesson->external_url }}" target="_blank">Open resource</a></p>@endif
{!! nl2br(e($lesson->content)) !!}
<hr>
<form method="POST" action="{{ route('learning.lesson.complete',$lesson) }}">@csrf<button>Mark Complete</button></form>
</div>
@endsection
