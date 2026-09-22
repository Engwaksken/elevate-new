@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $programme->exists ? 'Edit' : 'Add' }} Programme</h1>
<form method="POST" action="{{ $programme->exists ? route('admin.programmes.update',$programme) : route('admin.programmes.store') }}">
@csrf @if($programme->exists) @method('PUT') @endif
<label>Name</label><input name="name" value="{{ old('name',$programme->name) }}" required>
<label>Code</label><input name="code" value="{{ old('code',$programme->code) }}">
<label>Description</label><textarea name="description">{{ old('description',$programme->description) }}</textarea>
<div class="grid"><div><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date',optional($programme->start_date)->format('Y-m-d')) }}"></div>
<div><label>End date</label><input type="date" name="end_date" value="{{ old('end_date',optional($programme->end_date)->format('Y-m-d')) }}"></div></div>
<label>Status</label><select name="status">@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status',$programme->status)===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<button>Save</button></form></div>
@endsection
