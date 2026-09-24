@extends('layouts.admin')
@section('content')
<div class="card"><h1>Purchase Requests</h1>
<form method="POST" action="{{ route('admin.procurement.requests.store') }}">@csrf
<label>Department</label><input name="department">
<label>Required Date</label><input type="date" name="required_date">
<label>Funding Source</label><input name="funding_source">
<label>Justification</label><textarea name="justification"></textarea>
<label>Currency</label><input name="currency" value="UGX">
<hr>
<h3>Item 1</h3>
<input name="items[0][item_name]" placeholder="Item/service" required>
<textarea name="items[0][specification]" placeholder="Specification"></textarea>
<input type="number" step="0.01" name="items[0][quantity]" value="1" required>
<input name="items[0][unit]" placeholder="Unit">
<input type="number" step="0.01" name="items[0][estimated_unit_cost]" placeholder="Estimated unit cost">
<label><input type="checkbox" style="width:auto" name="items[0][is_asset]" value="1"> Capital asset</label><br><br>
<button>Create Request</button>
</form></div>
@foreach($requests as $pr)
<div class="card"><strong>{{ $pr->request_number }}</strong><br>{{ $pr->status }} · {{ $pr->currency }} {{ number_format($pr->estimated_total,2) }} · {{ $pr->items->count() }} item(s)</div>
@endforeach
{{ $requests->links() }}
@endsection
