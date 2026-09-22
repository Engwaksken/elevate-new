@extends('layouts.app')
@section('content')
<div class="card"><h1>Library Resources</h1>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.library.store') }}">@csrf
<label>Title</label><input name="title" required>
<label>Author</label><input name="author">
<label>Category</label><select name="library_category_id"><option value="">None</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
<label>Description</label><textarea name="description"></textarea>
<label>Tags</label><input name="tags_text">
<label>External URL</label><input name="external_url">
<label>Language</label><input name="language" value="English">
<label>Access</label><select name="access_level"><option value="authenticated">Authenticated</option><option value="public">Public</option><option value="staff">Staff</option></select>
<label>File</label><input type="file" name="file">
<button>Create Resource</button>
</form></div>
@foreach($resources as $resource)<div class="card">{{ $resource->title }} · {{ $resource->category?->name }} · {{ $resource->access_level }}</div>@endforeach
{{ $resources->links() }}
@endsection
