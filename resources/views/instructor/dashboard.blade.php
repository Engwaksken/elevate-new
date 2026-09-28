@extends('layouts.admin')
@section('title','My Courses | ElevateHer360')

@section('content')
<style>
.mc-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
.mc-stat{background:#fff;border:1px solid #e5e7eb;border-left:4px solid #800000;border-radius:12px;padding:16px}
.mc-stat small{display:block;color:#667085;font-weight:700}.mc-stat strong{display:block;font-size:1.35rem;color:#101828;margin-top:3px}
.mc-filter{display:grid;grid-template-columns:minmax(240px,1fr) 220px auto;gap:10px;align-items:end;margin-bottom:18px}
.mc-filter input,.mc-filter select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff}
.mc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.mc-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;min-height:220px}
.mc-card h2{margin:10px 0 8px;font-size:1.05rem}.mc-card p{color:#667085;margin:0 0 8px}.mc-actions{margin-top:auto;padding-top:14px}
.mc-badge{display:inline-flex;padding:5px 9px;border-radius:999px;background:#f2f4f7;color:#344054;font-size:.78rem;font-weight:700}
@media(max-width:1000px){.mc-stats{grid-template-columns:repeat(2,1fr)}.mc-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:680px){.mc-stats,.mc-grid,.mc-filter{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer</span>
        <h1>My Courses</h1>
        <p>View assigned courses and open one course to manage all authorised learning functions from its tabs.</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
        <i class="fas fa-gauge-high"></i> Dashboard
    </a>
</div>

<div class="mc-stats">
    <div class="mc-stat"><small>Assigned Courses</small><strong>{{ number_format($stats['courses'] ?? 0) }}</strong></div>
    <div class="mc-stat"><small>Total Learners</small><strong>{{ number_format($stats['learners'] ?? 0) }}</strong></div>
    <div class="mc-stat"><small>Lead Courses</small><strong>{{ number_format($stats['lead_courses'] ?? 0) }}</strong></div>
    <div class="mc-stat"><small>Active Courses</small><strong>{{ number_format($stats['active_courses'] ?? 0) }}</strong></div>
</div>

<div class="admin-panel">
    <form method="GET" class="mc-filter">
        <div>
            <label>Search assigned courses</label>
            <input name="search" value="{{ request('search') }}" placeholder="Course title, code or keyword">
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All statuses</option>
                @foreach(['published','active','draft','archived'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    </form>

    <div class="mc-grid">
        @forelse($courses as $course)
            <article class="mc-card">
                <div>
                    <span class="mc-badge">{{ ucfirst(str_replace('_',' ', $course->status ?: 'assigned')) }}</span>
                    @if((bool)data_get($course,'pivot.is_lead',false))
                        <span class="mc-badge">Lead</span>
                    @endif
                </div>

                <h2>{{ $course->title }}</h2>

                @if($course->code)
                    <p><strong>Code:</strong> {{ $course->code }}</p>
                @endif

                <p>
                    <i class="fas fa-users"></i>
                    {{ number_format((int)$course->enrolments_count) }}
                    participant{{ (int)$course->enrolments_count === 1 ? '' : 's' }}
                </p>

                @if($course->summary)
                    <p>{{ \Illuminate\Support\Str::limit($course->summary, 120) }}</p>
                @endif

                <div class="mc-actions">
                    @if(Route::has('instructor.courses.manage'))
                        <a href="{{ route('instructor.courses.manage',$course) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right"></i> Open Course
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="admin-empty" style="grid-column:1/-1">
                <i class="fas fa-graduation-cap"></i>
                <strong>No assigned courses found</strong>
                <span>Try clearing the filters or ask an administrator to assign a course.</span>
            </div>
        @endforelse
    </div>

    <div style="margin-top:18px">
        {{ $courses->links() }}
    </div>
</div>
@endsection
