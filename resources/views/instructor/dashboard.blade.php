@extends('layouts.admin')

@section('title', 'My Courses | ElevateHer360')

@section('content')
<style>
.instructor-workspace{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.instructor-stats{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
}

.instructor-stat{
    display:flex;
    align-items:center;
    gap:12px;
    min-height:92px;
    padding:16px;
    background:#fff;
    border:1px solid #e5e7eb;
    border-left:4px solid #800000;
    border-radius:12px;
    box-shadow:0 4px 14px rgba(16,24,40,.04);
}

.instructor-stat__icon{
    width:40px;
    height:40px;
    flex:0 0 40px;
    display:grid;
    place-items:center;
    border-radius:10px;
    background:#fff7da;
    color:#800000;
    font-size:1rem;
}

.instructor-stat small{
    display:block;
    margin-bottom:4px;
    color:#667085;
    font-weight:700;
}

.instructor-stat strong{
    display:block;
    color:#101828;
    font-size:1.35rem;
}

.instructor-course-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}

.instructor-course-card{
    display:flex;
    flex-direction:column;
    min-height:220px;
    padding:18px;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    box-shadow:0 4px 16px rgba(16,24,40,.04);
}

.instructor-course-card__head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
}

.instructor-course-card__icon{
    width:42px;
    height:42px;
    display:grid;
    place-items:center;
    border-radius:11px;
    background:#fff7da;
    color:#800000;
}

.instructor-course-card h2{
    margin:0 0 8px;
    color:#101828;
    font-size:1.05rem;
}

.instructor-course-card p{
    margin:0 0 6px;
    color:#667085;
}

.instructor-course-card__actions{
    margin-top:auto;
    padding-top:16px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.instructor-status{
    display:inline-flex;
    align-items:center;
    padding:5px 9px;
    border-radius:999px;
    background:#f2f4f7;
    color:#344054;
    font-size:.78rem;
    font-weight:700;
    text-transform:capitalize;
}

.instructor-empty{
    grid-column:1 / -1;
    padding:34px 20px;
    text-align:center;
    background:#fff;
    border:1px dashed #d0d5dd;
    border-radius:14px;
    color:#667085;
}

.instructor-empty i{
    display:block;
    margin-bottom:10px;
    color:#800000;
    font-size:1.8rem;
}

@media(max-width:1100px){
    .instructor-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
    .instructor-course-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}

@media(max-width:640px){
    .instructor-stats,
    .instructor-course-grid{grid-template-columns:1fr}
}
</style>

<div class="instructor-workspace">
    <div class="admin-page-header">
        <div>
            <span class="admin-eyebrow">Instructor / Trainer Workspace</span>
            <h1>My Courses</h1>
            <p>View only the courses assigned to you and manage learner attendance for those courses.</p>
        </div>
    </div>

    <div class="instructor-stats">
        <div class="instructor-stat">
            <span class="instructor-stat__icon"><i class="fas fa-graduation-cap"></i></span>
            <div>
                <small>Assigned Courses</small>
                <strong>{{ number_format($stats['courses'] ?? 0) }}</strong>
            </div>
        </div>

        <div class="instructor-stat">
            <span class="instructor-stat__icon"><i class="fas fa-users"></i></span>
            <div>
                <small>Total Learners</small>
                <strong>{{ number_format($stats['learners'] ?? 0) }}</strong>
            </div>
        </div>

        <div class="instructor-stat">
            <span class="instructor-stat__icon"><i class="fas fa-star"></i></span>
            <div>
                <small>Lead Courses</small>
                <strong>{{ number_format($stats['lead_courses'] ?? 0) }}</strong>
            </div>
        </div>

        <div class="instructor-stat">
            <span class="instructor-stat__icon"><i class="fas fa-circle-check"></i></span>
            <div>
                <small>Active Courses</small>
                <strong>{{ number_format($stats['active_courses'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <div>
                <h2>Assigned Courses</h2>
                <p>Only courses assigned to your staff account are shown here.</p>
            </div>
        </div>

        <div class="instructor-course-grid">
            @forelse($courses as $course)
                <article class="instructor-course-card">
                    <div class="instructor-course-card__head">
                        <span class="instructor-course-card__icon">
                            <i class="fas fa-book-open"></i>
                        </span>

                        <span class="instructor-status">
                            {{ ucfirst(str_replace('_',' ', $course->status ?: 'assigned')) }}
                        </span>
                    </div>

                    <h2>{{ $course->title }}</h2>

                    @if($course->code)
                        <p><strong>Code:</strong> {{ $course->code }}</p>
                    @endif

                    <p>
                        <i class="fas fa-users"></i>
                        {{ number_format((int) $course->enrolments_count) }}
                        learner{{ (int) $course->enrolments_count === 1 ? '' : 's' }}
                    </p>

                    @if((bool) data_get($course, 'pivot.is_lead', false))
                        <p><i class="fas fa-star"></i> Lead Instructor / Trainer</p>
                    @endif

                    <div class="instructor-course-card__actions">
                        @if(Route::has('instructor.attendance.create'))
                            <a
                                class="btn btn-primary btn-sm"
                                href="{{ route('instructor.attendance.create', $course) }}"
                            >
                                <i class="fas fa-user-check"></i>
                                Record Attendance
                            </a>
                        @endif

                        @if(Route::has('admin.elearning.courses.edit') && auth()->user()?->hasPermission('courses.edit'))
                            <a
                                class="btn btn-outline btn-sm"
                                href="{{ route('admin.elearning.courses.edit', $course) }}"
                            >
                                <i class="fas fa-pen"></i>
                                Course Setup
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="instructor-empty">
                    <i class="fas fa-graduation-cap"></i>
                    <strong>No courses assigned</strong>
                    <p>Your assigned courses will appear here after an Administrator assigns you as an Instructor or Trainer.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
