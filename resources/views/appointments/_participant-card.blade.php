@php
    /** @var \App\Models\InstructorAppointment $appointment */
    $canCancel = $service->participantCanCancel($appointment) && $appointment->ends_at->isFuture();
    $proposalOpen = $appointment->status === \App\Models\InstructorAppointment::PROPOSED;
@endphp
{{-- One table row per appointment; the cancel modal lives in _participant-modals so it sits outside the table. --}}
<tr>
    <td>
        <strong><time datetime="{{ $appointment->starts_at->toIso8601String() }}">{{ $appointment->starts_at->format('D j M Y') }}</time></strong>
        <small class="admin-cell-hint">{{ $appointment->starts_at->format('H:i') }} to {{ $appointment->ends_at->format('H:i') }} · {{ $appointment->duration_minutes }} min</small>
    </td>
    <td>
        <strong>{{ $appointment->topic }}</strong>
        @if($appointment->details)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($appointment->details, 120) }}</small>@endif
    </td>
    <td>{{ $appointment->instructor?->name ?? 'Instructor' }}@if($appointment->course)<small class="admin-cell-hint">{{ $appointment->course->title }}</small>@endif</td>
    <td>
        <i class="fas {{ $appointment->mode === 'online' ? 'fa-video' : 'fa-location-dot' }}" aria-hidden="true"></i> {{ $appointment->modeLabel() }}
        @if($appointment->status === 'approved' && $appointment->meeting_url)<small class="admin-cell-hint"><a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener noreferrer">Join online meeting</a></small>@endif
        @if($appointment->status === 'approved' && $appointment->location)<small class="admin-cell-hint">{{ $appointment->location }}</small>@endif
    </td>
    <td>
        <span class="appt-chip appt-chip--{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span>
        @if($proposalOpen && $appointment->proposed_starts_at)
            <small class="admin-cell-hint" style="color:#5925dc"><strong>New time proposed:</strong> {{ $appointment->proposed_starts_at->format('D j M Y, H:i') }} to {{ $appointment->proposedEndsAt()->format('H:i') }}@if($appointment->proposal_note) · "{{ $appointment->proposal_note }}"@endif</small>
        @endif
        @if(in_array($appointment->status, ['declined', 'cancelled'], true) && $appointment->decision_reason)
            <small class="admin-cell-hint" style="color:#b42318">{{ $appointment->status === 'cancelled' ? 'Cancellation note' : 'Reason' }}: {{ $appointment->decision_reason }}</small>
        @endif
    </td>
    <td class="table-actions">
        @if($proposalOpen || $canCancel)
            <div class="action-group">
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
        @else
            <span class="appt-hint">-</span>
        @endif
    </td>
</tr>
