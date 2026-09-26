@extends('layouts.app')
@section('title',$resource->title.' - ElevateHer360')
@section('content')

<div class="page-header">
<div>
<span class="eh-kicker">Digital Library</span>
<h1>{{ $resource->title }}</h1>
<p>{{ $resource->author ?: 'ElevateHer360 resource' }}</p>
</div>
<div class="page-actions">
<a href="{{ route('library.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Library</a>
</div>
</div>

<div class="library-detail-grid">
<section class="library-detail-main">
@if($resource->cover_image_path)
<div class="library-detail-cover">
<img src="{{ route('library.cover',$resource) }}" alt="{{ $resource->title }}">
</div>
@endif

<div class="eh-tab-section">
@if($resource->description)
<div class="library-description">{!! nl2br(e($resource->description)) !!}</div>
@endif

@if(is_array($resource->tags) && count($resource->tags))
<div class="library-tags">
@foreach($resource->tags as $tag)<span>{{ $tag }}</span>@endforeach
</div>
@endif
</div>
</section>

<aside class="library-detail-side">
<div class="library-info-card">
<div><span>Category</span><strong>{{ $resource->category?->name ?: '—' }}</strong></div>
<div><span>Language</span><strong>{{ $resource->language }}</strong></div>
<div><span>Published</span><strong>{{ optional($resource->publication_date)->format('d M Y') ?: '—' }}</strong></div>
<div><span>Views</span><strong>{{ number_format($resource->views_count) }}</strong></div>
<div><span>Downloads</span><strong>{{ number_format($resource->downloads_count) }}</strong></div>
</div>

<div class="library-action-stack">
@if($resource->external_url)
<a class="btn btn-outline" href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer">
<i class="fas fa-arrow-up-right-from-square"></i> Open External Resource
</a>
@endif

@if($resource->file_path)
<a class="btn btn-primary" href="{{ route('library.download',$resource) }}">
<i class="fas fa-download"></i> Download Resource
</a>
@endif

@if(auth()->check())
<form method="POST" action="{{ route('library.bookmark',$resource) }}">
@csrf
<button class="btn btn-outline" type="submit"><i class="fas fa-bookmark"></i> Bookmark</button>
</form>
@endif
</div>
</aside>
</div>
@endsection
