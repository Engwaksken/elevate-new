@extends('layouts.admin')
@section('title','Indicators | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div><span class="admin-eyebrow">MEAL</span><h1>Indicators</h1><p>Manage indicators, targets, calculated results and verification.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createIndicatorModal"><i class="fas fa-plus"></i> New Indicator</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Indicators','fa-chart-line'],
['active','Active','fa-circle-check'],
['pending','Pending Results','fa-clock'],
['verified','Verified Results','fa-badge-check']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search indicator name, code or definition..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['draft','active','inactive','closed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="result_level"><option value="">All levels</option>@foreach(['impact','outcome','output','activity'] as $s)<option value="{{ $s }}" @selected(request('result_level')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="indicator_type"><option value="">All types</option>@foreach(['number','percentage','rate','ratio','currency','binary','text'] as $s)<option value="{{ $s }}" @selected(request('indicator_type')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.indicators.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Indicator</th><th>Level</th><th>Type</th><th>Targets</th><th>Results</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($indicators as $indicator)
<tr>
<td><strong>{{ $indicator->name }}</strong><small class="admin-cell-hint">{{ $indicator->code ?: 'No code' }}</small></td>
<td>{{ ucfirst($indicator->result_level) }}</td>
<td>{{ ucfirst($indicator->indicator_type) }}</td>
<td>{{ $indicator->targets_count }}</td>
<td>{{ $indicator->results_count }}</td>
<td><span class="status-chip {{ $indicator->status }}">{{ ucfirst($indicator->status) }}</span></td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" data-modal-open="target{{ $indicator->id }}" title="Add target"><i class="fas fa-bullseye"></i></button>
@if($indicator->calculation_key)<button type="button" class="btn-icon" data-modal-open="calculate{{ $indicator->id }}" title="Calculate"><i class="fas fa-calculator"></i></button>@endif
</div></td>
</tr>
@if($indicator->results->where('verification_status','submitted')->count())
<tr><td colspan="7">
<details><summary><strong>Pending result verification</strong></summary>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Period</th><th>Actual</th><th>Source</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($indicator->results->where('verification_status','submitted') as $result)
<tr><td>{{ $result->reporting_period }}</td><td>{{ $result->actual_numeric ?? $result->actual_text ?? '—' }}</td><td>{{ $result->data_source ?: '—' }}</td><td>{{ ucfirst($result->verification_status) }}</td><td><button type="button" class="btn btn-outline btn-sm" data-modal-open="verifyResult{{ $result->id }}">Verify</button></td></tr>
@endforeach
</tbody></table></div>
</details>
</td></tr>
@endif
@empty<tr><td colspan="7"><div class="admin-empty">No indicators found.</div></td></tr>@endforelse
</tbody>
</table></div>
<div class="admin-pagination">{{ $indicators->links() }}</div>
</div>

<div class="eh-modal" id="createIndicatorModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Indicator</h2><p>Create an impact, outcome, output or activity indicator.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.indicators.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Name *</label><input name="name" required></div>
<div class="form-group"><label>Code</label><input name="code"></div>
<div class="form-group"><label>Result Level *</label><select name="result_level">@foreach(['impact','outcome','output','activity'] as $x)<option value="{{ $x }}">{{ ucfirst($x) }}</option>@endforeach</select></div>
<div class="form-group"><label>Indicator Type *</label><select name="indicator_type">@foreach(['number','percentage','rate','ratio','currency','binary','text'] as $x)<option value="{{ $x }}">{{ ucfirst($x) }}</option>@endforeach</select></div>
<div class="form-group"><label>Unit of Measure</label><input name="unit_of_measure"></div>
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Responsible Staff</label><select name="responsible_user_id"><option value="">None</option>@foreach($users as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Baseline Numeric</label><input type="number" step=".0001" name="baseline_numeric"></div>
<div class="form-group"><label>Baseline Text</label><input name="baseline_text"></div>
<div class="form-group"><label>Frequency</label><input name="frequency" placeholder="Monthly, Quarterly..."></div>
<div class="form-group"><label>Calculation Key</label><input name="calculation_key"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','active','inactive','closed'] as $x)<option value="{{ $x }}">{{ ucfirst($x) }}</option>@endforeach</select></div>
<div class="form-group full"><label>Definition</label><textarea name="definition"></textarea></div>
<div class="form-group full"><label>Data Source</label><textarea name="data_source"></textarea></div>
<div class="form-group full"><label>Means of Verification</label><textarea name="means_of_verification"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Indicator</button></div>
</form></div></div>

@foreach($indicators as $indicator)
<div class="eh-modal" id="target{{ $indicator->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Target</h2><p>{{ $indicator->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.indicators.targets.store',$indicator) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Period Type *</label><input name="period_type" required placeholder="Quarterly"></div>
<div class="form-group"><label>Period Label *</label><input name="period_label" required placeholder="Q1 2026"></div>
<div class="form-group"><label>Period Start</label><input type="date" name="period_start"></div>
<div class="form-group"><label>Period End</label><input type="date" name="period_end"></div>
<div class="form-group"><label>Target Numeric</label><input type="number" step=".0001" name="target_numeric"></div>
<div class="form-group"><label>Target Text</label><input name="target_text"></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Target</button></div>
</form></div></div>

@if($indicator->calculation_key)
<div class="eh-modal" id="calculate{{ $indicator->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Calculate Indicator?</h2><p>{{ $indicator->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>The system will calculate this indicator using <strong>{{ $indicator->calculation_key }}</strong> and create a submitted result.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.indicators.calculate',$indicator) }}">@csrf<button class="btn btn-primary">Calculate</button></form></div>
</div></div>
@endif

@foreach($indicator->results->where('verification_status','submitted') as $result)
<div class="eh-modal" id="verifyResult{{ $result->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Verify Result?</h2><p>{{ $indicator->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Verify the submitted result for {{ $result->reporting_period }}?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.indicator-results.verify',$result) }}">@csrf<button class="btn btn-primary">Verify</button></form></div>
</div></div>
@endforeach
@endforeach
@endsection
