@extends('layouts.admin')
@section('title','Help & Support | ElevateHer360')
@section('content')
@php
    $openCreate = $errors->any();
    $categories = \App\Models\ItSupportTicket::CATEGORIES;
    $priorities = \App\Models\ItSupportTicket::PRIORITIES;
    $statuses = \App\Models\ItSupportTicket::STATUSES;
    $itCategories = ['hardware', 'network', 'email', 'software', 'printer', 'other_it'];
    $isItTeam = auth()->user()?->hasRole(\App\Services\ItSupportTicketService::SUPPORT_ROLES);
    $faqs = [
        ['My laptop will not turn on or is very slow', 'Charge it for at least 15 minutes and restart it. If it is still slow or will not start, raise a "Laptop / hardware" request and mention the asset tag on the sticker underneath.'],
        ['I cannot connect to the Wi-Fi or internet', 'Forget the network and reconnect, then restart your device. If colleagues are also affected, mark the request as High priority under "Network / internet".'],
        ['I forgot my password or my email account is locked', 'Use "Forgot password" on the sign-in page first. If you are still locked out, raise an "Email / accounts" request from a colleague\'s session or contact IT directly.'],
        ['I need a new software licence or access to a system', 'Raise a "Software / system access" request, name the tool and explain what you need it for. Your supervisor may be asked to confirm.'],
        ['How quickly will IT respond?', 'Urgent requests (work completely blocked) are picked up first. You will get a notification each time your request\'s status or assignee changes.'],
    ];
@endphp

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Self-service</span>
    <h1>Help &amp; Support</h1>
    <p>Get help from the IT team — laptops, network, email, software, printers — or ask for general help, then track every request here.</p>
</div>
<div class="admin-page-actions">
    <x-export-buttons />
    @if($isItTeam && Route::has('it-support.tickets.index'))
        <a href="{{ route('it-support.tickets.index') }}" class="btn btn-outline"><i class="fas fa-headset" aria-hidden="true"></i> IT queue</a>
    @endif
    <button type="button" class="btn btn-primary" data-modal-open="ss-new"><i class="fas fa-plus" aria-hidden="true"></i> New request</button>
</div>
</div>

@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

