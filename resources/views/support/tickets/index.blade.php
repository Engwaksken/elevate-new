@extends('layouts.admin')

@section('title', 'IT Support Queue | ElevateHer360')

@section('content')
@use('App\Models\ItSupportTicket')
@php($canManageQueue = auth()->user()->can('assign', new ItSupportTicket()))
@php($priorityLabels = ItSupportTicket::PRIORITIES + ['medium' => 'Normal'])

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">IT support</span>
        <h1>IT Support Queue</h1>
        <p>{{ $canManageQueue ? 'Every help request from staff and participants. Most urgent and newest first.' : 'Your help requests and their progress.' }}</p>
    </div>
    <div class="admin-page-actions">
        <x-export-buttons />
        @if(Route::has('staff.support.index'))<a href="{{ route('staff.support.index') }}" class="btn btn-outline"><i class="fas fa-life-ring"></i> Help &amp; Support</a>@endif
    </div>
</div>

<div class="admin-stats-grid compact itq-stats">
    @foreach([['open','Open','fa-inbox','open'],['in_progress','In progress','fa-spinner','in_progress'],['awaiting_requester','Awaiting requester','fa-reply','awaiting_requester'],['unassigned','Unassigned','fa-user-slash',null]] as [$key,$label,$icon,$filter])
        <a class="admin-stat itq-stat" href="{{ route('it-support.tickets.index', $filter ? ['status' => $filter] : ['assignee_id' => 'none']) }}">
            <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
            <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
        </a>
    @endforeach
</div>

<section class="admin-panel">
    <form method="GET" action="{{ route('it-support.tickets.index') }}" class="itq-filters" aria-label="Filter support tickets">
        <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search subject, details or requester…" aria-label="Search tickets"></div>
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            @foreach(ItSupportTicket::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $value === 'awaiting_requester' ? 'Awaiting requester' : $label }}</option>
            @endforeach
        </select>
        <select name="priority" aria-label="Priority">
            <option value="">All priorities</option>
            @foreach(ItSupportTicket::PRIORITIES as $value => $label)
                <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" aria-label="Category">
            <option value="">All categories</option>
            @foreach(ItSupportTicket::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if($canManageQueue)
            <select name="assignee_id" aria-label="Assignee">
                <option value="">Anyone</option>
                <option value="none" @selected(request('assignee_id') === 'none')>Unassigned</option>
                @foreach($assignees as $person)
                    <option value="{{ $person->id }}" @selected((string) request('assignee_id') === (string) $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="btn btn-primary btn-sm" type="submit">Apply</button>
        <a class="btn btn-outline btn-sm" href="{{ route('it-support.tickets.index') }}">Reset</a>
    </form>

    @if($tickets->isNotEmpty())
        <div class="itq-list-tools">
            <span>{{ number_format($tickets->total()) }} {{ \Illuminate\Support\Str::plural('ticket', $tickets->total()) }} · click a ticket to show its details</span>
            <button type="button" class="btn btn-outline btn-sm" data-itq-toggle-all aria-expanded="false"><i class="fas fa-up-right-and-down-left-from-center"></i> <span>Expand all</span></button>
        </div>
    @endif
    <div class="itq-list" aria-label="Tickets">
        @forelse($tickets as $ticket)
            <details class="itq-row itq-{{ $ticket->status }}">
                <summary>
                    <span class="itq-row-top">
                        <i class="fas fa-chevron-right itq-caret" aria-hidden="true"></i>
                        <span class="itq-id">#{{ $ticket->id }}</span>
                        @if(in_array($ticket->priority, ['urgent', 'high'], true))<span class="itq-pri itq-pri--{{ $ticket->priority }}"><i class="fas fa-flag"></i> {{ $priorityLabels[$ticket->priority] ?? ucfirst($ticket->priority) }}</span>@endif
                        <span class="itq-chip itq-chip--{{ $ticket->status }}">{{ $ticket->status === 'awaiting_requester' ? 'Awaiting requester' : (ItSupportTicket::STATUSES[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status))) }}</span>
                    </span>
                    <strong class="itq-subject">{{ $ticket->subject }}</strong>
                    <span class="itq-row-foot">
                        <span class="itq-row-who"><i class="fas fa-user"></i> {{ $ticket->requester?->name ?? 'Unknown' }}</span>
                        <span class="itq-row-when">{{ $ticket->created_at?->diffForHumans() ?? '-' }}</span>
                    </span>
                </summary>
                <div class="itq-row-body">
                    <p class="itq-desc">{{ \Illuminate\Support\Str::limit($ticket->description, 400) }}</p>
                    <div class="itq-meta">
                        <span><i class="fas fa-tag"></i> {{ ItSupportTicket::categoryLabel($ticket->category) ?: 'Uncategorised' }}</span>
                        @if($ticket->priority)<span class="itq-pri itq-pri--{{ $ticket->priority }}"><i class="fas fa-flag"></i> {{ $priorityLabels[$ticket->priority] ?? ucfirst($ticket->priority) }}</span>@endif
                        <span><i class="fas fa-user"></i> {{ $ticket->requester?->name ?? 'Unknown' }}</span>
                        <span><i class="fas fa-user-gear"></i> {{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                        <span><i class="fas fa-clock"></i> {{ $ticket->created_at?->format('d M Y, H:i') ?? '-' }}</span>
                    </div>
                    <a class="btn btn-primary btn-sm" href="{{ route('it-support.tickets.show', $ticket) }}"><i class="fas fa-arrow-up-right-from-square"></i> Open ticket</a>
                </div>
            </details>
        @empty
            <div class="admin-empty itq-empty"><i class="fas fa-mug-hot"></i><strong>No tickets found</strong><span>{{ request()->hasAny(['status','priority','category','assignee_id','search']) ? 'Try changing or clearing the filters.' : 'Nothing in the queue right now.' }}</span></div>
        @endforelse
    </div>
    <div class="admin-pagination">{{ $tickets->links() }}</div>
</section>

@include('support.tickets._styles')
<script>
(() => {
    const toggle = document.querySelector('[data-itq-toggle-all]');
    const rows = () => [...document.querySelectorAll('.itq-list .itq-row')];
    if (!toggle) return;
    const sync = () => {
        const allOpen = rows().length && rows().every(r => r.open);
        toggle.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
        toggle.querySelector('span').textContent = allOpen ? 'Collapse all' : 'Expand all';
        toggle.querySelector('i').className = 'fas ' + (allOpen ? 'fa-down-left-and-up-right-to-center' : 'fa-up-right-and-down-left-from-center');
    };
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        rows().forEach(r => { r.open = open; });
        sync();
    });
    rows().forEach(r => r.addEventListener('toggle', sync));
})();
</script>
@endsection
