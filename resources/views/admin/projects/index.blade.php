@extends('layouts.app')
@section('content')
<div class="card"><h1>Projects</h1>
<p><a class="btn" href="{{ route('admin.projects.create') }}">Add Project</a></p>
<p>Controller includes search and pagination. Replace this starter table with the approved ElevateHer360 admin UI during the design pass.</p>
@foreach($projects as $project)<div class="card">{{ $project->name }} <a href="{{ route('admin.projects.edit',$project) }}">Edit</a></div>@endforeach
{{ $projects->links() }}</div>
@endsection
