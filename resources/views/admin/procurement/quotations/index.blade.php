@extends('layouts.admin')
@section('title','Quotations | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Procurement</span><h1>Quotations — {{ $purchaseRequest->request_number }}</h1><p>Record supplier quotations and evaluation scores.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.procurement.requests.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Requests</a><button type="button" class="btn btn-primary" data-modal-open="addQuotationModal"><i class="fas fa-plus"></i> Add Quotation</button></div>
</div>

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-file-invoice-dollar"></i></span><div><small>Quotations</small><strong>{{ $purchaseRequest->quotations->count() }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-building"></i></span><div><small>Suppliers Available</small><strong>{{ $suppliers->count() }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Request Estimate</small><strong style="font-size:.8rem">{{ $purchaseRequest->currency }} {{ number_format((float)$purchaseRequest->estimated_total,0) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-star"></i></span><div><small>Evaluated</small><strong>{{ $purchaseRequest->quotations->where('status','evaluated')->count() }}</strong></div></div>
</div>

<div class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Supplier</th><th>Quotation No.</th><th>Date</th><th>Valid Until</th><th>Total</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($purchaseRequest->quotations as $quotation)
<tr>
<td><strong>{{ data_get($quotation,'supplier.name','—') }}</strong></td>
<td>{{ $quotation->quotation_number ?: '—' }}</td>
<td>{{ optional($quotation->quotation_date)->format('d M Y') ?: '—' }}</td>
<td>{{ optional($quotation->valid_until)->format('d M Y') ?: '—' }}</td>
<td>{{ $quotation->currency }} {{ number_format((float)$quotation->total_amount,2) }}</td>
<td><span class="status-chip {{ $quotation->status }}">{{ ucfirst(str_replace('_',' ',$quotation->status)) }}</span></td>
<td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="evaluateQuotation{{ $quotation->id }}"><i class="fas fa-star"></i> Evaluate</button></td>
</tr>
@empty<tr><td colspan="7"><div class="admin-empty">No quotations recorded for this request.</div></td></tr>@endforelse
</tbody></table></div>
</div>

<div class="eh-modal" id="addQuotationModal" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Quotation</h2><p>{{ $purchaseRequest->request_number }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.quotations.store',$purchaseRequest) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Supplier *</label><select name="supplier_id" required><option value="">Select approved supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Quotation Number</label><input name="quotation_number"></div>
<div class="form-group"><label>Quotation Date</label><input type="date" name="quotation_date"></div>
<div class="form-group"><label>Valid Until</label><input type="date" name="valid_until"></div>
<div class="form-group"><label>Currency *</label><input name="currency" value="{{ $purchaseRequest->currency ?: 'UGX' }}" maxlength="3" required></div>
<div class="form-group"><label>Subtotal *</label><input type="number" step=".01" min="0" name="subtotal" value="0" required></div>
<div class="form-group"><label>Tax</label><input type="number" step=".01" min="0" name="tax_amount" value="0"></div>
<div class="form-group"><label>Total *</label><input type="number" step=".01" min="0" name="total_amount" required></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Quotation</button></div>
</form></div></div>

@foreach($purchaseRequest->quotations as $quotation)
<div class="eh-modal" id="evaluateQuotation{{ $quotation->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Evaluate Quotation</h2><p>{{ data_get($quotation,'supplier.name','Supplier') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.quotations.evaluate',$quotation) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Technical Score</label><input type="number" step=".01" min="0" max="100" name="technical_score"></div>
<div class="form-group"><label>Financial Score</label><input type="number" step=".01" min="0" max="100" name="financial_score"></div>
<div class="form-group"><label>Overall Score</label><input type="number" step=".01" min="0" max="100" name="overall_score"></div>
<div class="form-group"><label class="modal-check"><input type="checkbox" name="recommended" value="1"><span>Recommended supplier</span></label></div>
<div class="form-group full"><label>Comments</label><textarea name="comments"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Evaluation</button></div>
</form></div></div>
@endforeach
@endsection
