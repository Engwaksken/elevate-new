@extends('layouts.admin')
@section('content')
<div class="card"><h1>Results Frameworks</h1>
<form method="POST" action="{{ route('admin.results-framework.store') }}">@csrf
<label>Title</label><input name="title" required>
<label>Description</label><textarea name="description"></textarea>
<button>Create Framework</button></form></div>
@foreach($frameworks as $framework)
<div class="card"><strong>{{ $framework->title }}</strong><br>{{ $framework->results->count() }} results</div>
@endforeach
{{ $frameworks->links() }}
@endsection
