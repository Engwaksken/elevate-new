@extends('layouts.app')
@section('content')
<div class="card"><h1>Suppliers</h1>
<form method="POST" action="{{ route('admin.procurement.suppliers.store') }}">@csrf
<label>Name</label><input name="name" required>
<label>Category</label><input name="category">
<label>TIN</label><input name="tin">
<label>Contact Person</label><input name="contact_person">
<label>Phone</label><input name="phone">
<label>Email</label><input type="email" name="email">
<button>Create Supplier</button>
</form></div>
@foreach($suppliers as $supplier)
<div class="card"><strong>{{ $supplier->name }}</strong><br>{{ $supplier->category }} · {{ $supplier->status }}
@if($supplier->status==='pending')
<form method="POST" action="{{ route('admin.procurement.suppliers.approve',$supplier) }}">@csrf<button>Approve</button></form>
@endif
</div>
@endforeach
{{ $suppliers->links() }}
@endsection
