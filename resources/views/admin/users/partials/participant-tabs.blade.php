@php
    // Tabs keep the branch filter; each tab's own filters and paging are dropped when switching.
    $tabLink = fn ($tab) => route('admin.participants.index', array_filter([
        'tab' => $tab,
        'branch_id' => request('branch_id'),
    ], fn ($v) => $v !== null && $v !== ''));
    $tabs = [
        'participants' => ['Participants', 'fa-users'],
        'activity' => ['Participant activity', 'fa-chart-line'],
        'signins' => ['Sign-ins', 'fa-right-to-bracket'],
    ];
@endphp

<nav class="pa-tabs" aria-label="Participant views">
    @foreach($tabs as $key => [$label, $icon])
        <a href="{{ $tabLink($key) }}" @class(['is-active' => $participantTab === $key])
           @if($participantTab === $key) aria-current="page" @endif>
            <i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}
        </a>
    @endforeach
</nav>
