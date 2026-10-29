@extends(auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title', 'Calendar | ElevateHer360')
@section('content')
<style>
    .schedule-grid{display:grid;grid-template-columns:repeat(7,minmax(120px,1fr));min-width:850px;border:1px solid #ddd}
    .schedule-day{padding:10px;min-height:140px;border:1px solid #eee;background:#fff}
    .schedule-day.outside{background:#f6f6f6}
    .schedule-item{display:block;padding:6px;margin:6px 0;border-left:3px solid #800000;background:#fff4e3;font-size:.85rem;overflow-wrap:anywhere}
    .schedule-item.cancelled{opacity:.65;border-left-color:#777}
    .schedule-scroll{overflow:auto}
    .schedule-weekday{padding:10px;text-align:center;font-weight:700}
    .schedule-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .calendar-view-tabs{display:flex;gap:8px;margin:18px 0 12px;border-bottom:1px solid #ddd}
    .calendar-view-tab{padding:10px 14px;border:0;border-bottom:3px solid transparent;background:transparent;color:#555;font:inherit;font-weight:700;cursor:pointer}
    .calendar-view-tab[aria-selected="true"]{border-bottom-color:#800000;color:#800000}
    .calendar-view-tab:focus-visible{outline:2px solid #800000;outline-offset:2px}
    [data-calendar-panel][hidden]{display:none!important}
</style>
<div class="admin-page-header"><div><span class="admin-eyebrow">Schedule</span><h1>Calendar</h1><p>{{ auth()->user()->isStaff() ? 'Course timetables and platform activities across all courses.' : 'Timetable sessions for your enrolled courses and other available activities.' }} Times shown in {{ $zone }}.</p></div></div>
<div class="admin-panel"><form method="GET" class="schedule-toolbar">
<a class="btn btn-outline btn-sm" href="{{ route('calendar.index', array_merge(request()->except(['month','page']), ['month'=>$start->subMonth()->format('Y-m')])) }}">Previous month</a>
<label>Month <input type="month" name="month" value="{{ $start->format('Y-m') }}"></label>
<select name="event_type"><option value="">All activities</option><option value="course_timetable" @selected(request('event_type')==='course_timetable')>Course timetable</option>@foreach(['event','workshop','training','mentorship','appointment','programme_activity'] as $type)<option value="{{ $type }}" @selected(request('event_type')===$type)>{{ ucfirst(str_replace('_',' ',$type)) }}</option>@endforeach</select>
@if(request('programme_id'))<input type="hidden" name="programme_id" value="{{ request('programme_id') }}">@endif
<input type="hidden" name="view" value="{{ $activeView }}">
<button class="btn btn-primary btn-sm">Show calendar</button>
<a class="btn btn-outline btn-sm" href="{{ route('calendar.index', array_merge(request()->except(['month','page']), ['month'=>$start->addMonth()->format('Y-m')])) }}">Next month</a>
</form></div>
<div class="calendar-view-tabs" role="tablist" aria-label="Calendar views">
    <button type="button" class="calendar-view-tab" id="calendarViewTab" role="tab" aria-controls="calendarViewPanel" aria-selected="{{ $activeView === 'calendar' ? 'true' : 'false' }}" tabindex="{{ $activeView === 'calendar' ? '0' : '-1' }}" data-calendar-tab="calendar">Calendar</button>
    <button type="button" class="calendar-view-tab" id="agendaViewTab" role="tab" aria-controls="agendaViewPanel" aria-selected="{{ $activeView === 'agenda' ? 'true' : 'false' }}" tabindex="{{ $activeView === 'agenda' ? '0' : '-1' }}" data-calendar-tab="agenda">Calendar agenda</button>
</div>
<section id="calendarViewPanel" role="tabpanel" aria-labelledby="calendarViewTab" data-calendar-panel="calendar" @if($activeView !== 'calendar') hidden @endif>
<div class="schedule-scroll"><div class="schedule-grid">
@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<div class="schedule-weekday">{{ $day }}</div>@endforeach
@for($date=$gridStart; $date->lte($gridEnd); $date=$date->addDay())
<div class="schedule-day {{ $date->month !== $start->month ? 'outside' : '' }}"><strong>{{ $date->day }}</strong>
@foreach($days->get($date->format('Y-m-d'), collect()) as $entry)
<div class="schedule-item {{ $entry['status']==='cancelled' ? 'cancelled' : '' }}">
<strong>{{ $entry['starts_at']->format('H:i') }}@if($entry['ends_at']) - {{ $entry['ends_at']->format('H:i') }}@endif</strong>
@if($entry['url'])<a href="{{ $entry['url'] }}">{{ $entry['title'] }}</a>@else<span>{{ $entry['title'] }}</span>@endif
@if($entry['course_title'])<div>{{ $entry['course_title'] }}</div>@endif
@if($entry['status']==='cancelled')<span>Cancelled</span>@elseif($entry['status']==='draft')<span>Draft</span>@endif
</div>
@endforeach</div>@endfor
</div></div>
 </section>
<section id="agendaViewPanel" role="tabpanel" aria-labelledby="agendaViewTab" data-calendar-panel="agenda" @if($activeView !== 'agenda') hidden @endif>
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Activity</th><th>When ({{ $zone }})</th><th>Venue</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($events as $entry)<tr><td><strong>{{ $entry['title'] }}</strong>@if($entry['course_title'])<small class="admin-cell-hint">{{ $entry['course_title'] }}</small>@endif</td><td>{{ $entry['starts_at']->format('d M Y H:i') }}@if($entry['ends_at'])<small class="admin-cell-hint">to {{ $entry['ends_at']->format('d M Y H:i') }}</small>@endif</td><td>{{ $entry['venue'] ?: '-' }}</td><td><span class="status-chip {{ $entry['status'] }}">{{ ucfirst($entry['status']) }}</span></td><td class="table-actions"><div class="action-group">@if($entry['url'])<a class="btn btn-outline btn-sm" href="{{ $entry['url'] }}"><i class="fas fa-eye"></i> View</a>@endif @if($entry['meeting_link'])<a class="btn btn-outline btn-sm" href="{{ $entry['meeting_link'] }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-video"></i> Online meeting</a>@endif</div></td></tr>
@empty<tr><td colspan="5"><div class="admin-empty">No activities for this month.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $events->links() }}</div></div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = [...document.querySelectorAll('[data-calendar-tab]')];
    const panels = [...document.querySelectorAll('[data-calendar-panel]')];
    const activate = (view, updateUrl = true) => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.calendarTab === view;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.calendarPanel !== view; });
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.history.replaceState({}, '', url);
            const field = document.querySelector('input[name="view"]');
            if (field) field.value = view;
        }
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab.dataset.calendarTab));
        tab.addEventListener('keydown', (event) => {
            let target = null;
            if (event.key === 'ArrowRight') target = tabs[(index + 1) % tabs.length];
            if (event.key === 'ArrowLeft') target = tabs[(index - 1 + tabs.length) % tabs.length];
            if (event.key === 'Home') target = tabs[0];
            if (event.key === 'End') target = tabs[tabs.length - 1];
            if (target) {
                event.preventDefault();
                target.focus();
                activate(target.dataset.calendarTab);
            }
        });
    });
});
</script>
@endpush
