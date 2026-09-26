@extends('layouts.admin')
@section('title','Edit Course Call | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Course Call</span>
        <h1>Edit Course Call</h1>
        <p>{{ $courseCall->title }}</p>
    </div>

    <div class="admin-page-actions">
        <a
            href="{{ route('admin.course-calls.show', $courseCall) }}"
            class="btn btn-outline"
        >
            View Course Call
        </a>

        <a
            href="{{ route('admin.course-calls.index') }}"
            class="btn btn-outline"
        >
            Back
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="admin-panel">
<form
    method="POST"
    action="{{ route('admin.course-calls.update', $courseCall) }}"
>
@csrf
@method('PUT')

@php
    $selectedCourseIds = array_map(
        'strval',
        old(
            'course_ids',
            $courseCall->courses->pluck('id')->all()
        )
    );
@endphp

<div class="eh-form-grid">
    <div class="full">
        <label>Course Call Title *</label>
        <input
            name="title"
            required
            value="{{ old('title', $courseCall->title) }}"
            placeholder="Course Call title"
        >
    </div>

    <div class="full">
        <label>Active Courses to Include *</label>

        <div class="eh-course-multi-select">
            @foreach($courses as $course)
                <label class="eh-checkbox-row">
                    <input
                        type="checkbox"
                        name="course_ids[]"
                        value="{{ $course->id }}"
                        @checked(in_array((string)$course->id, $selectedCourseIds, true))
                    >

                    <span>
                        <strong>{{ $course->title }}</strong>
                        <small>{{ ucfirst($course->status) }}</small>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label>Programme</label>
        <select name="programme_id">
            <option value="">Select programme</option>
            @foreach($programmes as $programme)
                <option
                    value="{{ $programme->id }}"
                    @selected((string)old('programme_id',$courseCall->programme_id) === (string)$programme->id)
                >
                    {{ $programme->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Project</label>
        <select name="project_id">
            <option value="">Select project</option>
            @foreach($projects as $project)
                <option
                    value="{{ $project->id }}"
                    @selected((string)old('project_id',$courseCall->project_id) === (string)$project->id)
                >
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Cohort</label>
        <select name="cohort_id">
            <option value="">Select cohort</option>
            @foreach($cohorts as $cohort)
                <option
                    value="{{ $cohort->id }}"
                    @selected((string)old('cohort_id',$courseCall->cohort_id) === (string)$cohort->id)
                >
                    {{ $cohort->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Available Slots</label>
        <input
            type="number"
            min="1"
            name="available_slots"
            value="{{ old('available_slots',$courseCall->available_slots) }}"
            placeholder="Available slots"
        >
    </div>

    <div class="full">
        <label>Description</label>
        <textarea
            name="description"
            placeholder="Describe the Course Call"
        >{{ old('description',$courseCall->description) }}</textarea>
    </div>

    <div class="full">
        <label>Eligibility Criteria</label>
        <textarea
            name="eligibility_criteria"
            placeholder="Eligibility requirements"
        >{{ old('eligibility_criteria',$courseCall->eligibility_criteria) }}</textarea>
    </div>

    <div>
        <label>Opens At</label>
        <input
            type="datetime-local"
            name="opens_at"
            value="{{ old('opens_at', optional($courseCall->opens_at)->format('Y-m-d\TH:i')) }}"
        >
    </div>

    <div>
        <label>Closes At</label>
        <input
            type="datetime-local"
            name="closes_at"
            value="{{ old('closes_at', optional($courseCall->closes_at)->format('Y-m-d\TH:i')) }}"
        >
    </div>

    <div>
        <label>Status</label>
        <select name="status">
            @foreach(['draft','published','closed','archived'] as $status)
                <option
                    value="{{ $status }}"
                    @selected(old('status',$courseCall->status) === $status)
                >
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="eh-form-actions">
    <button class="btn btn-primary">
        <i class="fas fa-floppy-disk"></i>
        Save Changes
    </button>

    <a
        href="{{ route('admin.course-calls.show', $courseCall) }}"
        class="btn btn-outline"
    >
        Cancel
    </a>
</div>
</form>
</div>
@endsection
