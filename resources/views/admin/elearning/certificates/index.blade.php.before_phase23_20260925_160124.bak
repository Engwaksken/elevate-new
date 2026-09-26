@extends('layouts.admin')

@section('title','Certificates | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Programme Delivery</span>
        <h1>Course Certificates</h1>
        <p>Manage issued learner certificates and generate certificate PDFs.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="admin-stats-grid compact">
    @foreach([
        ['total','Total Certificates','fa-certificate'],
        ['generated','Generated PDFs','fa-file-pdf'],
        ['pending','Pending PDFs','fa-clock'],
        ['courses','Courses','fa-graduation-cap'],
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

<div class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search certificate number, learner or course..."
            >
        </div>

        <select name="course_id">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string)request('course_id')===(string)$course->id)>
                    {{ $course->title }}
                </option>
            @endforeach
        </select>

        <select name="status">
            <option value="">All statuses</option>
            <option value="generated" @selected(request('status')==='generated')>Generated</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
        </select>

        <button class="btn btn-primary btn-sm">
            <i class="fas fa-filter"></i>
            Apply
        </button>

        <a href="{{ route('admin.elearning.certificates.index') }}" class="btn btn-outline btn-sm">
            Reset
        </a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Certificate</th>
                    <th>Learner</th>
                    <th>Course</th>
                    <th>Issued</th>
                    <th>Status</th>
                    <th class="table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $certificate)
                    <tr>
                        <td><strong>{{ $certificate->certificate_number }}</strong></td>
                        <td>
                            <strong>{{ data_get($certificate,'user.name','—') }}</strong>
                            <small class="admin-cell-hint">{{ data_get($certificate,'user.email') }}</small>
                        </td>
                        <td>{{ data_get($certificate,'course.title','—') }}</td>
                        <td>{{ optional($certificate->issued_on)->format('d M Y') ?: '—' }}</td>
                        <td>
                            <span class="status-chip {{ $certificate->pdf_path ? 'active' : 'pending' }}">
                                {{ $certificate->pdf_path ? 'Generated' : 'Pending' }}
                            </span>
                        </td>
                        <td class="table-actions">
                            <button
                                type="button"
                                class="btn btn-outline btn-sm"
                                data-modal-open="generateCertificate{{ $certificate->id }}"
                            >
                                <i class="fas fa-file-pdf"></i>
                                {{ $certificate->pdf_path ? 'Regenerate' : 'Generate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty">
                                <i class="fas fa-certificate"></i>
                                <strong>No certificates found</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $certificates->links() }}
    </div>
</div>

@foreach($certificates as $certificate)
    <div class="eh-modal" id="generateCertificate{{ $certificate->id }}" aria-hidden="true">
        <div class="eh-modal-dialog eh-modal-sm">
            <div class="eh-modal-header">
                <div>
                    <h2>{{ $certificate->pdf_path ? 'Regenerate' : 'Generate' }} Certificate?</h2>
                    <p>{{ $certificate->certificate_number }}</p>
                </div>

                <button type="button" class="eh-modal-close" data-modal-close>
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="eh-modal-body">
                <p>
                    Generate the PDF certificate for
                    <strong>{{ data_get($certificate,'user.name','this learner') }}</strong>
                    in
                    <strong>{{ data_get($certificate,'course.title','this course') }}</strong>?
                </p>
            </div>

            <div class="eh-modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>
                    Cancel
                </button>

                @if(Route::has('admin.elearning.certificates.generate'))
                    <form method="POST" action="{{ route('admin.elearning.certificates.generate',$certificate) }}">
                        @csrf
                        <button class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i>
                            Generate PDF
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endforeach
@endsection
