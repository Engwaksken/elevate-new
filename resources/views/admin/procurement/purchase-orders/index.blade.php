@extends('layouts.app')
@section('content')
<div class="card"><h1>Purchase Orders</h1></div>
@foreach($orders as $po)
<div class="card"><strong>{{ $po->po_number }}</strong><br>{{ $po->supplier->name }} · {{ $po->currency }} {{ number_format($po->total_amount,2) }} · {{ $po->status }}</div>
@endforeach
{{ $orders->links() }}
@endsection
