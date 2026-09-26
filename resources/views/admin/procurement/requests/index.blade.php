@extends('layouts.admin')
@section('title','Purchase Requests | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Procurement</span><h1>Purchase Requests</h1><p>Create and route procurement requests through approval stages.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createRequestModal"><i class="fas fa-plus"></i> New Request</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Requests','fa-file-circle-plus'],['draft','Draft','fa-pen'],['submitted','Submitted','fa-paper-plane'],['approved','Approved','fa-circle-check']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search request number, department, funding source..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['draft','submitted','manager_approved','finance_approved','procurement_review','approved','rejected','ordered','received'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.procurement.requests.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Request</th><th>Department</th><th>Required</th><th>Items</th><th>Total</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($requests as $pr)
<tr>
<td><strong>{{ $pr->request_number }}</strong><small class="admin-cell-hint">{{ $pr->funding_source ?: 'No funding source' }}</small></td>
<td>{{ $pr->department ?: '—' }}</td>
<td>{{ optional($pr->required_date)->format('d M Y') ?: '—' }}</td>
<td>{{ $pr->items->count() }}</td>
<td>{{ $pr->currency ?: 'UGX' }} {{ number_format((float)$pr->estimated_total,2) }}</td>
<td><span class="status-chip {{ $pr->status }}">{{ ucfirst(str_replace('_',' ',$pr->status)) }}</span></td>
<td class="table-actions"><div class="action-group">
@if($pr->status==='draft')<button type="button" class="btn-icon" title="Submit" data-modal-open="submitPR{{ $pr->id }}"><i class="fas fa-paper-plane"></i></button>@endif
@if(in_array($pr->status,['submitted','manager_approved','finance_approved','procurement_review'],true))<button type="button" class="btn-icon" title="Approval action" data-modal-open="approvePR{{ $pr->id }}"><i class="fas fa-check-double"></i></button>@endif
<a class="btn-icon" title="Quotations" href="{{ route('admin.procurement.quotations.index',$pr) }}"><i class="fas fa-file-invoice-dollar"></i></a>
</div></td>
</tr>
@if($pr->items->count())
<tr><td colspan="7"><details><summary><strong>View request items</strong></summary>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Item</th><th>Specification</th><th>Qty</th><th>Unit</th><th>Estimated Unit Cost</th><th>Total</th><th>Asset?</th></tr></thead><tbody>
@foreach($pr->items as $item)
<tr><td>{{ $item->item_name }}</td><td>{{ $item->specification ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit ?: '—' }}</td><td>{{ number_format((float)$item->estimated_unit_cost,2) }}</td><td>{{ number_format((float)$item->estimated_total,2) }}</td><td>{{ $item->is_asset ? 'Yes' : 'No' }}</td></tr>
@endforeach
</tbody></table></div>
</details></td></tr>
@endif
@empty<tr><td colspan="7"><div class="admin-empty">No purchase requests found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $requests->links() }}</div>
</div>

<div class="eh-modal" id="createRequestModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Purchase Request</h2><p>Add one or more requested items or services.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.requests.store') }}">@csrf
<div class="eh-modal-body">
<div class="modal-grid">
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Workplan</label><select name="workplan_id"><option value="">None</option>@foreach($workplans as $x)<option value="{{ $x->id }}">{{ $x->title }}</option>@endforeach</select></div>
<div class="form-group"><label>Activity</label><select name="activity_id"><option value="">None</option>@foreach($activities as $x)<option value="{{ $x->id }}">{{ $x->title }}</option>@endforeach</select></div>
<div class="form-group"><label>Department</label><input name="department"></div>
<div class="form-group"><label>Required Date</label><input type="date" name="required_date"></div>
<div class="form-group"><label>Funding Source</label><input name="funding_source"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Justification</label><textarea name="justification"></textarea></div>
</div>

<div class="admin-panel-head" style="margin-top:14px"><div><h2>Items</h2><p>Add all items required under this request.</p></div><button type="button" class="btn btn-outline btn-sm" id="addProcurementItem"><i class="fas fa-plus"></i> Add Item</button></div>
<div id="procurementItems">
<div class="procurement-item-row" data-item-index="0">
<div class="modal-grid">
<div class="form-group"><label>Item / Service *</label><input name="items[0][item_name]" required></div>
<div class="form-group"><label>Quantity *</label><input type="number" step=".01" min=".01" name="items[0][quantity]" value="1" required></div>
<div class="form-group"><label>Unit</label><input name="items[0][unit]"></div>
<div class="form-group"><label>Estimated Unit Cost</label><input type="number" step=".01" min="0" name="items[0][estimated_unit_cost]"></div>
<div class="form-group full"><label>Specification</label><textarea name="items[0][specification]"></textarea></div>
<div class="form-group full"><label class="modal-check"><input type="checkbox" name="items[0][is_asset]" value="1"><span>Capital asset</span></label></div>
</div></div>
</div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Request</button></div>
</form></div></div>

@foreach($requests as $pr)
@if($pr->status==='draft')
<div class="eh-modal" id="submitPR{{ $pr->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Submit Request?</h2><p>{{ $pr->request_number }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Submit this purchase request for approval?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.procurement.requests.submit',$pr) }}">@csrf<button class="btn btn-primary">Submit</button></form></div></div></div>
@endif

@if(in_array($pr->status,['submitted','manager_approved','finance_approved','procurement_review'],true))
<div class="eh-modal" id="approvePR{{ $pr->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Approval Action</h2><p>{{ $pr->request_number }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.requests.approve',$pr) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Approval Stage *</label><select name="approval_stage">@foreach(['manager','finance','procurement','final'] as $x)<option value="{{ $x }}">{{ ucfirst($x) }}</option>@endforeach</select></div>
<div class="form-group"><label>Decision *</label><select name="decision"><option value="approved">Approve</option><option value="returned">Return</option><option value="rejected">Reject</option></select></div>
<div class="form-group full"><label>Comments</label><textarea name="comments"></textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Record Decision</button></div>
</form></div></div>
@endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addButton=document.getElementById('addProcurementItem');
    const container=document.getElementById('procurementItems');
    if(!addButton || !container) return;
    let index=1;
    addButton.addEventListener('click', function(){
        const row=document.createElement('div');
        row.className='procurement-item-row';
        row.innerHTML=`
            <div class="modal-grid">
                <div class="form-group"><label>Item / Service *</label><input name="items[${index}][item_name]" required></div>
                <div class="form-group"><label>Quantity *</label><input type="number" step=".01" min=".01" name="items[${index}][quantity]" value="1" required></div>
                <div class="form-group"><label>Unit</label><input name="items[${index}][unit]"></div>
                <div class="form-group"><label>Estimated Unit Cost</label><input type="number" step=".01" min="0" name="items[${index}][estimated_unit_cost]"></div>
                <div class="form-group full"><label>Specification</label><textarea name="items[${index}][specification]"></textarea></div>
                <div class="form-group"><label class="modal-check"><input type="checkbox" name="items[${index}][is_asset]" value="1"><span>Capital asset</span></label></div>
                <div class="form-group"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i> Remove</button></div>
            </div>`;
        container.appendChild(row);
        index++;
    });
    container.addEventListener('click', function(e){
        const button=e.target.closest('.remove-item');
        if(button) button.closest('.procurement-item-row')?.remove();
    });
});
</script>
@endsection
