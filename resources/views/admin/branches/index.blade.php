@extends('layouts.app')
@section('content')
<div class="card"><h1>Branches</h1>
<p><a class="btn" href="{{ route('admin.branches.create') }}">Add Branch</a></p>
<p>Controller includes search and pagination. Replace this starter table with the approved ElevateHer360 admin UI during the design pass.</p>
@foreach($branches as $branch)<div class="card">{{ $branch->name }} <a href="{{ route('admin.branches.edit',$branch) }}">Edit</a></div>@endforeach
{{ $branches->links() }}</div>
@endsection
