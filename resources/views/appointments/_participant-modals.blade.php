@php($canCancel = $service->participantCanCancel($appointment) && $appointment->ends_at->isFuture())
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
