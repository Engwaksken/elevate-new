@extends('layouts.app')
@section('content')
<div class="card"><h1>Gradebook — {{ $course->title }}</h1>
<table width="100%" cellpadding="8"><tr><th>Assessment</th><th>Learner ID</th><th>Status</th><th>Score</th><th></th></tr>
@foreach($attempts as $attempt)
<tr><td>{{ $attempt->assessment->title }}</td><td>{{ $attempt->user_id }}</td><td>{{ $attempt->status }}</td><td>{{ $attempt->percentage }}</td><td><a href="{{ route('admin.elearning.gradebook.edit',$attempt) }}">Grade</a></td></tr>
@endforeach
</table>
{{ $attempts->links() }}
</div>
@endsection
