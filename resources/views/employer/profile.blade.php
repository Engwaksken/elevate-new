@extends('layouts.app')
@section('content')
<div class="card"><h1>Employer Profile</h1>
<form method="POST" action="{{ route('employer.profile.update') }}">@csrf @method('PUT')
<label>Company name</label><input name="company_name" value="{{ old('company_name',$employer->company_name) }}" required>
<label>Company type</label><input name="company_type" value="{{ old('company_type',$employer->company_type) }}">
<label>Industry</label><input name="industry" value="{{ old('industry',$employer->industry) }}">
<label>Website</label><input name="website" value="{{ old('website',$employer->website) }}">
<label>Contact person</label><input name="contact_person" value="{{ old('contact_person',$employer->contact_person) }}">
<label>Email</label><input type="email" name="email" value="{{ old('email',$employer->email) }}">
<label>Phone</label><input name="phone" value="{{ old('phone',$employer->phone) }}">
<label>Country</label><input name="country" value="{{ old('country',$employer->country) }}">
<label>Location</label><input name="location" value="{{ old('location',$employer->location) }}">
<label>Description</label><textarea name="description">{{ old('description',$employer->description) }}</textarea>
<button>Save Profile</button>
</form></div>
@endsection
