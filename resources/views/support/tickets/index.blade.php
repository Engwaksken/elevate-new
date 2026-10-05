@extends('layouts.app')

@section('title', 'Support Ticket Queue | ElevateHer360')

@section('content')
@php($canManageQueue = auth()->check() && auth()->user()->hasRole(['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant']))

<div class="page-header">
    <div>
        <span class="eh-kicker">IT support</span>
        <h1>Support tickets</h1>
        <p>Review requests and track their progress.</p>
    </div>
</div>

@if($canManageQueue)
<form method="GET" action="{{ route('it-support.tickets.index') }}" class="eh-tab-section" aria-label="Filter support tickets">
    <div class="modal-grid">
        <div class="form-group">
            <label for="ticket-status">Status</label>
            <select id="ticket-status" name="status">
                <option value="">All statuses</option>
                @foreach(['open' => 'Open', 'in_progress' => 'In progress', 'awaiting_requester' => 'Awaiting requester', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="ticket-priority">Priority</label>
            <select id="ticket-priority" name="priority">
                <option value="">All priorities</option>
                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="ticket-category">Category</label>
            <input id="ticket-category" type="text" name="category" value="{{ request('category') }}" placeholder="Any category">
        </div>
        <div class="form-group">
            <label for="ticket-assignee">Assignee</label>
            <input id="ticket-assignee" type="number" name="assignee_id" min="1" value="{{ request('assignee_id') }}" placeholder="Any assignee">
        </div>
    </div>
    <button class="btn btn-primary btn-sm" type="submit">Apply filters</button>
    <a class="btn btn-outline btn-sm" href="{{ route('it-support.tickets.index') }}">Clear</a>
</form>
@endif

<section class="eh-tab-section" aria-label="Ticket results">
    @forelse($tickets as $ticket)
        <article class="eh-tab-section">
            <div class="page-header">
                <div>
                    <span class="eh-kicker">Ticket #{{ $ticket->id }} · {{ $ticket->category ?: 'Uncategorized' }}</span>
                    <h2><a href="{{ route('it-support.tickets.show', $ticket) }}">{{ $ticket->subject }}</a></h2>
                    <p>From {{ $ticket->requester?->name ?? 'Unknown requester' }} · {{ $ticket->created_at?->format('d M Y, H:i') ?: '—' }}</p>
                </div>
                <p><span class="status-chip {{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></p>
            </div>
            <p>Priority: {{ $ticket->priority ? ucfirst($ticket->priority) : '—' }} · Assigned to: {{ $ticket->assignee?->name ?? 'Unassigned' }}</p>
        </article>
    @empty
        <div class="admin-empty"><strong>No tickets found</strong><span>Try changing or clearing the filters.</span></div>
    @endforelse
    <div class="admin-pagination">{{ $tickets->links() }}</div>
</section>
@endsection
