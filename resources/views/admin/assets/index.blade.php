@extends('layouts.admin')
@section('title','Assets | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Assets & Operations</span>
        <h1>Asset Register</h1>
        <p>Register, assign, return, maintain and dispose organisational assets.</p>
    </div>
    <div class="admin-page-actions">
        <button type="button" class="btn btn-primary" data-modal-open="createAssetModal">
            <i class="fas fa-plus"></i> Register Asset
        </button>
    </div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Assets','fa-boxes-stacked'],
['available','Available','fa-box-open'],
['assigned','Assigned','fa-user-tag'],
['maintenance','Under Maintenance','fa-screwdriver-wrench']
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
        <input name="search" value="{{ request('search') }}" placeholder="Search code, tag, serial, description, brand, model or location...">
    </div>

    <select name="status">
        <option value="">All statuses</option>
        @foreach(['available','assigned','under_maintenance','disposed'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
        @endforeach
    </select>

    <select name="per_page">
        @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
    <a href="{{ route('admin.assets.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Asset</th>
    <th>Tag / Serial</th>
    <th>Location</th>
    <th>Condition</th>
    <th>Purchase Value</th>
    <th>Status</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($assets as $asset)
<tr>
    <td>
        <strong>{{ $asset->asset_code }}</strong>
        <small class="admin-cell-hint">{{ $asset->description }}</small>
    </td>
    <td>
        {{ $asset->asset_tag ?: '—' }}
        <small class="admin-cell-hint">{{ $asset->serial_number ?: 'No serial number' }}</small>
    </td>
    <td>{{ $asset->location ?: '—' }}</td>
    <td>{{ $asset->condition ?: '—' }}</td>
    <td>{{ $asset->currency ?: 'UGX' }} {{ number_format((float)$asset->purchase_price,2) }}</td>
    <td><span class="status-chip {{ $asset->status }}">{{ ucfirst(str_replace('_',' ',$asset->status)) }}</span></td>
    <td class="table-actions">
        <div class="action-group">
            @if(in_array($asset->status,['available','in_use'],true))
            <button type="button" class="btn-icon" data-modal-open="assignAsset{{ $asset->id }}" title="Assign asset">
                <i class="fas fa-user-plus"></i>
            </button>
            @endif

            @php($activeAssignment=$asset->assignments->firstWhere('status','active'))
            @if($activeAssignment)
            <button type="button" class="btn-icon" data-modal-open="returnAsset{{ $activeAssignment->id }}" title="Return asset">
                <i class="fas fa-rotate-left"></i>
            </button>
            @endif

            @if($asset->status!=='disposed')
            <button type="button" class="btn-icon" data-modal-open="maintenanceAsset{{ $asset->id }}" title="Maintenance">
                <i class="fas fa-screwdriver-wrench"></i>
            </button>
            @endif

            @if(!$asset->disposal && $asset->status!=='disposed')
            <button type="button" class="btn-icon danger" data-modal-open="disposeAsset{{ $asset->id }}" title="Request disposal">
                <i class="fas fa-trash-can"></i>
            </button>
            @elseif($asset->disposal && $asset->disposal->status==='requested')
            <button type="button" class="btn-icon" data-modal-open="approveDisposal{{ $asset->id }}" title="Approve disposal">
                <i class="fas fa-check"></i>
            </button>
            @elseif($asset->disposal && $asset->disposal->status==='approved')
            <button type="button" class="btn-icon danger" data-modal-open="completeDisposal{{ $asset->id }}" title="Complete disposal">
                <i class="fas fa-ban"></i>
            </button>
            @endif
        </div>
    </td>
</tr>

@if($asset->assignments->count() || $asset->maintenance->count() || $asset->disposal)
<tr>
<td colspan="7">
<details>
<summary><strong>View asset history</strong></summary>

<div class="admin-tabs" data-admin-tabs style="margin-top:10px">
    <button type="button" class="admin-tab active" data-admin-tab="assignments{{ $asset->id }}">Assignments</button>
    <button type="button" class="admin-tab" data-admin-tab="maintenance{{ $asset->id }}">Maintenance</button>
    <button type="button" class="admin-tab" data-admin-tab="disposal{{ $asset->id }}">Disposal</button>
</div>

<div class="admin-tab-pane active" data-admin-pane="assignments{{ $asset->id }}">
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Assigned To</th><th>Assigned</th><th>Expected Return</th><th>Returned</th><th>Status</th></tr></thead>
<tbody>
@forelse($asset->assignments as $assignment)
<tr>
<td>{{ optional($users->firstWhere('id',$assignment->assigned_to_user_id))->name ?: ('User #'.$assignment->assigned_to_user_id) }}</td>
<td>{{ optional($assignment->assigned_date)->format('d M Y') ?: '—' }}</td>
<td>{{ optional($assignment->expected_return_date)->format('d M Y') ?: '—' }}</td>
<td>{{ optional($assignment->returned_date)->format('d M Y') ?: '—' }}</td>
<td>{{ ucfirst($assignment->status) }}</td>
</tr>
@empty<tr><td colspan="5">No assignment history.</td></tr>@endforelse
</tbody>
</table>
</div>
</div>

<div class="admin-tab-pane" data-admin-pane="maintenance{{ $asset->id }}">
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Reported</th><th>Type</th><th>Issue</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
@forelse($asset->maintenance as $maintenance)
<tr>
<td>{{ optional($maintenance->reported_date)->format('d M Y') ?: '—' }}</td>
<td>{{ $maintenance->maintenance_type ?: '—' }}</td>
<td>{{ Str::limit($maintenance->issue_description,80) }}</td>
<td>{{ $maintenance->currency ?: 'UGX' }} {{ number_format((float)$maintenance->cost,2) }}</td>
<td>{{ ucfirst($maintenance->status ?: 'open') }}</td>
<td>
@if(($maintenance->status ?? 'open')!=='completed')
<button type="button" class="btn btn-outline btn-sm" data-modal-open="completeMaintenance{{ $maintenance->id }}">Complete</button>
@endif
</td>
</tr>
@empty<tr><td colspan="6">No maintenance history.</td></tr>@endforelse
</tbody>
</table>
</div>
</div>

<div class="admin-tab-pane" data-admin-pane="disposal{{ $asset->id }}">
@if($asset->disposal)
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Requested</th><th>Method</th><th>Value</th><th>Status</th><th>Reason</th></tr></thead>
<tbody><tr>
<td>{{ optional($asset->disposal->requested_date)->format('d M Y') ?: '—' }}</td>
<td>{{ $asset->disposal->disposal_method ?: '—' }}</td>
<td>{{ $asset->disposal->currency ?: 'UGX' }} {{ number_format((float)$asset->disposal->disposal_value,2) }}</td>
<td>{{ ucfirst($asset->disposal->status) }}</td>
<td>{{ $asset->disposal->reason }}</td>
</tr></tbody>
</table>
</div>
@else
<div class="admin-empty">No disposal process started.</div>
@endif
</div>
</details>
</td>
</tr>
@endif

@empty
<tr><td colspan="7"><div class="admin-empty">No assets found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $assets->links() }}</div>
</div>

<div class="eh-modal" id="createAssetModal" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Register Asset</h2><p>Create a new asset register record.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Asset Tag</label><input name="asset_tag"></div>
<div class="form-group"><label>Category</label><select name="asset_category_id"><option value="">None</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="form-group full"><label>Description *</label><input name="description" required></div>
<div class="form-group"><label>Brand</label><input name="brand"></div>
<div class="form-group"><label>Model</label><input name="model"></div>
<div class="form-group"><label>Serial Number</label><input name="serial_number"></div>
<div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date"></div>
<div class="form-group"><label>Purchase Price</label><input type="number" min="0" step=".01" name="purchase_price"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group"><label>Supplier</label><select name="supplier_id"><option value="">None</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Funding Source</label><input name="funding_source"></div>
<div class="form-group"><label>Location</label><input name="location"></div>
<div class="form-group"><label>Condition</label><input name="condition" value="good"></div>
<div class="form-group"><label>Warranty End Date</label><input type="date" name="warranty_end_date"></div>
<div class="form-group full"><label>Notes</label><textarea name="notes"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Register Asset</button></div>
</form>
</div>
</div>

@foreach($assets as $asset)
@if(in_array($asset->status,['available','in_use'],true))
<div class="eh-modal" id="assignAsset{{ $asset->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Assign Asset</h2><p>{{ $asset->asset_code }} — {{ $asset->description }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.assign',$asset) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Assign To *</label><select name="assigned_to_user_id" required><option value="">Select staff user</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>@endforeach</select></div>
<div class="form-group"><label>Assigned Date *</label><input type="date" name="assigned_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Expected Return Date</label><input type="date" name="expected_return_date"></div>
<div class="form-group full"><label>Assignment Notes</label><textarea name="assignment_notes"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Assign Asset</button></div>
</form>
</div>
</div>
@endif

@php($activeAssignment=$asset->assignments->firstWhere('status','active'))
@if($activeAssignment)
<div class="eh-modal" id="returnAsset{{ $activeAssignment->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Return Asset</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.return',$activeAssignment) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Returned Date *</label><input type="date" name="returned_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Return Condition</label><input name="return_condition" value="{{ $asset->condition }}"></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Confirm Return</button></div>
</form>
</div>
</div>
@endif

@if($asset->status!=='disposed')
<div class="eh-modal" id="maintenanceAsset{{ $asset->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Record Maintenance</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.maintenance.store',$asset) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Reported Date *</label><input type="date" name="reported_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Maintenance Type</label><input name="maintenance_type" placeholder="Repair, service, inspection..."></div>
<div class="form-group"><label>Supplier</label><select name="supplier_id"><option value="">None</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Cost</label><input type="number" step=".01" min="0" name="cost"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Issue Description</label><textarea name="issue_description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Record Maintenance</button></div>
</form>
</div>
</div>
@endif

@foreach($asset->maintenance as $maintenance)
@if(($maintenance->status ?? 'open')!=='completed')
<div class="eh-modal" id="completeMaintenance{{ $maintenance->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Complete Maintenance</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.maintenance.complete',$maintenance) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Completed Date *</label><input type="date" name="completed_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group full"><label>Resolution</label><textarea name="resolution"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Complete Maintenance</button></div>
</form>
</div>
</div>
@endif
@endforeach

@if(!$asset->disposal && $asset->status!=='disposed')
<div class="eh-modal" id="disposeAsset{{ $asset->id }}" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Request Asset Disposal</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.assets.disposal.request',$asset) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Requested Date *</label><input type="date" name="requested_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Disposal Method</label><input name="disposal_method" placeholder="Sale, donation, write-off..."></div>
<div class="form-group"><label>Disposal Value</label><input type="number" step=".01" min="0" name="disposal_value"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Reason *</label><textarea name="reason" required></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-danger">Request Disposal</button></div>
</form>
</div>
</div>
@endif

@if($asset->disposal && $asset->disposal->status==='requested')
<div class="eh-modal" id="approveDisposal{{ $asset->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Approve Disposal?</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Approve the disposal request for this asset?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.assets.disposal.approve',$asset) }}">@csrf<button class="btn btn-primary">Approve Disposal</button></form></div>
</div>
</div>
@endif

@if($asset->disposal && $asset->disposal->status==='approved')
<div class="eh-modal" id="completeDisposal{{ $asset->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Complete Disposal?</h2><p>{{ $asset->asset_code }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>This marks the asset as disposed and removes it from active use.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.assets.disposal.complete',$asset) }}">@csrf<button class="btn btn-danger">Complete Disposal</button></form></div>
</div>
</div>
@endif
@endforeach

@endsection
