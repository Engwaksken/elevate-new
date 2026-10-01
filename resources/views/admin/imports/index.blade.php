@extends('layouts.admin')
@section('title', 'Data Import Centre | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Reports</span>
        <h1>Data Import &amp; Migration Centre</h1>
        <p>Download a CSV template, fill it in, then upload CSV or Excel to preview it before import.</p>
    </div>
</div>

<div class="admin-panel" style="margin-bottom:16px">
    <h2 style="margin:0 0 12px;font-size:1rem">1. Download a template</h2>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach($modules as $m)
            <a class="btn btn-outline btn-sm" href="{{ route('admin.import-centre.template', $m) }}">
                <i class="fas fa-file-csv"></i> {{ ucwords(str_replace('_', ' ', $m)) }}
            </a>
        @endforeach
    </div>
</div>

<div class="admin-panel" style="margin-bottom:16px">
    <h2 style="margin:0 0 12px;font-size:1rem">2. Upload for preview</h2>

    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom:12px">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.import-centre.upload') }}">
        @csrf
        <div class="modal-grid">
            <div class="form-group">
                <label for="import-module">Module *</label>
                <select id="import-module" name="module" required>
                    <option value="">Select module</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" @selected(old('module') === $m)>{{ ucwords(str_replace('_', ' ', $m)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="import-file">File (CSV, XLS or XLSX, max 10 MB) *</label>
                <input id="import-file" type="file" name="file" accept=".csv,.xls,.xlsx" required>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:12px"><i class="fas fa-eye"></i> Preview Import</button>
    </form>
</div>

<div class="admin-panel">
    <h2 style="margin:0 0 12px;font-size:1rem">Recent imports</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>File</th><th>Module</th><th>Rows</th><th>Status</th><th>Uploaded</th><th class="table-actions">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($imports as $import)
                    <tr>
                        <td><strong>{{ $import->original_filename }}</strong></td>
                        <td>{{ ucwords(str_replace('_', ' ', $import->module)) }}</td>
                        <td>{{ number_format($import->total_rows) }}</td>
                        <td><span class="status-chip {{ $import->status }}">{{ ucwords(str_replace('_', ' ', $import->status)) }}</span></td>
                        <td>{{ $import->created_at?->format('d M Y H:i') }}</td>
                        <td class="table-actions"><a class="btn-icon" href="{{ route('admin.import-centre.preview', $import) }}" title="Preview"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty">No imports yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $imports->links() }}</div>
</div>
@endsection
