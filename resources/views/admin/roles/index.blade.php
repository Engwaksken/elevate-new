@extends('layouts.app')
@section('content')
<div class="card"><h1>Roles & Permissions</h1>
@foreach($roles as $role)
<div class="card"><strong>{{ $role->name }}</strong><br>
{{ $role->users_count }} users · {{ $role->permissions_count }} permissions<br><br>
<a class="btn" href="{{ route('admin.roles.edit',$role) }}">Edit permissions</a></div>
@endforeach
{{ $roles->links() }}
</div>
@endsection
