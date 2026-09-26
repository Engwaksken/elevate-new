@extends('layouts.admin')
@section('title','Data Migrations | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Assets & Operations</span>
    <h1>Data Migrations</h1>
    <p>Stage legacy CSV data, review identity matching and resolve migration quality before processing.</p>
</div>
<div class="admin-page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="newMigrationModal">
        <i class="fas fa-file-import"></i> New Migration Batch
    </button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Batches','fa-layer-group'],
['validated','Validated','fa-circle-check'],
['completed','Completed','fa-flag-checkered'],
['failed','Failed','fa-triangle-exclamation']
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
        <input name="search" value="{{ request('search') }}" placeholder="Search batch, source or file...">
    </div>

    <select name="source_system">
        <option value="">All source systems</option>
        @foreach(['elearning','mentorship','jobs','library','other'] as $source)
        <option value="{{ $source }}" @selected(request('source_system')===$source)>{{ ucfirst($source) }}</option>
        @endforeach
    </select>

    <select name="status">
        <option value="">All statuses</option>
        @foreach(['draft','validated','processing','completed','failed'] as $status)
        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <select name="per_page">
        @foreach([10,20,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.migrations.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Batch</th>
    <th>Source</th>
    <th>File</th>
    <th>Rows</th>
    <th>Successful</th>
    <th>Failed</th>
    <th>Status</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($batches as $batch)
<tr>
    <td><strong>{{ $batch->batch_name }}</strong><small class="admin-cell-hint">#{{ $batch->id }}</small></td>
    <td>{{ ucfirst($batch->source_system) }}</td>
    <td>{{ $batch->source_file ?: '—' }}</td>
    <td>{{ number_format($batch->total_rows) }}</td>
    <td>{{ number_format($batch->successful_rows) }}</td>
    <td>{{ number_format($batch->failed_rows) }}</td>
    <td><span class="status-chip {{ $batch->status }}">{{ ucfirst($batch->status) }}</span></td>
    <td class="table-actions">
        <a href="{{ route('admin.migrations.show',$batch) }}" class="btn btn-outline btn-sm">
            <i class="fas fa-eye"></i> Review
        </a>
    </td>
</tr>
@empty
<tr><td colspan="8"><div class="admin-empty">No migration batches found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $batches->links() }}</div>
</div>

<div class="eh-modal" id="newMigrationModal" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div>
        <h2>New Migration Batch</h2>
        <p>Upload a legacy CSV for staging and identity review. This does not write directly into live programme records.</p>
    </div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.migrations.store') }}" enctype="multipart/form-data">
@csrf
<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group">
        <label>Source System *</label>
        <select name="source_system" required>
            <option value="">Select source</option>
            @foreach(['elearning','mentorship','jobs','library','other'] as $source)
            <option value="{{ $source }}" @selected(old('source_system')===$source)>{{ ucfirst($source) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Batch Name *</label>
        <input name="batch_name" value="{{ old('batch_name') }}" required placeholder="e.g. Legacy eLearning participants – Sept 2026">
    </div>

    <div class="form-group full">
        <label>CSV File *</label>
        <input type="file" name="payload" accept=".csv,.txt,text/csv,text/plain" required>
        <small class="form-hint">
            Maximum 10 MB. The first row must contain unique column headers. Headers are normalised automatically.
        </small>
    </div>

    <div class="form-group full">
        <div class="migration-guidance">
            <strong>Recommended identity columns</strong>
            <span>email, phone, name, id/source_id/unique_id</span>
            <small>The existing identity matcher uses the staged payload to identify possible existing users.</small>
        </div>
    </div>
</div>
</div>

<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary"><i class="fas fa-upload"></i> Stage CSV</button>
</div>
</form>
</div>
</div>

@if($errors->any() || session('open_migration_modal'))
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelector('[data-modal-open="newMigrationModal"]')?.click();
});
</script>
@endif

@endsection
