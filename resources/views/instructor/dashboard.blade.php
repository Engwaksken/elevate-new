@extends('layouts.admin')
@section('title','My Courses | ElevateHer360')

@section('content')
@php $activeTab = request('tab') === 'overview' ? 'overview' : 'courses'; @endphp

<style>
.mc-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
.mc-stat{background:#fff;border:1px solid #e5e7eb;border-left:4px solid #800000;border-radius:12px;padding:16px}
.mc-stat small{display:block;color:#667085;font-weight:700}.mc-stat strong{display:block;font-size:1.35rem;color:#101828;margin-top:3px}
.mc-tabs{display:flex;gap:7px;overflow-x:auto;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:18px}
.mc-tabs [role=tab]{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border:0;border-radius:8px;background:transparent;color:#475467;font:inherit;font-weight:700;white-space:nowrap;cursor:pointer}
.mc-tabs [role=tab].active{background:#800000;color:#fff}
.mc-tabs [role=tab]:focus-visible,.mc-actions a:focus-visible{outline:3px solid #175cd3;outline-offset:3px}
.mc-tabpanel[hidden]{display:none!important}
.mc-filter{display:grid;grid-template-columns:2fr repeat(4,minmax(150px,1fr)) auto;gap:10px;align-items:end;margin-bottom:18px}
.mc-filter input,.mc-filter select{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff}
.mc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.mc-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;min-height:220px}
.mc-card h2{margin:10px 0 8px;font-size:1.05rem}.mc-card p{color:#667085;margin:0 0 8px}.mc-actions{margin-top:auto;padding-top:14px}
.mc-badge{display:inline-flex;padding:5px 9px;border-radius:999px;background:#f2f4f7;color:#344054;font-size:.78rem;font-weight:700}
.mc-guidance p{color:#475467;margin:0}
.mc-section-title{margin:0 0 14px;font-size:1.25rem;color:#101828}
.mc-filter label{display:block;margin-bottom:5px;font-weight:700;color:#344054}
@media(max-width:1200px){.mc-filter{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1000px){.mc-stats{grid-template-columns:repeat(2,1fr)}.mc-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:680px){.mc-stats,.mc-grid,.mc-filter{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer</span>
        <h1>My Courses</h1>
        <p>Search, filter and open assigned courses. All course functions stay inside the selected course workspace.</p>
    </div>
    <div class="admin-page-actions">
        <x-export-buttons />
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
    </div>
</div>

<div class="mc-tabset" data-mc-tabs>
<div class="mc-tabs" role="tablist" aria-label="My Courses sections" aria-orientation="horizontal">
    <button type="button" id="mc-tab-courses" role="tab" aria-controls="mc-panel-courses" aria-selected="{{ $activeTab !== 'overview' ? 'true' : 'false' }}" tabindex="{{ $activeTab !== 'overview' ? '0' : '-1' }}" class="{{ $activeTab !== 'overview' ? 'active' : '' }}">
        <i class="fas fa-graduation-cap"></i> My Courses
    </button>
    <button type="button" id="mc-tab-overview" role="tab" aria-controls="mc-panel-overview" aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}" tabindex="{{ $activeTab === 'overview' ? '0' : '-1' }}" class="{{ $activeTab === 'overview' ? 'active' : '' }}">
        <i class="fas fa-gauge-high"></i> Overview
    </button>
</div>

    <section class="mc-tabpanel mc-overview" id="mc-panel-overview" role="tabpanel" aria-labelledby="mc-tab-overview" tabindex="0" @if($activeTab !== 'overview') hidden @endif>
    <h2 class="mc-section-title" id="overview-heading">Course overview</h2>
    <div class="mc-stats">
        <div class="mc-stat"><small>Assigned Courses</small><strong>{{ number_format($stats['courses'] ?? 0) }}</strong></div>
        <div class="mc-stat"><small>Total Learners</small><strong>{{ number_format($stats['learners'] ?? 0) }}</strong></div>
        <div class="mc-stat"><small>Lead Courses</small><strong>{{ number_format($stats['lead_courses'] ?? 0) }}</strong></div>
        <div class="mc-stat"><small>Active Courses</small><strong>{{ number_format($stats['active_courses'] ?? 0) }}</strong></div>
    </div>

    <div class="admin-panel mc-guidance">
        <p>This area shows a summary of the courses assigned to you. Use the numbers above to track how many courses you deliver, how many learners you support, and how many of your courses are lead or currently active. Switch to the <strong>My Courses</strong> tab to search, filter and open any assigned course workspace.</p>
    </div>
    </section>
    <section class="mc-tabpanel" id="mc-panel-courses" role="tabpanel" aria-labelledby="mc-tab-courses" tabindex="0" @if($activeTab === 'overview') hidden @endif>
    <h2 class="mc-section-title" id="courses-heading">Assigned courses</h2>
    <div class="admin-panel">
        <form method="GET" class="mc-filter">
            <input type="hidden" name="tab" value="courses">
            <div><label for="course-search">Search</label><input id="course-search" name="search" value="{{ request('search') }}" placeholder="Title, code or keyword"></div>
            <div><label for="course-period">Period</label><select id="course-period" name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $value=>$label)<option value="{{ $value }}" @selected(request('period')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="course-filter">Course</label><select id="course-filter" name="course_id"><option value="">All courses</option>@foreach($courseOptions as $option)<option value="{{ $option->id }}" @selected((string)request('course_id')===(string)$option->id)>{{ $option->title }}</option>@endforeach</select></div>
            <div><label for="cohort-filter">Cohort</label><select id="cohort-filter" name="cohort_id"><option value="">All cohorts</option>@foreach($cohortOptions as $option)<option value="{{ $option->id }}" @selected((string)request('cohort_id')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
            <div><label for="status-filter">Status</label><select id="status-filter" name="status"><option value="">All statuses</option>@foreach(['published','active','draft','archived'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <button class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="mc-grid">
            @forelse($courses as $course)
                <article class="mc-card">
                    <div>
                        <span class="mc-badge">{{ ucfirst(str_replace('_',' ', $course->status ?: 'assigned')) }}</span>
                        @if((bool)data_get($course,'pivot.is_lead',false))<span class="mc-badge">Lead</span>@endif
                    </div>
                    <h2>{{ $course->title }}</h2>
                    @if($course->code)<p><strong>Code:</strong> {{ $course->code }}</p>@endif
                    <p><i class="fas fa-users"></i> {{ number_format((int)$course->enrolments_count) }} participants</p>
                    @if($course->cohorts->isNotEmpty())<p><strong>Cohorts:</strong> {{ $course->cohorts->pluck('name')->join(', ') }}</p>@endif
                    @if($course->summary)<p>{{ \Illuminate\Support\Str::limit($course->summary, 120) }}</p>@endif
                    <div class="mc-actions">
                        <a href="{{ route('instructor.courses.manage',$course) }}" class="btn btn-primary btn-sm"><i class="fas fa-arrow-right"></i> Open Course</a>
                    </div>
                </article>
            @empty
                <div class="admin-empty" style="grid-column:1/-1"><i class="fas fa-graduation-cap"></i><strong>No assigned courses found</strong><span>Adjust the filters or ask an administrator to assign a course.</span></div>
            @endforelse
        </div>

        <div style="margin-top:18px">{{ $courses->appends(['tab' => 'courses'])->links() }}</div>
    </div>
    </section>
</div>

<script>
(function () {
    var root = document.querySelector('[data-mc-tabs]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    function activate(tab, moveFocus) {
        tabs.forEach(function (item) {
            var selected = item === tab;
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            item.tabIndex = selected ? 0 : -1;
            item.classList.toggle('active', selected);
            document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
        });
        if (moveFocus) tab.focus();
    }
    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { activate(tab, false); });
        tab.addEventListener('keydown', function (event) {
            var next = null;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = tabs[(index + 1) % tabs.length];
            else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = tabs[(index - 1 + tabs.length) % tabs.length];
            else if (event.key === 'Home') next = tabs[0];
            else if (event.key === 'End') next = tabs[tabs.length - 1];
            if (next) { event.preventDefault(); activate(next, true); }
        });
    });
})();
</script>
@endsection
