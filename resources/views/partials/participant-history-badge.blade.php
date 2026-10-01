{{-- Returning-participant / duplicate-phone badges. Expects $history (ParticipantHistoryService::summaries row) and optional $duplicates (Collection of users). --}}
@if(! empty($history['returning']))
    <span class="ph-badge ph-returning" title="{{ $history['courses']->map(fn ($c) => $c['title'].' ('.ucfirst($c['status']).($c['year'] ? ', '.$c['year'] : '').')')->join('; ') }}">
        <i class="fas fa-rotate-left"></i> Returning · {{ $history['summary'] }}
    </span>
@elseif(isset($history))
    <span class="ph-badge ph-new"><i class="fas fa-seedling"></i> New participant</span>
@endif
@if(isset($duplicates) && $duplicates->isNotEmpty())
    <span class="ph-badge ph-duplicate" title="Same phone as: {{ $duplicates->map(fn ($u) => $u->name.' '.($u->participant_code ? '('.$u->participant_code.')' : ''))->join(', ') }}">
        <i class="fas fa-triangle-exclamation"></i> Possible duplicate account
    </span>
@endif
