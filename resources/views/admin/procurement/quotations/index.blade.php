@extends('layouts.admin')
@section('content')
<div class="card"><h1>Quotations — {{ $purchaseRequest->request_number }}</h1>
<form method="POST" action="{{ route('admin.procurement.quotations.store',$purchaseRequest) }}">@csrf
<label>Supplier</label><select name="supplier_id">@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>
<label>Quotation Number</label><input name="quotation_number">
<label>Date</label><input type="date" name="quotation_date">
<label>Subtotal</label><input type="number" step="0.01" name="subtotal" value="0">
<label>Tax</label><input type="number" step="0.01" name="tax_amount" value="0">
<label>Total</label><input type="number" step="0.01" name="total_amount" required>
<label>Currency</label><input name="currency" value="UGX">
<button>Add Quotation</button>
</form></div>
@foreach($purchaseRequest->quotations as $quotation)
<div class="card">{{ $quotation->supplier->name }} · {{ $quotation->currency }} {{ number_format($quotation->total_amount,2) }} · {{ $quotation->status }}</div>
@endforeach
@endsection
