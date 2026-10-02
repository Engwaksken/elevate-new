@extends('layouts.admin')
@section('title', ucfirst($type).' Details | Administration')
@section('content')
@php($prefix = $type === 'mentor' ? 'admin.mentorship.mentors.' : 'admin.jobs.employers.')
<div class="admin-page-header"><div><h1>{{ $profile->exists ? 'Edit' : 'Add' }} {{ ucfirst($type) }}</h1><p>Enter details directly or link a participant account. New accounts can set a password using the password-reset page.</p></div></div>
<div class="admin-panel">
<form method="POST" action="{{ $profile->exists ? route($prefix.'update', $profile) : route($prefix.'store') }}">@csrf @if($profile->exists) @method('PUT') @endif
@unless($profile->exists)<div class="form-group"><label>Link existing account (optional)</label><select name="user_id"><option value="">Create a new account using the contact details below</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></div>@endunless
@include('partials.partner-fields')
<div class="form-group"><label>Status</label><select name="status">@foreach(['approved','pending','rejected'] as $status)<option value="{{ $status }}" @selected(old('status', $profile->status ?? 'approved') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
<button class="btn btn-primary">Save details</button><a class="btn btn-outline" href="{{ route($prefix.'index') }}">Cancel</a>
</form></div>
@endsection
