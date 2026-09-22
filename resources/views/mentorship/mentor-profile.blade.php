@extends('layouts.app')
@section('content')
<div class="card"><h1>Mentor Profile</h1>
<form method="POST" action="{{ route('mentorship.mentor-profile.update') }}">@csrf @method('PUT')
<label>Organisation</label><input name="organisation" value="{{ old('organisation',$profile->organisation) }}">
<label>Job title</label><input name="job_title" value="{{ old('job_title',$profile->job_title) }}">
<label>Industry</label><input name="industry" value="{{ old('industry',$profile->industry) }}">
<label>Years of experience</label><input type="number" name="years_experience" value="{{ old('years_experience',$profile->years_experience) }}">
<label>Professional bio</label><textarea name="professional_bio">{{ old('professional_bio',$profile->professional_bio) }}</textarea>
<label>Skills (comma-separated)</label><input name="skills_text" value="{{ implode(', ',$profile->skills ?? []) }}">
<label>Languages</label><input name="languages_text" value="{{ implode(', ',$profile->languages ?? []) }}">
<label>Mentoring areas</label><input name="mentoring_areas_text" value="{{ implode(', ',$profile->mentoring_areas ?? []) }}">
<label>LinkedIn</label><input name="linkedin_url" value="{{ $profile->linkedin_url }}">
<label>Country</label><input name="country" value="{{ $profile->country }}">
<button>Submit Profile</button>
</form></div>
@endsection
