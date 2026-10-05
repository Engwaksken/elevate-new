@extends('layouts.admin')
@section('title','Bulk Enrolment | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Programme Delivery</span>
        <h1>Bulk Enrolment</h1>
        <p>Download the full CSV template, populate enrolment data and upload it.</p>
    </div>

    <div class="admin-page-actions">
        <a
            href="{{ route('admin.elearning.bulk-enrolment.template') }}"
            class="btn btn-outline"
        >
            <i class="fas fa-file-csv"></i>
            Download Full CSV Template
        </a>
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach([
        ['courses','Courses','fa-graduation-cap'],
        ['participants','Participants','fa-users'],
        ['enrolments','Enrolments','fa-user-check'],
        ['completed','Completed','fa-circle-check']
    ] as [$key,$label,$icon])
        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
            <div>
                <small>{{ $label }}</small>
                <strong>{{ number_format($stats[$key] ?? 0) }}</strong>
            </div>
        </div>
    @endforeach
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('bulk_import_errors'))
    <div class="alert alert-error">
        <strong>Rows requiring attention:</strong>
        @foreach(session('bulk_import_errors') as $importError)
            <div>{{ $importError }}</div>
        @endforeach
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Upload Enrolment CSV</h2>
            <p>CSV row values override the optional default Course and Cohort selected below.</p>
        </div>

        <a
            href="{{ route('admin.elearning.bulk-enrolment.template') }}"
            class="btn btn-outline btn-sm"
        >
            <i class="fas fa-download"></i>
            CSV Template
        </a>
    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ route('admin.elearning.bulk-enrolment.store') }}"
    >
        @csrf

        <div class="eh-form-grid">
            <div>
                <label>Default Course</label>
                <select name="course_id">
                    <option value="">Use course_id from CSV</option>

                    @foreach($courses as $course)
                        <option
                            value="{{ $course->id }}"
                            @selected((string)old('course_id') === (string)$course->id)
                        >
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Default Cohort</label>
                <select name="cohort_id">
                    <option value="">Use cohort_id from CSV / no cohort</option>

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

            <div class="full">
                <label>CSV File *</label>
                <input
                    type="file"
                    name="file"
                    accept=".csv,.txt,text/csv,text/plain"
                    required
                >
            </div>
        </div>

        <div class="bulk-guidance">
            <div>
                <i class="fas fa-table-columns"></i>
                <strong>Full Template</strong>
                <span>Contains all enrolment table columns plus email or participant ID (participant_code) for participant lookup</span>
            </div>

            <div>
                <i class="fas fa-id-card"></i>
                <strong>Participant</strong>
                <span>Provide user_id or email</span>
            </div>

            <div>
                <i class="fas fa-graduation-cap"></i>
                <strong>Course</strong>
                <span>Provide course_id in CSV or select a default Course</span>
            </div>

            <div>
                <i class="fas fa-database"></i>
                <strong>System Fields</strong>
                <span>id, created_at and updated_at are visible in the template but safely ignored during import</span>
            </div>
        </div>

        <div class="admin-table-wrap" style="margin-top:16px;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>CSV Column</th>
                        <th>Purpose</th>
                        <th>Import Behaviour</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>id</td><td>Database primary key</td><td>Ignored / system managed</td></tr>
                    <tr><td>course_id</td><td>Course ID</td><td>Required unless a default Course is selected</td></tr>
                    <tr><td>user_id</td><td>Participant User ID</td><td>Use user_id or email</td></tr>
                    <tr><td>email</td><td>Participant email helper</td><td>Use user_id or email</td></tr>
                    <tr><td>cohort_id</td><td>Cohort ID</td><td>Optional</td></tr>
                    <tr><td>status</td><td>Enrolment status</td><td>enrolled, in_progress, completed, withdrawn or failed</td></tr>
                    <tr><td>enrolled_at</td><td>Enrolment date/time</td><td>Optional; defaults to current time</td></tr>
                    <tr><td>started_at</td><td>Start date/time</td><td>Optional</td></tr>
                    <tr><td>completed_at</td><td>Completion date/time</td><td>Optional</td></tr>
                    <tr><td>progress_percent</td><td>Progress</td><td>0–100</td></tr>
                    <tr><td>final_score</td><td>Final score</td><td>Optional; 0–100</td></tr>
                    <tr><td>created_at</td><td>Audit timestamp</td><td>Ignored / system managed</td></tr>
                    <tr><td>updated_at</td><td>Audit timestamp</td><td>Ignored / system managed</td></tr>
                </tbody>
            </table>
        </div>

        <div class="eh-form-actions">
            <button class="btn btn-primary">
                <i class="fas fa-file-import"></i>
                Upload & Process CSV
            </button>

            <a
                href="{{ route('admin.elearning.bulk-enrolment.template') }}"
                class="btn btn-outline"
            >
                <i class="fas fa-file-csv"></i>
                Download Full Template
            </a>
        </div>
    </form>
</div>

<div class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Enroll selected participants</h2>
            <p>Search for participants, tick the ones to enroll, and choose a course (and optional cohort).</p>
        </div>
    </div>

    <form method="GET" class="admin-toolbar" action="{{ route('admin.elearning.bulk-enrolment.create') }}">
        <div class="search-box">
            <i class="fas fa-magnifying-glass"></i>
            <input name="p_search" value="{{ request('p_search') }}" placeholder="Search by name, email or participant code...">
        </div>
        <select name="per_page">
            @foreach([25, 50, 100] as $size)
                <option value="{{ $size }}" @selected((int)request('per_page', 25) === $size)>{{ $size }} per page</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm">Search</button>
        <a href="{{ route('admin.elearning.bulk-enrolment.create') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <form method="POST" action="{{ route('admin.elearning.bulk-enrolment.enroll-selected') }}" id="enroll-selected-form">
        @csrf

        <div class="eh-form-grid">
            <div>
                <label>Course *</label>
                <select name="course_id" required>
                    <option value="">Select a course</option>

                    @foreach($courses as $course)
                        <option
                            value="{{ $course->id }}"
                            @selected((string)old('course_id') === (string)$course->id)
                        >
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Cohort</label>
                <select name="cohort_id">
                    <option value="">No cohort</option>

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
        </div>

        <div class="admin-bulk-bar" id="enroll-bulk-bar">
            <strong><span data-selected-count>0</span> selected</strong>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-user-check"></i>
                Enroll selected participants
            </button>
        </div>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table" id="enrollTable">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#enroll-bulk-bar" aria-label="Select all participants"></th>
                    <th>Participant</th>
                    <th>Email</th>
                    <th>Participant Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $participant)
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                value="{{ $participant->id }}"
                                data-row-select
                                aria-label="Select {{ $participant->name }}"
                            >
                        </td>
                        <td><strong>{{ $participant->name }}</strong></td>
                        <td>{{ $participant->email }}</td>
                        <td>{{ $participant->participant_code ?? '—' }}</td>
                        <td>{{ ucfirst($participant->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-empty">
                                <i class="fas fa-users"></i>
                                <strong>No participants found</strong>
                                <span>Try adjusting your search.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $participants->links() }}</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('enroll-selected-form');
    if (!form) return;

    form.addEventListener('submit', function () {
        form.querySelectorAll('input[name="user_ids[]"]').forEach(function (input) { input.remove(); });
        document.querySelectorAll('#enrollTable [data-row-select]:checked').forEach(function (box) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = box.value;
            form.appendChild(input);
        });
    });
});
</script>
@endsection
