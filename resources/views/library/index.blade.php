@extends('layouts.app')
@section('content')
<div class="card"><h1>Digital Library</h1>
<form method="GET" class="grid">
<div><label>Search</label><input name="search" value="{{ request('search') }}"></div>
<div><label>Category</label><select name="category"><option value="">All</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
</form></div>
<div class="grid">
@foreach($resources as $resource)
<div class="card"><h3>{{ $resource->title }}</h3><p>{{ $resource->author }}</p><a class="btn" href="{{ route('library.show',$resource) }}">View</a></div>
@endforeach
</div>
{{ $resources->links() }}
@endsection
