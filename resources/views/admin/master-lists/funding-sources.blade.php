@extends('layouts.admin')
@section('title','Funding Sources | ElevateHer360 Administration')
@section('content')
@php
    $bulkRoute = route('admin.funding-sources.bulk-destroy');
    $bulkTableId = 'fundingSourcesTable';
    $blank = new \App\Models\FundingSource(['is_active' => true]);
@endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Administration</span><h1>Funding Sources</h1><p>The funding sources staff choose from on purchase requests, assets and workplan activities.</p></div>
    <div class="admin-page-actions"><x-export-buttons /><button type="button" class="btn btn-primary" data-modal-open="fundingSourceCreate"><i class="fas fa-plus"></i> Add Funding Source</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Funding Sources','fa-hand-holding-dollar'],['active','Active','fa-circle-check'],['inactive','Inactive','fa-circle-pause']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" role="search">
    <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search name, code or description..." aria-label="Search funding sources"></div>
    <select name="status" aria-label="Status"><option value="">All statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select>
    <select name="per_page" aria-label="Rows per page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
    <button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.funding-sources.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap"><table class="admin-table" id="{{ $bulkTableId }}">
<thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Funding source</th><th>Description</th><th>Used by</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($records as $source)
<tr>
    <td>@if($source->usage_count > 0)<input type="checkbox" disabled aria-label="{{ $source->name }} is in use and cannot be deleted" title="In use — cannot be deleted">@else<input type="checkbox" data-row-select value="{{ $source->id }}" aria-label="Select {{ $source->name }}">@endif</td>
    <td><strong>{{ $source->name }}</strong><small class="admin-cell-hint">{{ $source->code ?: 'No code' }}</small></td>
    <td>{{ $source->description ? \Illuminate\Support\Str::limit($source->description, 90) : '—' }}</td>
    <td>{{ $source->usage_count ? number_format($source->usage_count).' record(s)' : 'Not used yet' }}</td>
    <td><span class="status-chip {{ $source->is_active ? 'active' : 'inactive' }}">{{ $source->is_active ? 'Active' : 'Inactive' }}</span></td>
    <td class="table-actions"><div class="action-group">
        <button class="btn-icon" type="button" data-modal-open="fundingSource{{ $source->id }}" aria-label="Edit {{ $source->name }}" title="Edit"><i class="fas fa-pen"></i></button>
        <form method="POST" action="{{ route('admin.funding-sources.toggle', $source) }}">@csrf @method('PATCH')
            <button class="btn-icon" type="submit" aria-label="{{ $source->is_active ? 'Deactivate' : 'Activate' }} {{ $source->name }}" title="{{ $source->is_active ? 'Deactivate' : 'Activate' }}"><i class="fas {{ $source->is_active ? 'fa-circle-pause' : 'fa-circle-play' }}"></i></button>
        </form>
        @if($source->usage_count > 0)
        <button class="btn-icon danger" type="button" disabled aria-label="{{ $source->name }} is in use and cannot be deleted" title="In use — deactivate instead"><i class="fas fa-trash"></i></button>
        @else
        <button class="btn-icon danger" type="button" data-modal-open="fundingSourceDelete{{ $source->id }}" aria-label="Delete {{ $source->name }}" title="Delete"><i class="fas fa-trash"></i></button>
        @endif
    </div></td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No funding sources found. Use “Add Funding Source” to create one.</div></td></tr>
@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $records->links() }}</div>
</div>

@include('admin.master-lists.funding-source-modal', ['id' => 'fundingSourceCreate', 'title' => 'Add Funding Source', 'source' => $blank, 'action' => route('admin.funding-sources.store'), 'method' => 'POST'])
@foreach($records as $source)
@include('admin.master-lists.funding-source-modal', ['id' => 'fundingSource'.$source->id, 'title' => 'Edit Funding Source', 'source' => $source, 'action' => route('admin.funding-sources.update', $source), 'method' => 'PUT'])
@if(! $source->usage_count)
<div class="eh-modal" id="fundingSourceDelete{{ $source->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="fundingSourceDelete{{ $source->id }}-title"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2 id="fundingSourceDelete{{ $source->id }}-title">Delete Funding Source?</h2><p>{{ $source->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>No purchase requests, assets or activities use this funding source. Delete it permanently?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.funding-sources.destroy', $source) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div>
</div></div>
@endif
@endforeach
@endsection
