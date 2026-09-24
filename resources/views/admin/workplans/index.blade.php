@extends('layouts.admin')
@section('content')
<div class="card"><h1>Workplans</h1>
<form method="POST" action="{{ route('admin.workplans.store') }}">@csrf
<label>Title</label><input name="title" required>
<label>Financial Year</label><input name="financial_year">
<label>Period Type</label><select name="period_type"><option value="annual">Annual</option><option value="quarterly">Quarterly</option><option value="monthly">Monthly</option><option value="programme">Programme</option><option value="project">Project</option></select>
<label>Start</label><input type="date" name="start_date">
<label>End</label><input type="date" name="end_date">
<label>Description</label><textarea name="description"></textarea>
<button>Create Workplan</button></form></div>
@foreach($workplans as $workplan)
<div class="card"><strong>{{ $workplan->title }}</strong><br>{{ $workplan->status }} · {{ $workplan->progress_percent }}%</div>
@endforeach
{{ $workplans->links() }}
@endsection
