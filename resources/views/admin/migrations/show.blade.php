@extends('layouts.admin')
@section('title',$batch->batch_name.' | Data Migration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Data Migration Review</span>
    <h1>{{ $batch->batch_name }}</h1>
    <p>{{ ucfirst($batch->source_system) }} · {{ number_format($batch->total_rows) }} staged records · {{ $batch->source_file ?: 'No source filename' }}</p>
</div>
<div class="admin-page-actions">
    <a href="{{ route('admin.migrations.index') }}" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Migration Batches
    </a>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Staged Records','fa-table-list'],
['matched','Matched','fa-link'],
['ambiguous','Ambiguous','fa-code-branch'],
['unmatched','Unmatched','fa-link-slash']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search source ID, name, email or phone...">
    </div>

    <select name="match_status">
        <option value="">All match statuses</option>
        @foreach(['matched','ambiguous','unmatched'] as $status)
        <option value="{{ $status }}" @selected(request('match_status')===$status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <select name="per_page">
        @foreach([25,50,100,200] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',50)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.migrations.show',$batch) }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="migration-review-note">
    <i class="fas fa-circle-info"></i>
    <div>
        <strong>Review-only staging</strong>
        <span>Records shown here are staged for identity review. This page does not automatically overwrite live participant data.</span>
    </div>
</div>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Source ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Match</th>
    <th>Matched User</th>
    <th class="table-actions">Payload</th>
</tr>
</thead>
<tbody>
@forelse($records as $record)
<tr>
    <td>{{ $record->source_record_id ?: '—' }}</td>
    <td>{{ data_get($record->source_payload,'name') ?: data_get($record->source_payload,'full_name') ?: '—' }}</td>
    <td>{{ data_get($record->source_payload,'email') ?: '—' }}</td>
    <td>{{ data_get($record->source_payload,'phone') ?: data_get($record->source_payload,'contact') ?: '—' }}</td>
    <td>
        <span class="status-chip {{ $record->match_status ?: 'pending' }}">
            {{ ucfirst($record->match_status ?: 'Pending') }}
        </span>
    </td>
    <td>
        @if($record->matchedUser)
            <strong>{{ $record->matchedUser->name }}</strong>
            <small class="admin-cell-hint">{{ $record->matchedUser->email }}</small>
        @else
            —
        @endif
    </td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="payload{{ $record->id }}">
            <i class="fas fa-code"></i> View
        </button>
    </td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty">No staged records match the current filters.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $records->links() }}</div>
</div>

@foreach($records as $record)
<div class="eh-modal" id="payload{{ $record->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Staged Payload</h2><p>Record #{{ $record->id }} · {{ ucfirst($record->match_status ?: 'pending') }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body">
    <pre class="migration-json">{{ json_encode($record->source_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Close</button>
</div>
</div>
</div>
@endforeach

@endsection
