@extends('layouts.app')
@section('content')
<div class="card"><h1>Asset Register</h1>
<form method="POST" action="{{ route('admin.assets.store') }}">@csrf
<label>Asset Tag</label><input name="asset_tag">
<label>Category</label><select name="asset_category_id"><option value="">None</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
<label>Description</label><input name="description" required>
<label>Brand</label><input name="brand">
<label>Model</label><input name="model">
<label>Serial Number</label><input name="serial_number">
<label>Purchase Date</label><input type="date" name="purchase_date">
<label>Purchase Price</label><input type="number" step="0.01" name="purchase_price">
<label>Currency</label><input name="currency" value="UGX">
<label>Location</label><input name="location">
<label>Condition</label><input name="condition" value="good">
<button>Register Asset</button>
</form></div>

@foreach($assets as $asset)
<div class="card"><strong>{{ $asset->asset_code }}</strong> · {{ $asset->asset_tag }}<br>{{ $asset->description }} · {{ $asset->status }} · {{ $asset->condition }}</div>
@endforeach
{{ $assets->links() }}
@endsection
