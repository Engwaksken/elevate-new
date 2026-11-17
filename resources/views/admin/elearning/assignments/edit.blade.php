@extends('layouts.admin')
@section('title','Edit Course Assignment | ElevateHer360 Administration')
@section('content')
@php
    $selectedInstructors = $course->instructors->pluck('id')->all();
    $selectedCohorts = $course->cohorts->pluck('id')->all();
    $leadId = optional($course->instructors->firstWhere('pivot.is_lead', true))->id;
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Course Assignment</span>
        <h1>{{ $course->title }}</h1>
        <p>Choose instructors, lead instructor and cohorts for this course.</p>
    </div>
    <div class="admin-page-actions">
        <a href="{{ route('admin.elearning.assignments.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Assignments</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form method="POST" action="{{ route('admin.elearning.assignments.update', $course) }}">
    @csrf @method('PUT')

    {{-- Instructors --}}
    <details class="ag-section" open>
        <summary>
            <h2><i class="fas fa-chalkboard-user"></i> Instructors</h2>
            <span class="ag-count"><span data-ag-selected>{{ count($selectedInstructors) }}</span> of {{ $instructors->count() }} selected</span>
        </summary>
        <div class="ag-section-body" data-ag-scope>
            @if($instructors->isEmpty())
                <p class="admin-cell-hint">No staff hold the instructor or trainer role yet.</p>
            @else
            <div class="ag-section-tools">
                @if($instructors->count() > 8)
                    <input type="search" class="ag-filter" placeholder="Filter instructors by name or branch" data-ag-filter aria-label="Filter instructors">
                @endif
                <button type="button" class="ag-toolbar-btn" data-ag-only-selected>Show selected only</button>
            </div>
            <div class="eh-choice-grid ag-grid">
                @foreach($instructors as $instructor)
                    @php $branches = $instructor->branches->pluck('name')->join(', '); @endphp
                    <label class="eh-choice-card" data-ag-item data-search="{{ mb_strtolower($instructor->name.' '.$branches) }}" title="{{ $instructor->name }}{{ $branches ? ' · '.$branches : '' }}">
                        <input type="checkbox" name="instructors[]" value="{{ $instructor->id }}" @checked(in_array($instructor->id, $selectedInstructors))>
                        <span class="eh-choice-card__text">{{ $instructor->name }}
                            <small class="admin-cell-hint">{{ $branches ? $branches.' · ' : '' }}{{ $instructor->instructed_courses_count }} {{ \Illuminate\Support\Str::plural('course', $instructor->instructed_courses_count) }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="ag-empty-filter" data-ag-no-match hidden>No instructors match.</p>
            @endif

            <div class="assignment-section" style="margin-top:14px;max-width:420px">
                <label for="lead-instructor">Lead Instructor</label>
                <select id="lead-instructor" name="lead_instructor_id">
                    <option value="">No lead instructor</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected($leadId === $instructor->id)>{{ $instructor->name }}</option>
                    @endforeach
                </select>
                <small class="form-hint">The selected lead instructor should also be ticked above.</small>
            </div>
        </div>
    </details>

    {{-- Cohorts --}}
    <details class="ag-section" open>
        <summary>
            <h2><i class="fas fa-people-group"></i> Cohorts</h2>
            <span class="ag-count"><span data-ag-selected>{{ count($selectedCohorts) }}</span> of {{ $cohorts->count() }} selected</span>
        </summary>
        <div class="ag-section-body" data-ag-scope>
            @if($cohorts->isEmpty())
                <p class="admin-cell-hint">No cohorts have been created yet.</p>
            @else
            <div class="ag-section-tools">
                @if($cohorts->count() > 8)
                    <input type="search" class="ag-filter" placeholder="Filter cohorts" data-ag-filter aria-label="Filter cohorts">
                @endif
                <button type="button" class="ag-toolbar-btn" data-ag-only-selected>Show selected only</button>
            </div>
            <div class="eh-choice-grid ag-grid">
                @foreach($cohorts as $cohort)
                    <label class="eh-choice-card" data-ag-item data-search="{{ mb_strtolower($cohort->name) }}" title="{{ $cohort->name }}">
                        <input type="checkbox" name="cohorts[]" value="{{ $cohort->id }}" @checked(in_array($cohort->id, $selectedCohorts))>
                        <span class="eh-choice-card__text">{{ $cohort->name }}</span>
                    </label>
                @endforeach
            </div>
            <p class="ag-empty-filter" data-ag-no-match hidden>No cohorts match.</p>
            @endif
        </div>
    </details>

    <div class="eh-form-actions"><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Assignments</button></div>
</form>
@endsection
