@extends('layouts.app')
@section('content')

<div class="instructor-dashboard-page">
    <div class="admin-page-header">
        <div>
            <span class="admin-eyebrow">Instructor Workspace</span>
            <h1>Instructor Dashboard</h1>
            <p>Access assigned courses and record learner attendance.</p>
        </div>
    </div>

    <div class="admin-stats-grid compact">
        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas fa-graduation-cap"></i></span>
            <div><small>Assigned Courses</small><strong>{{ number_format($stats['courses'] ?? 0) }}</strong></div>
        </div>

        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas fa-users"></i></span>
            <div><small>Total Learners</small><strong>{{ number_format($stats['learners'] ?? 0) }}</strong></div>
        </div>

        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas fa-star"></i></span>
            <div><small>Lead Courses</small><strong>{{ number_format($stats['lead_courses'] ?? 0) }}</strong></div>
        </div>

        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas fa-circle-check"></i></span>
            <div><small>Active Courses</small><strong>{{ number_format($stats['active_courses'] ?? 0) }}</strong></div>
        </div>
    </div>

    <div class="instructor-course-grid">
        @forelse($courses as $course)
            <article class="instructor-course-card">
                <div class="instructor-course-card-head">
                    <span class="instructor-course-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </span>

                    <span class="status-chip {{ $course->status }}">
                        {{ ucfirst(str_replace('_',' ',$course->status)) }}
                    </span>
                </div>

                <h2>{{ $course->title }}</h2>

                <p>
                    {{ number_format($course->enrolments_count) }}
                    learner{{ $course->enrolments_count === 1 ? '' : 's' }}
                </p>

                <div class="instructor-course-actions">
                    <a
                        class="btn btn-primary btn-sm"
                        href="{{ route('instructor.attendance.create',$course) }}"
                    >
                        <i class="fas fa-user-check"></i>
                        Record Attendance
                    </a>
                </div>
            </article>
        @empty
            <div class="admin-empty">
                <i class="fas fa-graduation-cap"></i>
                <strong>No courses assigned</strong>
                <span>Assigned courses will appear here.</span>
            </div>
        @endforelse
    </div>
</div>
@endsection
