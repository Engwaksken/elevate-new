@extends('layouts.app')
@section('title', 'My Support Requests | ElevateHer360')

@section('content')
<div class="page-header">
    <div><span class="eh-kicker">Support</span><h1>My Support Requests</h1><p>Review your requests and track their current status.</p></div>
    <div class="page-actions"><x-export-buttons /><a href="#new-support-request" class="btn btn-primary">Submit a request</a></div>
</div>

@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

<div class="eh-data-list">
    @forelse($tickets as $ticket)
        <a class="eh-data-row" href="{{ route('participant.support-tickets.show', $ticket) }}">
            <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-life-ring"></i></span><div class="eh-data-row-copy">
                <strong>{{ $ticket->subject }}</strong>
                <span>Ticket #{{ $ticket->id }} · Submitted {{ $ticket->created_at?->format('d M Y') ?: '—' }}</span>
            </div></div>
            <span class="status-chip {{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </a>
    @empty
        <div class="eh-empty"><h3>No support requests yet</h3><p>Submit a request below and its status will appear here.</p></div>
    @endforelse
</div>
<div class="admin-pagination">{{ $tickets->links() }}</div>

<section id="new-support-request" class="eh-tab-section" aria-labelledby="new-support-request-title">
    <h2 id="new-support-request-title">Submit a support request</h2>
    <form method="POST" action="{{ route('participant.support-tickets.store') }}" class="form-grid">
        @csrf
        <div class="form-group"><label for="subject">Subject *</label><input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255">@error('subject')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group"><label for="category">Category</label><input id="category" name="category" value="{{ old('category') }}">@error('category')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group"><label for="priority">Priority</label><select id="priority" name="priority"><option value="">Select priority</option>@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select>@error('priority')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group"><label for="description">Describe the issue *</label><textarea id="description" name="description" rows="5" required>{{ old('description') }}</textarea>@error('description')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <button class="btn btn-primary" type="submit">Send support request</button>
    </form>
</section>
@endsection
