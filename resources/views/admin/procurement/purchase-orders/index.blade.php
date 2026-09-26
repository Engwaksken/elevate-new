@extends('layouts.admin')
@section('title','Purchase Orders | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Procurement</span><h1>Purchase Orders</h1><p>Create purchase orders from approved requests and record goods received.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createPOModal"><i class="fas fa-plus"></i> New Purchase Order</button></div>
</div>

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-invoice"></i></span><div><small>Total Orders</small><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-paper-plane"></i></span><div><small>Issued</small><strong>{{ number_format($stats['issued'] ?? 0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-box-open"></i></span><div><small>Received</small><strong>{{ number_format($stats['received'] ?? 0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Total Value</small><strong style="font-size:.8rem">{{ number_format((float)($stats['value'] ?? 0),0) }}</strong></div></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search PO number or supplier..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['issued','received','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.procurement.purchase-orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>PO</th><th>Supplier</th><th>Order Date</th><th>Expected</th><th>Total</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($orders as $po)
<tr>
<td><strong>{{ $po->po_number }}</strong></td>
<td>{{ data_get($po,'supplier.name','—') }}</td>
<td>{{ optional($po->order_date)->format('d M Y') ?: '—' }}</td>
<td>{{ optional($po->expected_delivery_date)->format('d M Y') ?: '—' }}</td>
<td>{{ $po->currency }} {{ number_format((float)$po->total_amount,2) }}</td>
<td><span class="status-chip {{ $po->status }}">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span></td>
<td class="table-actions">@if($po->status==='issued')<button type="button" class="btn btn-outline btn-sm" data-modal-open="receivePO{{ $po->id }}"><i class="fas fa-box-open"></i> Receive</button>@endif</td>
</tr>
@if($po->items->count())
<tr><td colspan="7"><details><summary><strong>View PO items</strong></summary>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Item</th><th>Specification</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Asset?</th></tr></thead><tbody>
@foreach($po->items as $item)
<tr><td>{{ $item->item_name }}</td><td>{{ $item->specification ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2) }}</td><td>{{ number_format((float)$item->line_total,2) }}</td><td>{{ $item->is_asset ? 'Yes' : 'No' }}</td></tr>
@endforeach
</tbody></table></div>
</details></td></tr>
@endif
@empty<tr><td colspan="7"><div class="admin-empty">No purchase orders found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $orders->links() }}</div>
</div>

<div class="eh-modal" id="createPOModal" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>New Purchase Order</h2><p>Create a PO from an approved procurement request.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.purchase-orders.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Purchase Request *</label><select name="purchase_request_id" required><option value="">Select approved request</option>@foreach($requests as $pr)<option value="{{ $pr->id }}">{{ $pr->request_number }} — {{ $pr->currency }} {{ number_format((float)$pr->estimated_total,0) }}</option>@endforeach</select></div>
<div class="form-group full"><label>Supplier *</label><select name="supplier_id" required><option value="">Select supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Order Date *</label><input type="date" name="order_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Expected Delivery</label><input type="date" name="expected_delivery_date"></div>
<div class="form-group"><label>Currency *</label><input name="currency" value="UGX" maxlength="3" required></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Purchase Order</button></div>
</form></div></div>

@foreach($orders as $po)
@if($po->status==='issued')
<div class="eh-modal" id="receivePO{{ $po->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Receive Goods</h2><p>{{ $po->po_number }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.receipts.store',$po) }}">@csrf
<div class="eh-modal-body">
<div class="modal-grid">
<div class="form-group"><label>Received Date *</label><input type="date" name="received_date" value="{{ now()->format('Y-m-d') }}" required></div>
<div class="form-group"><label>Delivery Note Reference</label><input name="delivery_note_reference"></div>
<div class="form-group full"><label>Remarks</label><textarea name="remarks"></textarea></div>
</div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Item</th><th>Ordered</th><th>Received</th><th>Accepted</th><th>Rejected</th><th>Condition Notes</th></tr></thead><tbody>
@foreach($po->items as $i=>$item)
<tr>
<td>{{ $item->item_name }}<input type="hidden" name="items[{{ $i }}][purchase_order_item_id]" value="{{ $item->id }}"></td>
<td>{{ $item->quantity }}</td>
<td><input type="number" step=".01" min="0" name="items[{{ $i }}][quantity_received]" value="{{ $item->quantity }}" required></td>
<td><input type="number" step=".01" min="0" name="items[{{ $i }}][quantity_accepted]" value="{{ $item->quantity }}" required></td>
<td><input type="number" step=".01" min="0" name="items[{{ $i }}][quantity_rejected]" value="0"></td>
<td><input name="items[{{ $i }}][condition_notes]"></td>
</tr>
@endforeach
</tbody></table></div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-check"></i> Confirm Receipt</button></div>
</form></div></div>
@endif
@endforeach
@endsection
