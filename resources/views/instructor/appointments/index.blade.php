@extends('layouts.admin')
@section('title', 'Appointments | ElevateHer360')
@section('content')
@include('appointments._styles')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Instructor</span>
        <h1>Appointments</h1>
        <p>Participants of your courses request one-to-one time here. Approve, decline or propose a different time.</p>
    </div>
    <div class="admin-page-actions">
        <x-export-buttons />
        @if($canViewAll)
            <a class="btn btn-outline btn-sm" href="{{ route('instructor.appointments.index', array_merge(request()->except(['scope', 'page']), $showAll ? [] : ['scope' => 'all'])) }}">
                <i class="fas {{ $showAll ? 'fa-user' : 'fa-users' }}" aria-hidden="true"></i> {{ $showAll ? 'Show only mine' : 'View all instructors' }}
            </a>
        @endif
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach([['pending', 'New requests', 'fa-inbox'], ['awaiting', 'Awaiting participant', 'fa-hourglass-half'], ['upcoming', 'Upcoming approved', 'fa-calendar-check'], ['completed', 'Completed', 'fa-circle-check']] as [$key, $label, $icon])
        <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
    @endforeach
</div>

<div class="admin-panel">
    <nav aria-label="Appointment lists">
        <ul class="appt-tabs">
            @foreach($tabs as $key => $label)
                <li><a href="{{ route('instructor.appointments.index', array_merge(request()->only(['scope', 'search']), ['tab' => $key])) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }} <span class="appt-count">{{ $counts[$key] }}</span></a></li>
            @endforeach
        </ul>
    </nav>

    <form method="GET" class="admin-toolbar" role="search">
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if($showAll)<input type="hidden" name="scope" value="all">@endif
        <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search participant or topic..." aria-label="Search appointments"></div>
        <button class="btn btn-primary btn-sm">Search</button>
        @if(request('search'))<a href="{{ route('instructor.appointments.index', array_merge(request()->only('scope'), ['tab' => $tab])) }}" class="btn btn-outline btn-sm">Reset</a>@endif
    </form>

    @if($appointments->isEmpty())
        <div class="appt-empty"><i class="fas fa-calendar" aria-hidden="true"></i>
            {{ match($tab) { 'requests' => 'No appointment requests right now.', 'upcoming' => 'No upcoming appointments.', default => 'No past appointments yet.' } }}
        </div>
    @else
        <div class="appt-list">
            @foreach($appointments as $appointment)
                @php
                    $mine = $appointment->instructor_user_id === auth()->id();
                    $isPending = $appointment->status === 'pending';
                    $isApproved = $appointment->status === 'approved';
                    $started = $appointment->starts_at->isPast();
                @endphp
                <article class="appt-card appt-card--{{ $appointment->status }}" aria-labelledby="ia-{{ $appointment->id }}-title">
                    <div class="appt-date" aria-hidden="true"><b>{{ $appointment->starts_at->format('j') }}</b><span>{{ $appointment->starts_at->format('M') }}</span></div>
                    <div class="appt-body">
                        <div class="appt-head">
                            <h3 id="ia-{{ $appointment->id }}-title">{{ $appointment->topic }}</h3>
                            <span class="appt-chip appt-chip--{{ $appointment->status }}">{{ $appointment->status === 'rescheduled_proposed' ? 'Awaiting participant' : $appointment->statusLabel() }}</span>
                        </div>
                        <ul class="appt-meta">
                            <li><i class="fas fa-user-graduate" aria-hidden="true"></i> {{ $appointment->participant?->name ?? 'Participant' }}</li>
                            @if($appointment->participant?->email)<li><i class="fas fa-envelope" aria-hidden="true"></i> {{ $appointment->participant->email }}</li>@endif
                            @if($showAll)<li><i class="fas fa-chalkboard-user" aria-hidden="true"></i> {{ $appointment->instructor?->name }}</li>@endif
                            @if($appointment->course)<li><i class="fas fa-book" aria-hidden="true"></i> {{ $appointment->course->title }}</li>@endif
                            <li><i class="fas fa-clock" aria-hidden="true"></i> <time datetime="{{ $appointment->starts_at->toIso8601String() }}">{{ $appointment->starts_at->format('D j M Y, H:i') }}</time> – {{ $appointment->ends_at->format('H:i') }}</li>
                            <li><i class="fas {{ $appointment->mode === 'online' ? 'fa-video' : 'fa-location-dot' }}" aria-hidden="true"></i> {{ $appointment->modeLabel() }} · {{ $appointment->duration_minutes }} min</li>
                        </ul>
                        @if($appointment->details)<p class="appt-details">{{ $appointment->details }}</p>@endif
                        @if($appointment->status === 'rescheduled_proposed' && $appointment->proposed_starts_at)
                            <p class="appt-note appt-note--proposal"><strong>You proposed:</strong> {{ $appointment->proposed_starts_at->format('D j M Y, H:i') }} – {{ $appointment->proposedEndsAt()->format('H:i') }}. Waiting for the participant to respond.</p>
                        @endif
                        @if($isApproved && ($appointment->meeting_url || $appointment->location))
                            <p class="appt-note appt-note--link">
                                @if($appointment->meeting_url)<i class="fas fa-video" aria-hidden="true"></i> <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener noreferrer">Meeting link</a>@endif
                                @if($appointment->meeting_url && $appointment->location) · @endif
                                @if($appointment->location)<i class="fas fa-location-dot" aria-hidden="true"></i> {{ $appointment->location }}@endif
                            </p>
                        @endif
                        @if(in_array($appointment->status, ['declined', 'cancelled'], true) && ($appointment->decision_reason || $appointment->cancelled_by))
                            <p class="appt-note appt-note--reason">@if($appointment->status === 'cancelled' && $appointment->cancelled_by)<strong>Cancelled by {{ $appointment->cancelled_by === $appointment->participant_user_id ? 'participant' : 'instructor' }}.</strong> @endif{{ $appointment->decision_reason }}</p>
                        @endif

                        @if($mine && ($isPending || $isApproved))
                            <div class="appt-actions">
                                @if($isPending)
                                    <button type="button" class="btn btn-primary btn-sm" data-modal-open="ia-approve-{{ $appointment->id }}"><i class="fas fa-check" aria-hidden="true"></i> Approve</button>
                                    <button type="button" class="btn btn-outline btn-sm" data-modal-open="ia-propose-{{ $appointment->id }}"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Propose time</button>
                                    <button type="button" class="btn btn-outline btn-sm" data-modal-open="ia-decline-{{ $appointment->id }}"><i class="fas fa-xmark" aria-hidden="true"></i> Decline</button>
                                @elseif($isApproved)
                                    @if($started)
                                        <form method="POST" action="{{ route('instructor.appointments.complete', $appointment) }}">@csrf
                                            <button class="btn btn-primary btn-sm"><i class="fas fa-circle-check" aria-hidden="true"></i> Mark completed</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-outline btn-sm" data-modal-open="ia-cancel-{{ $appointment->id }}"><i class="fas fa-ban" aria-hidden="true"></i> Cancel</button>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                </article>

                @if($mine && $isPending)
                    <div class="eh-modal" id="ia-approve-{{ $appointment->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ia-approve-{{ $appointment->id }}-title">
                        <div class="eh-modal-dialog"><form method="POST" action="{{ route('instructor.appointments.approve', $appointment) }}">@csrf
                            <div class="eh-modal-header"><div><h2 id="ia-approve-{{ $appointment->id }}-title">Approve appointment</h2><p>{{ $appointment->participant?->name }} · {{ $appointment->starts_at->format('D j M Y, H:i') }} – {{ $appointment->ends_at->format('H:i') }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                            <div class="eh-modal-body"><div class="modal-grid">
                                <div class="form-group full"><label for="ia-url-{{ $appointment->id }}">Meeting link {{ $appointment->mode === 'online' ? '(recommended)' : '(optional)' }}</label><input id="ia-url-{{ $appointment->id }}" type="url" name="meeting_url" maxlength="500" placeholder="https://meet.example.com/..."></div>
                                <div class="form-group full"><label for="ia-loc-{{ $appointment->id }}">Venue {{ $appointment->mode === 'in_person' ? '(recommended)' : '(optional)' }}</label><input id="ia-loc-{{ $appointment->id }}" name="location" maxlength="255" placeholder="e.g. Room 4, Kampala hub"></div>
                            </div><span class="appt-hint">The participant is notified and the appointment appears on both your calendars.</span></div>
                            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-check" aria-hidden="true"></i> Approve</button></div>
                        </form></div>
                    </div>
                    <div class="eh-modal" id="ia-propose-{{ $appointment->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ia-propose-{{ $appointment->id }}-title">
                        <div class="eh-modal-dialog"><form method="POST" action="{{ route('instructor.appointments.propose', $appointment) }}">@csrf
                            <div class="eh-modal-header"><div><h2 id="ia-propose-{{ $appointment->id }}-title">Propose another time</h2><p>Requested: {{ $appointment->starts_at->format('D j M Y, H:i') }} ({{ $appointment->duration_minutes }} min)</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                            <div class="eh-modal-body"><div class="modal-grid">
                                <div class="form-group"><label for="ia-pdate-{{ $appointment->id }}">New date *</label><input id="ia-pdate-{{ $appointment->id }}" type="date" name="proposed_date" required min="{{ now()->toDateString() }}" value="{{ $appointment->starts_at->isFuture() ? $appointment->starts_at->toDateString() : now()->addDay()->toDateString() }}"></div>
                                <div class="form-group"><label for="ia-ptime-{{ $appointment->id }}">New start time *</label><input id="ia-ptime-{{ $appointment->id }}" type="time" name="proposed_time" required step="300" value="{{ $appointment->starts_at->format('H:i') }}"></div>
                                <div class="form-group full"><label for="ia-pnote-{{ $appointment->id }}">Message to participant (optional)</label><textarea id="ia-pnote-{{ $appointment->id }}" name="note" maxlength="1000" rows="3"></textarea></div>
                            </div></div>
                            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send proposal</button></div>
                        </form></div>
                    </div>
                    <div class="eh-modal" id="ia-decline-{{ $appointment->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ia-decline-{{ $appointment->id }}-title">
                        <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('instructor.appointments.decline', $appointment) }}">@csrf
                            <div class="eh-modal-header"><div><h2 id="ia-decline-{{ $appointment->id }}-title">Decline request</h2><p>{{ $appointment->participant?->name }} · {{ $appointment->topic }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                            <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full"><label for="ia-reason-{{ $appointment->id }}">Reason *</label><textarea id="ia-reason-{{ $appointment->id }}" name="reason" required maxlength="1000" rows="3"></textarea></div></div></div>
                            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Back</button><button class="btn btn-danger"><i class="fas fa-xmark" aria-hidden="true"></i> Decline</button></div>
                        </form></div>
                    </div>
                @elseif($mine && $isApproved && ! $started)
                    <div class="eh-modal" id="ia-cancel-{{ $appointment->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ia-cancel-{{ $appointment->id }}-title">
                        <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('instructor.appointments.cancel', $appointment) }}">@csrf
                            <div class="eh-modal-header"><div><h2 id="ia-cancel-{{ $appointment->id }}-title">Cancel appointment</h2><p>{{ $appointment->participant?->name }} · {{ $appointment->starts_at->format('D j M, H:i') }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
                            <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full"><label for="ia-creason-{{ $appointment->id }}">Reason *</label><textarea id="ia-creason-{{ $appointment->id }}" name="reason" required maxlength="1000" rows="3"></textarea></div></div></div>
                            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep it</button><button class="btn btn-danger"><i class="fas fa-ban" aria-hidden="true"></i> Cancel appointment</button></div>
                        </form></div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="admin-pagination">{{ $appointments->links() }}</div>
    @endif
</div>
@endsection