<div class="admin-stats-grid compact">
@foreach([['open','Open','fa-inbox'],['in_progress','In progress','fa-spinner'],['awaiting_requester','Awaiting your reply','fa-reply'],['resolved','Resolved','fa-circle-check']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
@endforeach
</div>

<div class="ss-layout">
<section class="admin-panel ss-requests" aria-labelledby="ss-requests-title">
    <div class="ss-panel-head"><h2 id="ss-requests-title">My requests</h2></div>
    <form method="GET" class="admin-toolbar" aria-label="Filter my requests">
        <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search subject or details..." aria-label="Search requests"></div>
        <select name="status" aria-label="Status"><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-primary btn-sm">Apply</button>
        <a href="{{ route('staff.support.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="ss-list">
    @forelse($tickets as $ticket)
        <details class="ss-ticket ss-{{ $ticket->status }}">
            <summary>
                <span class="ss-ticket-icon" aria-hidden="true"><i class="fas {{ in_array($ticket->category, $itCategories, true) ? 'fa-laptop' : 'fa-life-ring' }}"></i></span>
                <span class="ss-ticket-copy">
                    <strong>{{ $ticket->subject }}</strong>
                    <small>#{{ $ticket->id }} · {{ \App\Models\ItSupportTicket::categoryLabel($ticket->category) }} · {{ $ticket->created_at?->format('d M Y') }}</small>
                </span>
                <span class="ss-ticket-chips">
                    @if(in_array($ticket->priority, ['high','urgent'], true))<span class="status-chip ss-prio-{{ $ticket->priority }}">{{ $priorities[$ticket->priority] }}</span>@endif
                    <span class="status-chip {{ $ticket->status }}">{{ $statuses[$ticket->status] ?? ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
                </span>
            </summary>
            <div class="ss-ticket-body">
                <dl class="ss-meta">
                    <div><dt>Category</dt><dd>{{ \App\Models\ItSupportTicket::categoryLabel($ticket->category) }}</dd></div>
                    <div><dt>Priority</dt><dd>{{ $priorities[$ticket->priority] ?? ucfirst((string) $ticket->priority) }}</dd></div>
                    <div><dt>Assigned to</dt><dd>{{ $ticket->assignee?->name ?: 'Not yet assigned' }}</dd></div>
                    <div><dt>Submitted</dt><dd>{{ $ticket->created_at?->format('d M Y, H:i') }}</dd></div>
                    <div><dt>Last status change</dt><dd>{{ $ticket->status_updated_at?->format('d M Y, H:i') ?: '—' }}</dd></div>
                </dl>
                <p class="ss-description">{!! nl2br(e($ticket->description)) !!}</p>
            </div>
        </details>
    @empty
        <div class="admin-empty"><i class="fas fa-life-ring" aria-hidden="true"></i><strong>No requests yet</strong><span>Use “New request” whenever you need help from IT or support.</span></div>
    @endforelse
    </div>
    <div class="admin-pagination">{{ $tickets->links() }}</div>
</section>

<aside class="ss-help" aria-label="Help information">
    <section class="admin-panel">
        <div class="ss-panel-head"><h2>Contact IT support</h2></div>
        <ul class="ss-contact">
            @if($support['email'])<li><i class="fas fa-envelope" aria-hidden="true"></i><a href="mailto:{{ $support['email'] }}">{{ $support['email'] }}</a></li>@endif
            @if($support['phone'])<li><i class="fas fa-phone" aria-hidden="true"></i><a href="tel:{{ preg_replace('/\s+/','',$support['phone']) }}">{{ $support['phone'] }}</a></li>@endif
            @if($support['whatsapp'])<li><i class="fab fa-whatsapp" aria-hidden="true"></i><span>{{ $support['whatsapp'] }}</span></li>@endif
            @if($support['hours'])<li><i class="fas fa-clock" aria-hidden="true"></i><span>{{ $support['hours'] }}</span></li>@endif
            <li><i class="fas fa-headset" aria-hidden="true"></i><span>Requests go straight to the IT Lead and IT Assistants, who are notified immediately.</span></li>
        </ul>
        @if($support['technical'])<p class="ss-technical">{!! nl2br(e($support['technical'])) !!}</p>@endif
    </section>
    <section class="admin-panel">
        <div class="ss-panel-head"><h2>Quick answers</h2></div>
        <div class="ss-faqs">
        @foreach($faqs as [$q, $a])
            <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
        </div>
    </section>
</aside>
</div>

{{-- New request --}}
<div class="eh-modal" id="ss-new" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ss-new-title" @if($openCreate) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('staff.support.store') }}">
    @csrf
    <div class="eh-modal-header">
        <div><h2 id="ss-new-title">New help request</h2><p>Describe the problem and the IT team will pick it up.</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any())<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ $errors->first() }}</span></div>@endif
        <div class="modal-grid">
            <div class="form-group full"><label for="ss-subject">Subject *</label><input id="ss-subject" name="subject" value="{{ old('subject') }}" required maxlength="255" placeholder="e.g. Laptop will not connect to the office Wi-Fi"></div>
            <div class="form-group"><label for="ss-category">Category *</label>
                <select id="ss-category" name="category" required>
                    <optgroup label="IT support">
                        @foreach($itCategories as $value)<option value="{{ $value }}" @selected(old('category', 'hardware') === $value)>{{ $categories[$value] }}</option>@endforeach
                    </optgroup>
                    <optgroup label="Other">
                        @foreach(['access', 'general'] as $value)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $categories[$value] }}</option>@endforeach
                    </optgroup>
                </select>
            </div>
            <div class="form-group"><label for="ss-priority">Priority *</label>
                <select id="ss-priority" name="priority" required>
                    @foreach($priorities as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="form-group full"><label for="ss-description">What is happening? *</label><textarea id="ss-description" name="description" rows="5" required maxlength="5000" placeholder="What were you doing, what did you expect, and any error message or asset tag.">{{ old('description') }}</textarea></div>
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send request</button>
    </div>
</form>
</div>
</div>

<style>
.ss-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start;margin-top:12px}
.ss-layout>*{min-width:0}
.ss-help{display:grid;gap:16px}
.ss-help .admin-panel,.ss-requests{margin:0}
.ss-panel-head h2{margin:0 0 10px;font-size:1rem;color:#172033}
.ss-list{display:grid;gap:10px;margin-top:12px}
.ss-ticket{border:1px solid #e4e7ec;border-left:3px solid #2e90fa;border-radius:10px;background:#fff;min-width:0}
.ss-ticket.ss-in_progress{border-left-color:#f79009}
.ss-ticket.ss-awaiting_requester{border-left-color:#7a5af8}
.ss-ticket.ss-resolved{border-left-color:#12b76a}
.ss-ticket summary{display:flex;align-items:center;gap:12px;padding:12px 14px;cursor:pointer;list-style:none}
.ss-ticket summary::-webkit-details-marker{display:none}
.ss-ticket summary:focus-visible{outline:2px solid #800000;outline-offset:2px;border-radius:10px}
.ss-ticket-icon{width:36px;height:36px;flex-shrink:0;display:grid;place-items:center;border-radius:10px;background:rgba(128,0,0,.08);color:#800000}
.ss-ticket-copy{flex:1;min-width:0;display:flex;flex-direction:column}
.ss-ticket-copy strong{color:#172033;overflow-wrap:anywhere}
.ss-ticket-copy small{color:#667085;font-size:.75rem}
.ss-ticket-chips{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px}
.ss-ticket-body{padding:0 14px 14px;border-top:1px solid #f2f4f7}
.ss-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px 16px;margin:12px 0}
.ss-meta dt{color:#667085;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
.ss-meta dd{margin:0;color:#172033;font-size:.85rem}
.ss-description{margin:0;padding:10px 12px;border-radius:8px;background:#f9fafb;color:#344054;font-size:.85rem;overflow-wrap:anywhere}
.ss-list .status-chip{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.7rem;font-weight:800;white-space:nowrap}
.ss-list .status-chip.open{background:#eff8ff;color:#175cd3}
.ss-list .status-chip.in_progress{background:#fffaeb;color:#b54708}
.ss-list .status-chip.awaiting_requester{background:#f4f3ff;color:#5925dc}
.ss-list .status-chip.resolved{background:#ecfdf3;color:#067647}
.ss-list .status-chip.ss-prio-high{background:#fef3f2;color:#b42318}
.ss-list .status-chip.ss-prio-urgent{background:#b42318;color:#fff}
.ss-contact{display:grid;gap:10px;margin:0;padding:0;list-style:none;font-size:.85rem;color:#344054}
.ss-contact li{display:flex;gap:10px;align-items:flex-start;overflow-wrap:anywhere}
.ss-contact i{width:16px;margin-top:3px;color:#800000}
.ss-technical{margin:12px 0 0;font-size:.82rem;color:#475467}
.ss-faqs details{border-bottom:1px solid #f2f4f7;padding:8px 0}
.ss-faqs details:last-child{border-bottom:0}
.ss-faqs summary{cursor:pointer;font-weight:700;font-size:.85rem;color:#172033}
.ss-faqs p{margin:6px 0 0;font-size:.82rem;color:#475467}
@media(max-width:1024px){.ss-layout{grid-template-columns:1fr}}
@media(max-width:560px){.ss-ticket summary{flex-wrap:wrap}.ss-ticket-chips{width:100%;justify-content:flex-start;padding-left:48px}}
</style>
@endsection
