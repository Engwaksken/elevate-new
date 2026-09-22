@extends('layouts.app')
@section('content')
<div class="card"><h1>Performance Appraisals</h1>
<form method="POST" action="{{ route('admin.hr.appraisal-cycles.store') }}">@csrf
<label>Cycle Name</label><input name="name" required>
<label>Type</label><select name="cycle_type"><option value="annual">Annual</option><option value="mid_year">Mid-Year</option><option value="probation">Probation</option><option value="special">Special</option></select>
<label>Start</label><input type="date" name="start_date" required>
<label>End</label><input type="date" name="end_date" required>
<button>Create Cycle</button></form></div>

@foreach($appraisals as $appraisal)
<div class="card"><strong>{{ $appraisal->employee->user->name }}</strong><br>{{ $appraisal->status }} · Final Score: {{ $appraisal->final_score }}</div>
@endforeach
{{ $appraisals->links() }}
@endsection
