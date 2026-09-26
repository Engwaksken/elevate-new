@extends('layouts.app')
@section('title','Digital Library - ElevateHer360')
@section('content')

<div class="page-header">
<div>
    <span class="eh-kicker">Resources</span>
    <h1>Digital Library</h1>
    <p>Browse learning materials, guides and career resources.</p>
</div>
</div>

<div class="eh-tabs" data-eh-tabs>
<div class="eh-tab-nav">
<button class="eh-tab-button active" data-eh-tab="resources"><i class="fas fa-book-open"></i> Resources</button>
<button class="eh-tab-button" data-eh-tab="categories"><i class="fas fa-layer-group"></i> Categories</button>
</div>

<div class="eh-tab-content">
<section class="eh-tab-pane active" data-eh-pane="resources">
<div class="eh-tab-section">

<form method="GET" class="eh-filter-bar">
<div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Title, author or keyword"></div>

<div><label>Category</label>
<select name="category">
<option value="">All categories</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected((string)request('category')===(string)$category->id)>{{ $category->name }}</option>
@endforeach
</select>
</div>

<div><label>Language</label>
<select name="language">
<option value="">All languages</option>
@foreach($languages as $language)
<option value="{{ $language }}" @selected(request('language')===$language)>{{ $language }}</option>
@endforeach
</select>
</div>

<div class="eh-filter-action"><button class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button></div>
</form>

<div class="library-card-grid">
@forelse($resources as $resource)
<a class="library-card" href="{{ route('library.show',$resource) }}">
<div class="library-card-cover">
@if($resource->cover_image_path)
<img src="{{ route('library.cover',$resource) }}" alt="{{ $resource->title }}">
@else
<div class="library-card-placeholder"><i class="fas fa-book-open"></i></div>
@endif
<span class="library-access-badge">{{ ucfirst($resource->access_level) }}</span>
</div>

<div class="library-card-body">
<small>{{ $resource->category?->name ?: 'Resource' }}</small>
<h3>{{ $resource->title }}</h3>
<p>{{ $resource->author ?: 'ElevateHer360 resource' }}</p>

<div class="library-card-meta">
<span><i class="fas fa-language"></i> {{ $resource->language }}</span>
<span><i class="fas fa-eye"></i> {{ number_format($resource->views_count) }}</span>
</div>
</div>
</a>
@empty
<div class="eh-empty library-empty"><i class="fas fa-book-open"></i><h3>No resources found</h3><p>Try another search, language or category.</p></div>
@endforelse
</div>

<div class="admin-pagination">{{ $resources->links() }}</div>
</div>
</section>

<section class="eh-tab-pane" data-eh-pane="categories">
<div class="eh-tab-section">
<div class="library-category-grid">
@forelse($categories as $category)
<a class="library-category-card" href="{{ route('library.index',['category'=>$category->id]) }}">
<i class="fas fa-folder-open"></i>
<strong>{{ $category->name }}</strong>
<span>Browse resources</span>
</a>
@empty
<div class="eh-empty"><p>No categories available.</p></div>
@endforelse
</div>
</div>
</section>
</div>
</div>
@endsection
