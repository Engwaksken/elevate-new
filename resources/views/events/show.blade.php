@extends('layouts.app')
@section('title',$event->title.' | ElevateHer360')
@section('content')
<div class="page-header">
<div><span class="eh-kicker">{{ ucwords(str_replace('_',' ',$event->event_type)) }}</span><h1>{{ $event->title }}</h1><p>{{ $event->starts_at->format('l, d F Y · H:i') }}</p></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

<div class="event-detail-grid">
<section class="eh-tab-section">
<h2>About this event</h2><p>{{ $event->description ?: 'No description provided.' }}</p>
<div class="event-meta-list">
<span><i class="fas fa-location-dot"></i> {{ $event->venue ?: ucfirst($event->delivery_mode) }}</span>
@if($event->district)<span><i class="fas fa-map"></i> {{ $event->district }}</span>@endif
@if($event->capacity)<span><i class="fas fa-users"></i> Capacity {{ $event->capacity }}</span>@endif
@if($event->ends_at)<span><i class="fas fa-clock"></i> Ends {{ $event->ends_at->format('d M Y H:i') }}</span>@endif
</div>

@php
$googleUrl='https://calendar.google.com/calendar/render?action=TEMPLATE'
.'&text='.rawurlencode($event->title)
.'&dates='.$event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.($event->ends_at ?: $event->starts_at->copy()->addHour())->copy()->utc()->format('Ymd\THis\Z')
.'&details='.rawurlencode($event->description ?? '')
.'&location='.rawurlencode($event->venue ?: $event->meeting_url ?? '');
@endphp
<div class="event-calendar-actions">
<a href="{{ route('events.calendar',$event) }}" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Add to Calendar</a>
<a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="btn btn-outline"><i class="fab fa-google"></i> Google Calendar</a>
</div>
</section>

<aside class="event-register-card">
<h2>Participation</h2>
@if($registered)<div class="alert alert-success" data-no-auto-dismiss="true">You are registered for this event.</div>
@elseif(auth()->check())<form method="POST" action="{{ route('events.register',$event) }}">@csrf<button class="btn btn-primary btn-block"><i class="fas fa-user-plus"></i> Register for Event</button></form>
@else<p>Sign in to register for this event.</p>@endif

@if($attended && $event->feedback_enabled)
<a href="{{ route('events.feedback',$event) }}" class="btn btn-outline btn-block"><i class="fas fa-comment-dots"></i> {{ $feedbackSubmitted ? 'Update Feedback':'Give Feedback' }}</a>
@endif

@if($attended && $event->certificate_enabled && (!$event->certificate_requires_feedback || $feedbackSubmitted))
<a href="{{ route('events.certificate',$event) }}" class="btn btn-outline btn-block"><i class="fas fa-award"></i> Download Certificate</a>
@endif
</aside>
</div>
@endsection
