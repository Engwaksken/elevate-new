@extends('layouts.app')
@section('title', app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses').' | ElevateHer360')
@section('meta_description', app(\App\Services\CmsContentService::class)->text('learning', 'summary', 'Explore our learning courses.'))
@section('content')
<div class="card">
<h1>{{ app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses') }}</h1>
<p>{{ app(\App\Services\CmsContentService::class)->text('learning', 'summary') }}</p>
@include('partials.cms-intro', ['slug' => 'learning'])
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
<p>{{ $course->branches->pluck('name')->join(', ') }}</p>
<p>{{ ucfirst(str_replace('_',' ',$course->delivery_mode)) }} · {{ $course->modules_count }} modules</p>
<a class="btn" href="{{ route('learning.course.show',$course) }}">View Course</a>
@if($myCourseIds->contains($course->id)) <span>Enrolled</span> @endif
</div>
@endforeach
</div>
{{ $courses->links() }}
@endsection
