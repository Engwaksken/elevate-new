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

    <div class="itq-grid" aria-label="Tickets">
        @forelse($tickets as $ticket)
            <a class="itq-card itq-{{ $ticket->status }}" href="{{ route('it-support.tickets.show', $ticket) }}">
                <div class="itq-card-top">
                    <span class="itq-id">#{{ $ticket->id }}</span>
                    <span class="itq-chip itq-chip--{{ $ticket->status }}">{{ $ticket->status === 'awaiting_requester' ? 'Awaiting requester' : (ItSupportTicket::STATUSES[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status))) }}</span>
                </div>
                <strong class="itq-subject">{{ $ticket->subject }}</strong>
                <p class="itq-desc">{{ \Illuminate\Support\Str::limit($ticket->description, 110) }}</p>
                <div class="itq-meta">
                    <span><i class="fas fa-tag"></i> {{ ItSupportTicket::categoryLabel($ticket->category) ?: 'Uncategorised' }}</span>
                    @if($ticket->priority)<span class="itq-pri itq-pri--{{ $ticket->priority }}"><i class="fas fa-flag"></i> {{ $priorityLabels[$ticket->priority] ?? ucfirst($ticket->priority) }}</span>@endif
                </div>
                <div class="itq-foot">
                    <span><i class="fas fa-user"></i> {{ $ticket->requester?->name ?? 'Unknown' }}</span>
                    <span><i class="fas fa-user-gear"></i> {{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                    <span><i class="fas fa-clock"></i> {{ $ticket->created_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </a>
        @empty
            <div class="admin-empty itq-empty"><i class="fas fa-mug-hot"></i><strong>No tickets found</strong><span>{{ request()->hasAny(['status','priority','category','assignee_id','search']) ? 'Try changing or clearing the filters.' : 'Nothing in the queue right now.' }}</span></div>
        @endforelse
    </div>
    <div class="admin-pagination">{{ $tickets->links() }}</div>
</section>

@include('support.tickets._styles')
@endsection
