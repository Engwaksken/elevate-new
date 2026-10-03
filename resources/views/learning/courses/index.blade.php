@extends('layouts.app')
@section('title', app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses').' | ElevateHer360')
@section('meta_description', app(\App\Services\CmsContentService::class)->text('learning', 'summary', 'Explore our learning courses.'))
@section('content')
<div class="card">
<h1>{{ app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses') }}</h1>
<p>{{ app(\App\Services\CmsContentService::class)->text('learning', 'summary') }}</p>
@include('partials.cms-intro', ['slug' => 'learning'])
</div>
<style>
#courses-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
@media (max-width: 1024px){#courses-grid{grid-template-columns:repeat(2,1fr)!important}}
@media (max-width: 640px){#courses-grid{grid-template-columns:repeat(1,1fr)!important}}
</style>
<div id="courses-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px">
@forelse($courses as $course)
<article class="card">
<a href="{{ route('learning.course.show',$course) }}" style="display:block;text-decoration:none;color:inherit"><img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" loading="lazy" style="width:100%;height:200px;object-fit:cover;border-radius:12px"><h2>{{ $course->title }}</h2></a>
<p>{{ $course->briefDescription() }}</p>
</article>
@empty<p>No courses found.</p>@endforelse
</div>
{{ $courses->links() }}
@endsection
