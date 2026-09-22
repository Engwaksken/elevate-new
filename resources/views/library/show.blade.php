@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $resource->title }}</h1>
<p>{{ $resource->author }}</p><p>{{ $resource->description }}</p>
@if($resource->external_url)<p><a href="{{ $resource->external_url }}" target="_blank">Open External Resource</a></p>@endif
@if($resource->file_path)<p><a class="btn" href="{{ route('library.download',$resource) }}">Download</a></p>@endif
@if(auth()->check())<form method="POST" action="{{ route('library.bookmark',$resource) }}">@csrf<button>Bookmark</button></form>@endif
</div>
@endsection
