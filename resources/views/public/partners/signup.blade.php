@extends('layouts.app')
@section('title', ($content['title'] ?? ($type === 'mentor' ? 'Become a Mentor' : 'Register an Employer')).' | ElevateHer360')
@section('meta_description', $content['summary'] ?? '')
@section('content')
@php($fieldLabels = data_get($content, 'settings.field_labels', []))
<div class="page-header"><div><h1>{{ $content['title'] ?? ($type === 'mentor' ? 'Become a Mentor' : 'Register an Employer') }}</h1><p>{{ $content['summary'] ?? 'Submit your details for administrator review.' }}</p></div></div>
@include('partials.cms-intro', ['slug' => $type.'-signup'])
@if(data_get($content, 'settings.signup_open', true))
<div class="card"><form method="POST" action="{{ route('public.partners.'.$type.'.store') }}">@csrf
@include('partials.partner-fields')
<div class="form-grid"><div class="form-group"><label>Password</label><input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></div><div class="form-group"><label>Confirm password</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div></div>
<label class="modal-check"><input type="checkbox" name="consent" value="1" required><span>I agree to the <a href="{{ route('legal.privacy') }}">Privacy Policy</a> and <a href="{{ route('legal.terms') }}">Terms of Use</a>.</span></label>
<button class="btn btn-primary">{{ data_get($content, 'settings.button_label') ?: 'Submit registration' }}</button>
</form></div>
@else<div class="info-box">Registrations are currently closed.</div>@endif
@endsection
