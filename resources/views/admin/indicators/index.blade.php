@extends('layouts.app')
@section('content')
<div class="card"><h1>Indicators</h1>
<form method="POST" action="{{ route('admin.indicators.store') }}">@csrf
<label>Name</label><input name="name" required>
<label>Code</label><input name="code">
<label>Result Level</label><select name="result_level"><option value="impact">Impact</option><option value="outcome">Outcome</option><option value="output">Output</option><option value="activity">Activity</option></select>
<label>Type</label><select name="indicator_type"><option value="number">Number</option><option value="percentage">Percentage</option><option value="rate">Rate</option><option value="ratio">Ratio</option><option value="currency">Currency</option><option value="binary">Binary</option><option value="text">Text</option></select>
<label>Definition</label><textarea name="definition"></textarea>
<label>Calculation Key</label><input name="calculation_key" placeholder="optional system calculation key">
<label>Status</label><select name="status"><option value="draft">Draft</option><option value="active">Active</option></select>
<button>Create Indicator</button></form></div>

@foreach($indicators as $indicator)
<div class="card"><strong>{{ $indicator->name }}</strong><br>{{ $indicator->code }} · {{ $indicator->result_level }} · {{ $indicator->targets_count }} targets · {{ $indicator->results_count }} results
@if($indicator->calculation_key)
<form method="POST" action="{{ route('admin.indicators.calculate',$indicator) }}">@csrf<button>Calculate from System</button></form>
@endif
</div>
@endforeach
{{ $indicators->links() }}
@endsection
