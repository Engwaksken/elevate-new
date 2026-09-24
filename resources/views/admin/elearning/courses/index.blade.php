@extends('layouts.admin')
@section('content')
<div class="card">
<h1>Courses</h1>
<form method="GET"><input name="search" placeholder="Search courses" value="{{ request('search') }}"><button>Search</button></form>
<p><a class="btn" href="{{ route('admin.elearning.courses.create') }}">Add Course</a></p>
@foreach($courses as $course)
<div class="card">
<strong>{{ $course->title }}</strong> ({{ $course->code }})<br>
{{ $course->delivery_mode }} · {{ $course->status }}
<br><a href="{{ route('admin.elearning.courses.edit',$course) }}">Edit</a>
</div>
@endforeach
{{ $courses->links() }}
</div>
@endsection
