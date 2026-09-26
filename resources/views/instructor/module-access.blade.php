@extends('layouts.admin')
@section('title','Cohort Module Access | ElevateHer360')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Instructor Controls</span><h1>Module Access — {{ $course->title }}</h1><p>Require participants to complete each module before the next opens, with cohort-level instructor locks.</p></div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<label>Cohort</label>
<select name="cohort_id" onchange="this.form.submit()">
<option value="">Select cohort</option>
@foreach($course->cohorts as $cohort)<option value="{{ $cohort->id }}" @selected((int)$cohortId===(int)$cohort->id)>{{ $cohort->name }}</option>@endforeach
</select>
</form>
</div>

@if($cohortId && $setting)
<div class="admin-panel">
<div class="admin-panel-head"><div><h2>Sequential Learning Rules</h2><p>These rules apply only to the selected cohort.</p></div></div>
<form method="POST" action="{{ route('instructor.module-access.settings',$course) }}">@csrf
<input type="hidden" name="cohort_id" value="{{ $cohortId }}">
<div class="module-rule-grid">
<label class="eh-choice-card"><input type="checkbox" name="sequential_modules" value="1" @checked($setting->sequential_modules)><span class="eh-choice-card__text">Require previous module completion before opening the next module</span></label>
<label class="eh-choice-card"><input type="checkbox" name="instructor_release_required" value="1" @checked($setting->instructor_release_required)><span class="eh-choice-card__text">Also require instructor release for later modules</span></label>
</div>
<div class="eh-form-actions"><button class="btn btn-primary">Save Rules</button></div>
</form>
</div>

<div class="admin-panel">
<div class="admin-panel-head"><div><h2>Module Locks</h2><p>First module is available by default. Later modules can be released or locked for this cohort.</p></div></div>
<div class="module-access-grid">
@foreach($course->modules->sortBy('position') as $module)
@php($release=$releases->get($module->id); $released=(bool)($release?->is_released))
<article class="module-access-card">
<div><span class="module-number">{{ $loop->iteration }}</span><strong>{{ $module->title }}</strong><small>{{ $module->lessons->count() }} lesson(s)</small></div>
@if($loop->first)
<span class="status-chip active">First module</span>
@else
<form method="POST" action="{{ route('instructor.module-access.release',[$course,$module]) }}">@csrf
<input type="hidden" name="cohort_id" value="{{ $cohortId }}">
<input type="hidden" name="is_released" value="{{ $released ? 0 : 1 }}">
<button class="btn {{ $released ? 'btn-outline':'btn-primary' }} btn-sm"><i class="fas {{ $released ? 'fa-lock':'fa-lock-open' }}"></i> {{ $released ? 'Lock':'Release' }}</button>
</form>
@endif
</article>
@endforeach
</div>
</div>
@endif
@endsection
