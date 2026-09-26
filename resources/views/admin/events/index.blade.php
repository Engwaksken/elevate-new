@extends('layouts.admin')
@section('title','Events | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Events</h1><p>Create events, publish opportunities, manage registrations and track attendance.</p></div>
<div class="admin-page-actions"><button class="btn btn-primary" type="button" data-modal-open="createEvent"><i class="fas fa-plus"></i> Add Event</button></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
<div class="admin-stats-grid compact">
@foreach([['total','Total Events','fa-calendar-days'],['upcoming','Upcoming','fa-clock'],['published','Published','fa-globe'],['attendance','Attendance Records','fa-user-check']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search event, venue or district..."></div>
<select name="event_type"><option value="">All event types</option>@foreach(['training','workshop','webinar','meeting','mentorship','career_fair','community','other'] as $type)<option value="{{ $type }}" @selected(request('event_type')===$type)>{{ ucwords(str_replace('_',' ',$type)) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}"><input type="date" name="to" value="{{ request('to') }}"><button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.events.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Event</th><th>Date & Venue</th><th>Registrations</th><th>Attendance</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($events as $event)
<tr><td><strong>{{ $event->title }}</strong><small class="admin-cell-hint">{{ ucwords(str_replace('_',' ',$event->event_type)) }}</small></td><td>{{ $event->starts_at->format('d M Y H:i') }}<small class="admin-cell-hint">{{ $event->venue ?: ucfirst($event->delivery_mode) }}</small></td><td>{{ $event->registrations_count }}{{ $event->capacity ? ' / '.$event->capacity : '' }}</td><td>{{ $event->attendance_records_count }}</td><td><span class="status-chip {{ $event->is_published ? 'active':'inactive' }}">{{ $event->is_published ? 'Published':'Draft' }}</span></td><td class="table-actions"><div class="action-group"><a href="{{ route('admin.events.view',$event) }}" class="btn-icon" title="View Event"><i class="fas fa-eye"></i></a><a href="{{ route('admin.events.attendance',$event) }}" class="btn-icon" title="Attendance"><i class="fas fa-user-check"></i></a><button type="button" class="btn-icon" data-modal-open="editEvent{{ $event->id }}"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('admin.events.destroy',$event) }}">@csrf @method('DELETE')<button class="btn-icon danger"><i class="fas fa-box-archive"></i></button></form></div></td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No events found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $events->links() }}</div></div>

@php($blank=new \App\Models\Event(['delivery_mode'=>'physical','event_type'=>'training','registration_required'=>true]))
@include('admin.events.modal',['id'=>'createEvent','title'=>'Add Event','event'=>$blank,'action'=>route('admin.events.store'),'method'=>'POST'])
@foreach($events as $event)
@include('admin.events.modal',['id'=>'editEvent'.$event->id,'title'=>'Edit Event','event'=>$event,'action'=>route('admin.events.update',$event),'method'=>'PUT'])
@endforeach
@endsection