@extends('layouts.admin')
@section('title','Course Calls | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Programme Delivery</span>
        <h1>Course Calls</h1>
        <p>Create one general call and include multiple active courses.</p>
    </div>

    <button
        type="button"
        class="btn btn-primary"
        data-modal-open="createCourseCall"
    >
        <i class="fas fa-plus"></i>
        New Course Call
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input
                name="search"
                value="{{ request('search') }}"
                placeholder="Search calls or included courses..."
            >
        </div>

        <select name="status">
            <option value="">All statuses</option>
            @foreach(['draft','published','closed','archived'] as $status)
                <option
                    value="{{ $status }}"
                    @selected(request('status') === $status)
                >
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>

        <button class="btn btn-primary btn-sm">Apply</button>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Course Call</th>
                    <th>Included Courses</th>
                    <th>Status</th>
                    <th>Applications</th>
                    <th>Closing Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($calls as $call)
                    <tr>
                        <td>
                            <strong>{{ $call->title }}</strong>
                            <small class="admin-cell-hint">
                                {{ $call->programme?->name ?? '' }}
                            </small>
                        </td>

                        <td>
                            @forelse($call->courses as $course)
                                <span class="admin-chip">{{ $course->title }}</span>
                            @empty
                                <span>—</span>
                            @endforelse
                        </td>

                        <td>{{ ucfirst($call->status) }}</td>

                        <td>{{ number_format($call->applications_count) }}</td>

                        <td>
                            {{ $call->closes_at?->format('d M Y H:i') ?? '—' }}
                        </td>

                        <td>
                            <div class="admin-row-actions">
                                <a
                                    href="{{ route('admin.course-calls.show', $call) }}"
                                    class="btn btn-outline btn-sm"
                                >
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>

                                <a
                                    href="{{ route('admin.course-calls.edit', $call) }}"
                                    class="btn btn-outline btn-sm"
                                >
                                    <i class="fas fa-pen"></i>
                                    Edit
                                </a>

                                <a
                                    href="{{ route('admin.course-calls.applications', $call) }}"
                                    class="btn btn-outline btn-sm"
                                >
                                    <i class="fas fa-file-lines"></i>
                                    Applications
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No Course Calls found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $calls->links() }}
</div>

<div class="eh-modal" id="createCourseCall">
    <div class="eh-modal-dialog eh-modal-lg">
        <div class="eh-modal-header">
            <div>
                <span class="admin-eyebrow">Course Call</span>
                <h2>New General Course Call</h2>
            </div>

            <button
                type="button"
                data-modal-close
                class="eh-modal-close"
            >
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.course-calls.store') }}">
            @csrf

            <div class="eh-modal-body">
                <div class="eh-form-grid">
                    <div class="full">
                        <label>Course Call Title *</label>
                        <input
                            name="title"
                            value="{{ old('title') }}"
                            required
                            placeholder="e.g. Women in Digital Skills Training Call 2026"
                        >
                    </div>

                    <div class="full">
                        <label>Active Courses to Include *</label>

                        <div class="eh-course-multi-select">
                            @forelse($courses as $course)
                                <label class="eh-checkbox-row">
                                    <input
                                        type="checkbox"
                                        name="course_ids[]"
                                        value="{{ $course->id }}"
                                        @checked(in_array(
                                            (string)$course->id,
                                            array_map('strval', old('course_ids', [])),
                                            true
                                        ))
                                    >

                                    <span>
                                        <strong>{{ $course->title }}</strong>
                                        <small>Published / Active</small>
                                    </span>
                                </label>
                            @empty
                                <p>No published courses are available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label>Programme</label>
                        <select name="programme_id">
                            <option value="">Select programme</option>
                            @foreach($programmes as $programme)
                                <option
                                    value="{{ $programme->id }}"
                                    @selected((string)old('programme_id') === (string)$programme->id)
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
                                    @selected((string)old('project_id') === (string)$project->id)
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
                                    @selected((string)old('cohort_id') === (string)$cohort->id)
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
                            value="{{ old('available_slots') }}"
                            placeholder="Number of available slots"
                        >
                    </div>

                    <div class="full">
                        <label>Description</label>
                        <textarea
                            name="description"
                            placeholder="Describe the overall Course Call"
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="full">
                        <label>Eligibility Criteria</label>
                        <textarea
                            name="eligibility_criteria"
                            placeholder="Enter eligibility requirements"
                        >{{ old('eligibility_criteria') }}</textarea>
                    </div>

                    <div>
                        <label>Opens At</label>
                        <input
                            type="datetime-local"
                            name="opens_at"
                            value="{{ old('opens_at') }}"
                        >
                    </div>

                    <div>
                        <label>Closes At</label>
                        <input
                            type="datetime-local"
                            name="closes_at"
                            value="{{ old('closes_at') }}"
                        >
                    </div>

                    <div>
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="eh-modal-footer">
                <button
                    type="button"
                    data-modal-close
                    class="btn btn-outline"
                >
                    Cancel
                </button>

                <button class="btn btn-primary">
                    <i class="fas fa-floppy-disk"></i>
                    Create Course Call
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
