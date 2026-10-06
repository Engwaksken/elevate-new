@extends('layouts.app')
@section('title', 'My Support Requests | ElevateHer360')

@section('content')
<div class="page-header">
    <div><span class="eh-kicker">Support</span><h1>My Support Requests</h1><p>Review your requests and track their current status.</p></div>
    <div class="page-actions"><x-export-buttons /><a href="#new-support-request" class="btn btn-primary">Submit a request</a></div>
</div>

@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

<section class="sp-card" aria-label="Your support requests">
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
</section>

<section id="new-support-request" class="sp-card sp-form-card" aria-labelledby="new-support-request-title">
    <div class="sp-card-head"><span class="sp-card-icon"><i class="fas fa-life-ring" aria-hidden="true"></i></span><div><h2 id="new-support-request-title">Submit a support request</h2><p>Tell us what went wrong and we’ll get back to you.</p></div></div>
    <form method="POST" action="{{ route('participant.support-tickets.store') }}" class="form-grid sp-form">
        @csrf
        <div class="form-group"><label for="subject">Subject *</label><input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255">@error('subject')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group"><label for="category">Category</label><input id="category" name="category" value="{{ old('category') }}">@error('category')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group"><label for="priority">Priority</label><select id="priority" name="priority"><option value="">Select priority</option>@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select>@error('priority')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="form-group sp-full"><label for="description">Describe the issue *</label><textarea id="description" name="description" rows="5" required>{{ old('description') }}</textarea>@error('description')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="sp-full sp-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send support request</button></div>
    </form>
</section>
<style>
.sp-card{margin-bottom:22px;padding:22px 24px;border:1px solid #eadede;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.sp-card .eh-data-list{margin:0;border:0;padding:0}
.sp-card .eh-empty{margin:0;padding:18px 0;background:transparent;border:0;box-shadow:none}
.sp-card .admin-pagination:empty{display:none}
.sp-card-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.sp-card-head h2{margin:0 0 2px;font-size:1.2rem}
.sp-card-head p{margin:0;color:#667085;font-size:.85rem}
.sp-card-icon{width:40px;height:40px;display:grid;place-items:center;flex-shrink:0;border-radius:12px;background:rgba(128,0,0,.08);color:#800000}
.sp-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}
.sp-form .form-group{margin:0}
.sp-form .sp-full{grid-column:1/-1}
.sp-actions{display:flex;justify-content:flex-end}
.sp-actions .btn{width:auto;padding-inline:22px}
@media(max-width:640px){.sp-card{padding:18px 16px}.sp-form{grid-template-columns:1fr}.sp-actions .btn{width:100%}}
.sp-card .status-chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.72rem;font-weight:800;letter-spacing:.02em}
.sp-card .status-chip.open{background:#eff8ff;color:#175cd3}
.sp-card .status-chip.in_progress{background:#fffaeb;color:#b54708}
.sp-card .status-chip.resolved,.sp-card .status-chip.closed{background:#ecfdf3;color:#067647}
</style>
@endsection
