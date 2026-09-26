@extends('layouts.admin')
@section('title','Suppliers | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Procurement</span><h1>Suppliers</h1><p>Manage supplier records and approval status.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createSupplierModal"><i class="fas fa-plus"></i> New Supplier</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Suppliers','fa-truck-field'],['pending','Pending','fa-clock'],['approved','Approved','fa-circle-check'],['inactive','Inactive','fa-circle-pause']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search supplier, category, TIN, contact or email..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['pending','approved','inactive'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.procurement.suppliers.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Supplier</th><th>Category</th><th>TIN</th><th>Contact</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($suppliers as $supplier)
<tr><td><strong>{{ $supplier->name }}</strong><small class="admin-cell-hint">{{ $supplier->registration_number ?: 'No registration number' }}</small></td><td>{{ $supplier->category ?: '—' }}</td><td>{{ $supplier->tin ?: '—' }}</td><td>{{ $supplier->contact_person ?: '—' }}<small class="admin-cell-hint">{{ $supplier->phone ?: $supplier->email }}</small></td><td><span class="status-chip {{ $supplier->status }}">{{ ucfirst($supplier->status) }}</span></td><td class="table-actions">@if($supplier->status==='pending')<button type="button" class="btn btn-outline btn-sm" data-modal-open="approveSupplier{{ $supplier->id }}"><i class="fas fa-check"></i> Approve</button>@endif</td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No suppliers found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $suppliers->links() }}</div>
</div>

<div class="eh-modal" id="createSupplierModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Supplier</h2><p>Create a supplier record for procurement review.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.procurement.suppliers.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Name *</label><input name="name" required></div>
<div class="form-group"><label>Category</label><input name="category"></div>
<div class="form-group"><label>Registration Number</label><input name="registration_number"></div>
<div class="form-group"><label>TIN</label><input name="tin"></div>
<div class="form-group"><label>Contact Person</label><input name="contact_person"></div>
<div class="form-group"><label>Phone</label><input name="phone"></div>
<div class="form-group"><label>Email</label><input type="email" name="email"></div>
<div class="form-group"><label>Address</label><input name="address"></div>
<div class="form-group"><label>Bank Name</label><input name="bank_name"></div>
<div class="form-group"><label>Bank Account Name</label><input name="bank_account_name"></div>
<div class="form-group full"><label>Bank Account Number</label><input name="bank_account_number"><small class="form-hint">Stored securely by the existing supplier model workflow.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Supplier</button></div>
</form></div></div>

@foreach($suppliers as $supplier)
@if($supplier->status==='pending')
<div class="eh-modal" id="approveSupplier{{ $supplier->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Approve Supplier?</h2><p>{{ $supplier->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Approve this supplier for procurement use?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.procurement.suppliers.approve',$supplier) }}">@csrf<button class="btn btn-primary">Approve</button></form></div>
</div></div>
@endif
@endforeach
@endsection
