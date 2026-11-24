@php
    $items = $signIns['items'];
    $channels = ['web' => 'Website', 'app' => 'Mobile app'];
@endphp

<div class="admin-stats-grid compact">
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-right-to-bracket"></i></span>
        <div><small>Sign-ins today</small><strong>{{ number_format($signIns['today']) }}</strong></div>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-calendar-week"></i></span>
        <div><small>Sign-ins, last 7 days</small><strong>{{ number_format($signIns['week']) }}</strong></div>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-user-check"></i></span>
        <div><small>Participants signed in, last 30 days</small><strong>{{ number_format($signIns['people_30']) }}</strong></div>
    </div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" data-auto-filter>
    <input type="hidden" name="tab" value="signins">
    @if(request('branch_id'))
        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
    @endif
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="signin_search" value="{{ request('signin_search') }}" placeholder="Search participant name, email or ID..." aria-label="Search participant">
    </div>
    <select name="channel" aria-label="Signed in from">
        <option value="">Website and app</option>
        @foreach($channels as $value => $label)
            <option value="{{ $value }}" @selected(request('channel') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <div><label for="signinFrom" class="sr-only">From</label><input id="signinFrom" type="date" name="from" value="{{ request('from') }}" max="{{ now()->toDateString() }}" title="From"></div>
    <div><label for="signinTo" class="sr-only">To</label><input id="signinTo" type="date" name="to" value="{{ request('to') }}" max="{{ now()->toDateString() }}" title="To"></div>
    <select name="per_page" aria-label="Rows per page">
        @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}" @selected((int) request('per_page', 25) === $n)>{{ $n }}/page</option>
        @endforeach
    </select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.participants.index', array_filter(['tab' => 'signins', 'branch_id' => request('branch_id')])) }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Participant</th>
    <th>Signed in</th>
    <th>From</th>
    <th>Device</th>
    <th>IP address</th>
</tr>
</thead>
<tbody>
@forelse($items as $signIn)
<tr>
    <td>
        @if($signIn->user)
            <strong>{{ $signIn->user->name }}</strong>
            @if($signIn->user->participant_code)<span class="participant-code">{{ $signIn->user->participant_code }}</span>@endif
            <small class="admin-cell-hint">{{ $signIn->user->email }}</small>
        @else
            <span class="admin-cell-hint">{{ $signIn->email ?: 'Deleted account' }}</span>
        @endif
    </td>
    <td>
        {{ $signIn->attempted_at?->format('d M Y, H:i') }}
        <small class="admin-cell-hint">{{ $signIn->attempted_at?->diffForHumans() }}</small>
    </td>
    <td>{{ $channels[$signIn->channel] ?? 'Website' }}</td>
    <td>{{ $signIn->deviceLabel() }}</td>
    <td>{{ $signIn->ip_address ?: '-' }}</td>
</tr>
@empty
<tr><td colspan="5"><div class="admin-empty">No sign-ins recorded{{ request()->hasAny(['signin_search','channel','from','to']) ? ' for these filters' : ' yet' }}. Every participant sign-in is recorded from 24 Nov 2026.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $items->links() }}</div>
</div>
