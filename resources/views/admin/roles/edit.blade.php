@extends('layouts.admin')
@section('content')
<div class="card"><h1>{{ $role->name }}</h1>
<form method="POST" action="{{ route('admin.roles.update',$role) }}">@csrf @method('PUT')
<input type="hidden" name="name" value="{{ $role->name }}">
@foreach($permissions as $module => $items)
<div class="card"><h3>{{ ucfirst($module ?: 'General') }}</h3>
@foreach($items as $permission)
<label style="display:block;margin:8px 0">
<input style="width:auto" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission->id))>
{{ $permission->name }}
</label>
@endforeach
</div>
@endforeach
<button>Save Permissions</button>
</form></div>
@endsection
