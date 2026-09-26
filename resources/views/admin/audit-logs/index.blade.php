@extends('layouts.admin')
@section('title','Audit Logs | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">System Administration</span>
    <h1>Audit Logs & Activity History</h1>
    <p>Review recorded user actions, affected records, IP addresses and before/after values.</p>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Events','fa-clock-rotate-left'],
['today','Events Today','fa-calendar-day'],
['users','Users Recorded','fa-users'],
['modules','Modules','fa-layer-group']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search user, module, action, model or IP...">
    </div>

    <select name="module">
        <option value="">All modules</option>
        @foreach($modules as $module)
        <option value="{{ $module }}" @selected(request('module')===$module)>{{ $module }}</option>
        @endforeach
    </select>

    <select name="action">
        <option value="">All actions</option>
        @foreach($actions as $action)
        <option value="{{ $action }}" @selected(request('action')===$action)>{{ ucfirst(str_replace('_',' ',$action)) }}</option>
        @endforeach
    </select>

    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">

    <select name="per_page">
        @foreach([10,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Time</th>
    <th>User</th>
    <th>Module</th>
    <th>Action</th>
    <th>Record</th>
    <th>IP Address</th>
    <th class="table-actions">Details</th>
</tr>
</thead>
<tbody>
@forelse($logs as $log)
<tr>
    <td>{{ optional($log->occurred_at)->format('d M Y H:i:s') ?: '—' }}</td>
    <td>
        <strong>{{ data_get($log,'user.name','System') }}</strong>
        <small class="admin-cell-hint">{{ data_get($log,'user.email','') }}</small>
    </td>
    <td>{{ $log->module ?: '—' }}</td>
    <td>{{ ucfirst(str_replace('_',' ',$log->action)) }}</td>
    <td>
        {{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}
        @if($log->auditable_id)<small class="admin-cell-hint">#{{ $log->auditable_id }}</small>@endif
    </td>
    <td>{{ $log->ip_address ?: '—' }}</td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="audit{{ $log->id }}">
            <i class="fas fa-eye"></i> View
        </button>
    </td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty">No audit events found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $logs->links() }}</div>
</div>

@foreach($logs as $log)
<div class="eh-modal" id="audit{{ $log->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Audit Event #{{ $log->id }}</h2><p>{{ ucfirst(str_replace('_',' ',$log->action)) }} · {{ $log->module ?: 'Uncategorised' }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group"><label>User</label><div class="admin-readonly">{{ data_get($log,'user.name','System') }}</div></div>
    <div class="form-group"><label>Occurred At</label><div class="admin-readonly">{{ optional($log->occurred_at)->format('d M Y H:i:s') ?: '—' }}</div></div>
    <div class="form-group"><label>IP Address</label><div class="admin-readonly">{{ $log->ip_address ?: '—' }}</div></div>
    <div class="form-group"><label>Record</label><div class="admin-readonly">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</div></div>
    <div class="form-group full"><label>Old Values</label><pre class="admin-code-block">{{ json_encode($log->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre></div>
    <div class="form-group full"><label>New Values</label><pre class="admin-code-block">{{ json_encode($log->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre></div>
    <div class="form-group full"><label>User Agent</label><div class="admin-readonly">{{ $log->user_agent ?: '—' }}</div></div>
</div>
</div>

<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Close</button></div>
</div>
</div>
@endforeach
@endsection
