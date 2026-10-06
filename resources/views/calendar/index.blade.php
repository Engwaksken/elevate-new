@extends(auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title', 'Calendar | ElevateHer360')
@section('content')
<style>.schedule-grid{display:grid;grid-template-columns:repeat(7,minmax(120px,1fr));min-width:850px;border:1px solid #ddd}.schedule-day{padding:10px;min-height:140px;border:1px solid #eee;background:#fff}.schedule-day.outside{background:#f6f6f6}.schedule-item{display:block;padding:6px;margin:6px 0;border-left:3px solid #800000;background:#fff4e3;font-size:.85rem;overflow-wrap:anywhere}.schedule-item.cancelled{opacity:.65;border-left-color:#777}.schedule-scroll{overflow:auto}.schedule-weekday{padding:10px;text-align:center;font-weight:700}.schedule-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}</style>
<div class="admin-page-header"><div><span class="admin-eyebrow">Schedule</span><h1>Calendar</h1><p>{{ auth()->user()->isStaff() ? 'Course timetables and platform activities across all courses.' : 'Timetable sessions for your enrolled courses and other available activities.' }} Times shown in {{ $zone }}.</p></div></div>
<div class="admin-panel"><form method="GET" class="schedule-toolbar">
<a class="btn btn-outline btn-sm" href="{{ route('calendar.index', array_merge(request()->except(['month','page']), ['month'=>$start->subMonth()->format('Y-m')])) }}">Previous month</a>
<label>Month <input type="month" name="month" value="{{ $start->format('Y-m') }}"></label>
<select name="event_type"><option value="">All activities</option><option value="course_timetable" @selected(request('event_type')==='course_timetable')>Course timetable</option>@foreach(['event','workshop','training','mentorship','appointment','programme_activity'] as $type)<option value="{{ $type }}" @selected(request('event_type')===$type)>{{ ucfirst(str_replace('_',' ',$type)) }}</option>@endforeach</select>
@if(request('programme_id'))<input type="hidden" name="programme_id" value="{{ request('programme_id') }}">@endif
<button class="btn btn-primary btn-sm">Show calendar</button>
<a class="btn btn-outline btn-sm" href="{{ route('calendar.index', array_merge(request()->except(['month','page']), ['month'=>$start->addMonth()->format('Y-m')])) }}">Next month</a>
</form></div>
<div class="schedule-scroll"><div class="schedule-grid">
@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)<div class="schedule-weekday">{{ $day }}</div>@endforeach
@for($date=$gridStart; $date->lte($gridEnd); $date=$date->addDay())
<div class="schedule-day {{ $date->month !== $start->month ? 'outside' : '' }}"><strong>{{ $date->day }}</strong>
@foreach($days->get($date->format('Y-m-d'), collect()) as $entry)
<div class="schedule-item {{ $entry['status']==='cancelled' ? 'cancelled' : '' }}">
<strong>{{ $entry['starts_at']->format('H:i') }}@if($entry['ends_at']) – {{ $entry['ends_at']->format('H:i') }}@endif</strong>
@if($entry['url'])<a href="{{ $entry['url'] }}">{{ $entry['title'] }}</a>@else<span>{{ $entry['title'] }}</span>@endif
@if($entry['course_title'])<div>{{ $entry['course_title'] }}</div>@endif
@if($entry['status']==='cancelled')<span>Cancelled</span>@elseif($entry['status']==='draft')<span>Draft</span>@endif
</div>
@endforeach</div>@endfor
</div></div>
<div class="admin-panel" style="margin-top:20px"><h2>Calendar agenda</h2><div class="eh-data-list">
@forelse($events as $entry)<article class="eh-data-row"><div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-calendar-days"></i></span><div class="eh-data-row-copy"><strong>{{ $entry['title'] }}</strong>@if($entry['course_title'])<span>{{ $entry['course_title'] }}</span>@endif<span>{{ $entry['starts_at']->format('d M Y H:i') }}@if($entry['ends_at']) – {{ $entry['ends_at']->format('d M Y H:i') }}@endif · {{ $zone }}</span>@if($entry['venue'])<span>{{ $entry['venue'] }}</span>@endif<span>{{ ucfirst($entry['status']) }}</span></div></div><div class="eh-data-row-actions">@if($entry['url'])<a class="btn btn-outline btn-sm" href="{{ $entry['url'] }}">View</a>@endif @if($entry['meeting_link'])<a class="btn btn-outline btn-sm" href="{{ $entry['meeting_link'] }}" target="_blank" rel="noopener noreferrer">Online meeting</a>@endif</div></article>
@empty<p>No activities for this month.</p>@endforelse
</div>{{ $events->links() }}</div>
@endsection
