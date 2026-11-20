@extends('layouts.admin')
@section('title',$policy->title.' | Policies')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('hr.manage');
    $pending = $policy->assignments->whereNull('acknowledged_at');
@endphp
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('admin.hr.policies.index') }}">Policies</a></span>
        <h1>{{ $policy->title }}</h1>
        <p>{{ collect([$policy->category, $policy->version ? 'v'.$policy->version : null, $policy->effective_date ? 'Effective '.$policy->effective_date->format('d M Y') : null])->filter()->join(' · ') }}
            <span class="status-chip {{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Archived' }}</span></p>
    </div>
    <div class="admin-page-actions">
        @if($policy->file_path)<a href="{{ route('admin.hr.policies.file', $policy) }}" class="btn btn-outline"><i class="fas fa-file-arrow-down" aria-hidden="true"></i> Document</a>@endif
        @if($canManage)
            <button type="button" class="btn btn-outline" data-modal-open="editPolicy">Edit</button>
            <button type="button" class="btn btn-primary" data-modal-open="sharePolicy"><i class="fas fa-share-nodes" aria-hidden="true"></i> Share with staff</button>
        @endif
    </div>
</div>
@include('admin.hr.partials.nav')

<div class="admin-stats-grid compact">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-users" aria-hidden="true"></i></span><div><small>Shared with</small><strong>{{ $policy->assignments->count() }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></span><div><small>Acknowledged</small><strong>{{ $policy->assignments->whereNotNull('acknowledged_at')->count() }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></span><div><small>Outstanding</small><strong>{{ $policy->requires_acknowledgement ? $pending->count() : 0 }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span><div><small>Overdue</small><strong>{{ $policy->requires_acknowledgement ? $pending->filter(fn($a) => $a->due_date && $a->due_date->isPast())->count() : 0 }}</strong></div></div>
</div>

<div class="admin-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h2 style="margin:0;font-size:1.05rem">Acknowledgement tracking</h2>
        @if($canManage && $policy->requires_acknowledgement && $pending->isNotEmpty())
            <form method="POST" action="{{ route('admin.hr.policies.remind', $policy) }}">@csrf<button class="btn btn-outline btn-sm"><i class="fas fa-bell" aria-hidden="true"></i> Remind {{ $pending->count() }} outstanding</button></form>
        @endif
    </div>
    <div class="admin-table-wrap" style="margin-top:10px"><table class="admin-table">
        <thead><tr><th>Staff member</th><th>Shared</th><th>Due</th><th>Opened</th><th>Acknowledged</th><th>Reminders</th></tr></thead>
        <tbody>
        @forelse($policy->assignments as $a)
            <tr>
                <td><strong>{{ $a->user?->name ?? 'Removed user' }}</strong><small class="admin-cell-hint">{{ $a->user?->email }}</small></td>
                <td>{{ $a->created_at->format('d M Y') }}</td>
                <td>{{ optional($a->due_date)->format('d M Y') ?: '-' }}@if(! $a->acknowledged_at && $a->due_date?->isPast())<small class="admin-cell-hint" style="color:#b42318">overdue</small>@endif</td>
                <td>{{ optional($a->viewed_at)->format('d M Y H:i') ?: '-' }}</td>
                <td>@if($a->acknowledged_at)<span class="status-chip active">{{ $a->acknowledged_at->format('d M Y') }}</span>@else<span class="status-chip draft">Pending</span>@endif</td>
                <td>{{ $a->reminders_sent }}@if($a->last_reminded_at)<small class="admin-cell-hint">last {{ $a->last_reminded_at->diffForHumans() }}</small>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="admin-empty">Not shared with anyone yet.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@if($policy->body)
<div class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Policy text</h2><div style="white-space:pre-line">{{ $policy->body }}</div></div>
@endif

@if($canManage)
<div class="eh-modal" id="sharePolicy" aria-hidden="true"><div class="eh-modal-dialog">
    <div class="eh-modal-header"><div><h2>Share with staff</h2><p>People who already have this policy are skipped.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.policies.assign', $policy) }}">@csrf
        <div class="eh-modal-body">@include('admin.hr.policies._share', ['prefix' => 'sh', 'allowNone' => false])</div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Share</button></div>
    </form>
</div></div>

<div class="eh-modal" id="editPolicy" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Edit policy</h2></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.policies.update', $policy) }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="eh-modal-body">@include('admin.hr.policies._fields', ['policy' => $policy])</div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
</div></div>
@endif
@endsection
