@extends('layouts.app')
@section('content')
<div class="card"><h1>{{ $resume->exists ? 'Edit Resume' : 'Create Resume' }}</h1>
<form method="POST" action="{{ $resume->exists ? route('career.resume.update',$resume) : route('career.resume.store') }}">@csrf @if($resume->exists) @method('PUT') @endif
<label>Title</label><input name="title" value="{{ old('title',$resume->title) }}" required>
<label>Template</label><select name="template"><option value="classic">Classic</option><option value="modern">Modern</option><option value="minimal">Minimal</option></select>
<label>Professional Summary</label><textarea name="professional_summary">{{ old('professional_summary',$resume->professional_summary) }}</textarea>
<button>Save</button></form></div>

@if($resume->exists)
<div class="card"><h2>Add Experience</h2>
<form method="POST" action="{{ route('career.resume.experience.store',$resume) }}">@csrf
<label>Job title</label><input name="job_title" required>
<label>Organisation</label><input name="organisation" required>
<label>Description</label><textarea name="description"></textarea>
<button>Add Experience</button></form></div>

<div class="card"><h2>Add Education</h2>
<form method="POST" action="{{ route('career.resume.education.store',$resume) }}">@csrf
<label>Institution</label><input name="institution" required>
<label>Qualification</label><input name="qualification" required>
<button>Add Education</button></form></div>

<div class="card"><h2>Add Skill</h2>
<form method="POST" action="{{ route('career.resume.skill.store',$resume) }}">@csrf
<label>Skill</label><input name="skill" required>
<label>Level</label><input name="level">
<button>Add Skill</button></form></div>
@endif
@endsection
