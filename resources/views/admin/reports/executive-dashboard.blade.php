@extends('layouts.app')
@section('content')
<div class="card"><h1>Executive Dashboard</h1></div>
<div class="grid">
@foreach($stats as $label=>$value)
<div class="card">
<h3>{{ ucwords(str_replace('_',' ',$label)) }}</h3>
<div style="font-size:32px;font-weight:bold">{{ number_format($value) }}</div>
</div>
@endforeach
</div>
@endsection
