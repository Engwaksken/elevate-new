@extends('layouts.admin')
@section('title', 'Import Preview | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Data Import Centre</span>
        <h1>Import Preview</h1>
        <p>{{ $import->original_filename }} · {{ ucwords(str_replace('_', ' ', $import->module)) }}</p>
    </div>
    <div class="admin-page-actions">
        <a class="btn btn-outline" href="{{ route('admin.import-centre.index') }}"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach([
        ['Data Rows', number_format($import->total_rows), 'fa-table-list'],
        ['Columns', number_format(count($headers)), 'fa-table-columns'],
        ['Showing', number_format(count($rows)), 'fa-eye'],
        ['Status', ucwords(str_replace('_', ' ', $import->status)), 'fa-circle-info'],
    ] as [$label, $value, $icon])
        <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></div>
    @endforeach
</div>

<div class="alert alert-info" style="margin-bottom:16px">No rows are inserted at this stage. Check the columns and values below; only the first 25 rows are shown.</div>

<div class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>@foreach($headers as $i => $h)<td>{{ $row[$i] ?? '' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ max(1, count($headers)) }}"><div class="admin-empty">The file has a header row but no data rows.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
