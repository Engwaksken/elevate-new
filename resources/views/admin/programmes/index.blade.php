@extends('layouts.app')
@section('content')
<div class="card">
<h1>Programmes</h1>
<form method="GET"><input name="search" placeholder="Search programmes" value="{{ request('search') }}"><button>Search</button></form>
<p><a class="btn" href="{{ route('admin.programmes.create') }}">Add Programme</a></p>
<table width="100%" cellpadding="8"><tr><th align="left">Name</th><th>Code</th><th>Status</th><th>Actions</th></tr>
@foreach($programmes as $programme)
<tr><td>{{ $programme->name }}</td><td align="center">{{ $programme->code }}</td><td align="center">{{ $programme->status }}</td>
<td align="center"><a href="{{ route('admin.programmes.edit',$programme) }}">Edit</a></td></tr>
@endforeach
</table>{{ $programmes->links() }}
</div>
@endsection
