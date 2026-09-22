@extends('layouts.app')
@section('content')
<div class="card">
<h1>Courses</h1>
<form method="GET" class="grid">
<div><label>Search</label><input name="search" value="{{ request('search') }}"></div>
<div><label>Delivery mode</label><select name="delivery_mode"><option value="">All</option><option value="online">Online</option><option value="in_person">In person</option><option value="blended">Blended</option></select></div>
</form>
</div>
<div class="grid">
@foreach($courses as $course)
<div class="card">
<h3>{{ $course->title }}</h3>
<p>{{ $course->summary }}</p>
<p>{{ ucfirst(str_replace('_',' ',$course->delivery_mode)) }} · {{ $course->modules_count }} modules</p>
<a class="btn" href="{{ route('learning.course.show',$course) }}">View Course</a>
@if($myCourseIds->contains($course->id)) <span>Enrolled</span> @endif
</div>
@endforeach
</div>
{{ $courses->links() }}
@endsection
