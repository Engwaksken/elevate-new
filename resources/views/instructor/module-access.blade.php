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
.ma-list form{margin:0}
.ma-num{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#f2f4f7;font-weight:800;color:#475467}
.ma-title{flex:1;min-width:200px}.ma-title strong{display:block;color:#172033}.ma-title small{color:#667085}
.ma-groups{display:flex;flex-wrap:wrap;gap:6px}.ma-groups a{padding:6px 12px;border:1px solid #d0d5dd;border-radius:999px;color:#475467;font-weight:700;font-size:.8rem;text-decoration:none;background:#fff}
.ma-groups a.is-active{background:#800000;border-color:#800000;color:#fff}
.ma-subhead{margin:12px 0 6px;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:#667085}
.ma-note{margin:8px 0 0;color:#667085;font-size:.9rem}
.ma-partial{background:#fffaeb;color:#b54708;border:1px solid #fedf89}
@media(max-width:800px){.ma-modes{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor / Trainer Workspace</span>
        <h1>Module access: {{ $course->title }}</h1>
        <p>Choose, for all your groups, a whole branch or one group, whether modules are all open, open in order, or opened by you.</p>
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
@php
    $targetLabel = $target['label'];
    $byKind = $targets->groupBy('kind');
@endphp
<div class="admin-panel">
    <h2 style="margin:0 0 4px;font-size:1rem">Apply to</h2>
    <p class="ma-note" style="margin:0 0 6px">Pick all your groups, every group at one branch, or a single group. Changes below apply to every group you pick.</p>
    @foreach(['all' => null, 'branch' => 'By branch', 'cohort' => 'Single group'] as $kind => $heading)
        @if($byKind->has($kind))
            @if($heading)<h3 class="ma-subhead">{{ $heading }}</h3>@endif
            <nav class="ma-groups" aria-label="{{ $heading ?? 'All groups' }}">
                @foreach($byKind->get($kind) as $option)
                    @php $isActive = $option['key'] === $target['key']; @endphp
                    <a href="{{ route('instructor.courses.module-access', [$course, 'target' => $option['key']]) }}" @class(['is-active' => $isActive]) @if($isActive) aria-current="page" @endif>
                        @if($kind === 'all')
                            <i class="fas fa-layer-group" aria-hidden="true"></i> All groups ({{ $option['cohorts']->count() }})
                        @elseif($kind === 'branch')
                            <i class="fas fa-location-dot" aria-hidden="true"></i> {{ $option['label'] }} ({{ $option['cohorts']->count() }})
                        @else
                            {{ $option['label'] }}
                        @endif
                    </a>
                @endforeach
            </nav>
        @endif
    @endforeach
    @if($target['kind'] !== 'cohort')
        <p class="ma-note">Groups included: {{ $target['cohorts']->pluck('name')->join(', ') }}</p>
    @endif
</div>

<div class="admin-panel">
    <h2 style="margin:0;font-size:1rem">How modules open for {{ $targetLabel }}</h2>
    @if($mode === null)
        <p class="ma-note">These groups use different rules right now:</p>
        <ul class="ma-note" style="margin:4px 0 0 18px">@foreach($modeSummary as $line)<li>{{ $line }}</li>@endforeach</ul>
        <p class="ma-note">Saving sets the same rule for all {{ $groupCount }} groups.</p>
    @endif
    <form method="POST" action="{{ route('instructor.courses.module-access.settings', $course) }}">
        @csrf @method('PUT')
        <input type="hidden" name="target" value="{{ $target['key'] }}">
        <div class="ma-modes" role="radiogroup" aria-label="How modules open">
            @foreach($modes as $key => [$label, $hint])
                <label class="ma-mode">
                    <input type="radio" name="mode" value="{{ $key }}" @checked($mode === $key) required>
                    <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
                </label>
            @endforeach
        </div>
        <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save{{ $groupCount > 1 ? ' for '.$groupCount.' groups' : '' }}</button>
    </form>
</div>

<div class="admin-panel">
    <h2 style="margin:0 0 4px;font-size:1rem">Modules</h2>
    <p class="icm-muted" style="margin:0 0 12px;color:#667085">
        @if($mode === 'release')
            Release a module when {{ $targetLabel }} {{ $groupCount > 1 ? 'are' : 'is' }} ready for it. Each group is notified when a module opens.
        @elseif($mode === 'sequential')
            Modules open one after another as each participant completes them. Switch to "Released by the instructor" to control the timing yourself.
        @elseif($mode === 'open')
            Every published module is open to {{ $targetLabel }}. Choose "In order" or "Released by the instructor" above to pace them.
        @else
            The groups use different rules. Releasing applies to every group here, and only affects groups set to "Released by the instructor".
        @endif
    </p>
    <ul class="ma-list">
        @forelse($modules as $module)
            @php
                $releasedIn = (int) ($releasedCounts[$module->id] ?? 0);
                $allReleased = $releasedIn >= $groupCount;
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
                @elseif($mode === 'open' || $mode === 'sequential')
                    <span class="status-chip active">{{ $mode === 'open' || $isFirst ? 'Open' : 'Opens in order' }}</span>
                @elseif($isFirst)
                    <span class="status-chip active">Always open (first module)</span>
                @else
                    @if($allReleased)
                        <span class="status-chip active">Released</span>
                    @elseif($releasedIn > 0)
                        <span class="status-chip ma-partial">Released for {{ $releasedIn }} of {{ $groupCount }} groups</span>
                    @else
                        <span class="status-chip inactive">Locked</span>
                    @endif
                    @unless($allReleased)
                        <form method="POST" action="{{ route('instructor.courses.module-access.release', [$course, $module]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="target" value="{{ $target['key'] }}">
                            <input type="hidden" name="is_released" value="1">
                            <button class="btn btn-primary btn-sm"><i class="fas fa-lock-open"></i> Release to {{ $targetLabel }}</button>
                        </form>
                    @endunless
                    @if($releasedIn > 0)
                        <form method="POST" action="{{ route('instructor.courses.module-access.release', [$course, $module]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="target" value="{{ $target['key'] }}">
                            <input type="hidden" name="is_released" value="0">
                            <button class="btn btn-outline btn-sm"><i class="fas fa-lock"></i> Lock again{{ $groupCount > 1 ? ' for all' : '' }}</button>
                        </form>
                    @endif
                @endif
            </li>
        @empty
            <li>This course has no modules yet.</li>
        @endforelse
    </ul>
</div>
@endif
@endsection
