@php
    /** @var \App\Models\InstructorAppointment $appointment */
    $canCancel = $service->participantCanCancel($appointment) && $appointment->ends_at->isFuture();
    $proposalOpen = $appointment->status === \App\Models\InstructorAppointment::PROPOSED;
@endphp
<article class="appt-card appt-card--{{ $appointment->status }}" aria-labelledby="appt-{{ $appointment->id }}-title">
    <div class="appt-date" aria-hidden="true"><b>{{ $appointment->starts_at->format('j') }}</b><span>{{ $appointment->starts_at->format('M') }}</span></div>
    <div class="appt-body">
        <div class="appt-head">
            <h3 id="appt-{{ $appointment->id }}-title">{{ $appointment->topic }}</h3>
            <span class="appt-chip appt-chip--{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span>
        </div>
        <ul class="appt-meta">
            <li><i class="fas fa-chalkboard-user" aria-hidden="true"></i> {{ $appointment->instructor?->name ?? 'Instructor' }}</li>
            @if($appointment->course)<li><i class="fas fa-book" aria-hidden="true"></i> {{ $appointment->course->title }}</li>@endif
            <li><i class="fas fa-clock" aria-hidden="true"></i> <time datetime="{{ $appointment->starts_at->toIso8601String() }}">{{ $appointment->starts_at->format('D j M Y, H:i') }}</time> – {{ $appointment->ends_at->format('H:i') }}</li>
            <li><i class="fas {{ $appointment->mode === 'online' ? 'fa-video' : 'fa-location-dot' }}" aria-hidden="true"></i> {{ $appointment->modeLabel() }} · {{ $appointment->duration_minutes }} min</li>
        </ul>
        @if($appointment->details)<p class="appt-details">{{ \Illuminate\Support\Str::limit($appointment->details, 220) }}</p>@endif

        @if($proposalOpen && $appointment->proposed_starts_at)
            <p class="appt-note appt-note--proposal"><strong><i class="fas fa-calendar-plus" aria-hidden="true"></i> New time proposed:</strong>
                {{ $appointment->proposed_starts_at->format('D j M Y, H:i') }} – {{ $appointment->proposedEndsAt()->format('H:i') }}@if($appointment->proposal_note)<br>“{{ $appointment->proposal_note }}”@endif</p>
        @endif
        @if($appointment->status === 'approved' && ($appointment->meeting_url || $appointment->location))
            <p class="appt-note appt-note--link">
                @if($appointment->meeting_url)<i class="fas fa-video" aria-hidden="true"></i> <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener noreferrer">Join online meeting</a>@endif
                @if($appointment->meeting_url && $appointment->location) · @endif
                @if($appointment->location)<i class="fas fa-location-dot" aria-hidden="true"></i> {{ $appointment->location }}@endif
            </p>
        @endif
        @if(in_array($appointment->status, ['declined', 'cancelled'], true) && $appointment->decision_reason)
            <p class="appt-note appt-note--reason"><strong>{{ $appointment->status === 'cancelled' ? 'Cancellation note' : 'Reason' }}:</strong> {{ $appointment->decision_reason }}</p>
        @endif

        @if($proposalOpen || $canCancel)
            <div class="appt-actions">
                @if($proposalOpen)
                    <form method="POST" action="{{ route('appointments.accept', $appointment) }}">@csrf
                        <button class="btn btn-primary btn-sm"><i class="fas fa-check" aria-hidden="true"></i> Accept new time</button>
                    </form>
                    <form method="POST" action="{{ route('appointments.decline-proposal', $appointment) }}" data-delete-form data-confirm="Decline the proposed time? The request will be closed.">@csrf
                        <button class="btn btn-outline btn-sm"><i class="fas fa-xmark" aria-hidden="true"></i> Decline</button>
                    </form>
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-outline btn-sm" data-modal-open="appt-cancel-{{ $appointment->id }}"><i class="fas fa-ban" aria-hidden="true"></i> Cancel</button>
                @endif
            </div>
        @elseif($appointment->status === 'approved' && $appointment->ends_at->isFuture())
            <span class="appt-hint">Approved appointments can be cancelled up to {{ \App\Services\AppointmentService::CANCEL_CUTOFF_HOURS }} hours before they start.</span>
        @endif
    </div>
</article>
@if($canCancel)
<div class="eh-modal" id="appt-cancel-{{ $appointment->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="appt-cancel-{{ $appointment->id }}-title">
    <div class="eh-modal-dialog eh-modal-sm">
        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
            @csrf
            <div class="eh-modal-header">
                <div><h2 id="appt-cancel-{{ $appointment->id }}-title">Cancel appointment?</h2><p>{{ $appointment->topic }} · {{ $appointment->starts_at->format('D j M, H:i') }}</p></div>
                <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            </div>
            <div class="eh-modal-body">
                <div class="modal-grid"><div class="form-group full">
                    <label for="appt-cancel-reason-{{ $appointment->id }}">Reason (optional)</label>
                    <textarea id="appt-cancel-reason-{{ $appointment->id }}" name="reason" maxlength="1000" rows="3" placeholder="Let your instructor know why"></textarea>
                </div></div>
            </div>
            <div class="eh-modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Keep it</button>
                <button class="btn btn-danger"><i class="fas fa-ban" aria-hidden="true"></i> Cancel appointment</button>
            </div>
        </form>
    </div>
</div>
@endif
