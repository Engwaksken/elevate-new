@extends('layouts.admin')
@section('title','Module Access | ElevateHer360')
@section('content')
<style>
.ma-modes{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:12px 0}
.ma-mode{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border:1px solid #d0d5dd;border-radius:10px;background:#fff;cursor:pointer}
.ma-mode:has(input:checked){border-color:#800000;box-shadow:0 0 0 2px rgba(128,0,0,.12)}
.ma-mode input{margin-top:3px}.ma-mode strong{display:block;color:#172033}.ma-mode small{color:#667085}
.ma-list{list-style:none;margin:0;padding:0;border-top:1px solid #eaecf0}
.ma-list li{display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:12px 4px;border-bottom:1px solid #eaecf0}
.ma-num{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#f2f4f7;font-weight:800;color:#475467}
.ma-title{flex:1;min-width:200px}.ma-title strong{display:block;color:#172033}.ma-title small{color:#667085}
.ma-groups{display:flex;flex-wrap:wrap;gap:6px}.ma-groups a{padding:6px 12px;border:1px solid #d0d5dd;border-radius:999px;color:#475467;font-weight:700;font-size:.8rem;text-decoration:none;background:#fff}
.ma-groups a.is-active{background:#800000;border-color:#800000;color:#fff}
@media(max-width:800px){.ma-modes{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer Workspace</span>
        <h1>Module access: {{ $course->title }}</h1>
        <p>Choose, for each group, whether modules are all open, open in order, or opened by you.</p>
    </div>
    <div class="admin-page-actions">
        <a href="{{ route('instructor.courses.manage', [$course, 'tab' => 'modules']) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to course</a>
    </div>
</div>

@if($cohorts->isEmpty())
<div class="admin-panel">
    <div class="admin-empty">
        This course has no groups you manage yet. Module access is set per group, so ask an administrator to add a cohort to this course and link it to your branch.
    </div>
</div>
@else
<div class="admin-panel">
    <h2 style="margin:0 0 8px;font-size:1rem">Group</h2>
    <nav class="ma-groups" aria-label="Groups">
        @foreach($cohorts as $group)
            <a href="{{ route('instructor.courses.module-access', [$course, 'cohort_id' => $group->id]) }}" @class(['is-active' => $cohort->id === $group->id])
               @if($cohort->id === $group->id) aria-current="page" @endif>{{ $group->name }}</a>
        @endforeach
    </nav>
</div>

<div class="admin-panel">
    <h2 style="margin:0;font-size:1rem">How modules open for {{ $cohort->name }}</h2>
    <form method="POST" action="{{ route('instructor.courses.module-access.settings', $course) }}">
        @csrf @method('PUT')
        <input type="hidden" name="cohort_id" value="{{ $cohort->id }}">
        <div class="ma-modes" role="radiogroup" aria-label="How modules open">
            @foreach($modes as $key => [$label, $hint])
                <label class="ma-mode">
                    <input type="radio" name="mode" value="{{ $key }}" @checked($mode === $key)>
                    <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
                </label>
            @endforeach
        </div>
        <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </form>
</div>

<div class="admin-panel">
    <h2 style="margin:0 0 4px;font-size:1rem">Modules</h2>
    <p class="icm-muted" style="margin:0 0 12px;color:#667085">
        @if($mode === 'release')
            Release a module when {{ $cohort->name }} is ready for it. The group is notified when a module opens.
        @elseif($mode === 'sequential')
            Modules open one after another as each participant completes them. Switch to "Released by the instructor" to control the timing yourself.
        @else
            Every published module is open to {{ $cohort->name }}. Choose "In order" or "Released by the instructor" above to pace them.
        @endif
    </p>
    <ul class="ma-list">
        @forelse($modules as $module)
            @php
                $isReleased = (bool) $releases->get($module->id)?->is_released;
                $isFirst = $module->id === $firstPublishedId;
            @endphp
            <li>
                <span class="ma-num">{{ $loop->iteration }}</span>
                <span class="ma-title">
                    <strong>{{ $module->title }}</strong>
                    <small>{{ $module->lessons_count }} lesson(s){{ $module->is_published ? '' : ' · Draft (participants cannot see it)' }}</small>
                </span>
                @if(! $module->is_published)
                    <span class="status-chip draft">Draft</span>
                @elseif($mode !== 'release')
                    <span class="status-chip active">{{ $mode === 'open' ? 'Open' : ($isFirst ? 'Open' : 'Opens in order') }}</span>
                @elseif($isFirst)
                    <span class="status-chip active">Always open (first module)</span>
                @else
                    <span class="status-chip {{ $isReleased ? 'active' : 'inactive' }}">{{ $isReleased ? 'Released' : 'Locked' }}</span>
                    <form method="POST" action="{{ route('instructor.courses.module-access.release', [$course, $module]) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="cohort_id" value="{{ $cohort->id }}">
                        <input type="hidden" name="is_released" value="{{ $isReleased ? 0 : 1 }}">
                        <button class="btn {{ $isReleased ? 'btn-outline' : 'btn-primary' }} btn-sm">
                            <i class="fas {{ $isReleased ? 'fa-lock' : 'fa-lock-open' }}"></i> {{ $isReleased ? 'Lock again' : 'Release to '.$cohort->name }}
                        </button>
                    </form>
                @endif
            </li>
        @empty
            <li>This course has no modules yet.</li>
        @endforelse
    </ul>
</div>
@endif
@endsection
