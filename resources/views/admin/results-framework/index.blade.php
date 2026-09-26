@extends('layouts.admin')
@section('title','Results Framework | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">MEAL</span><h1>Results Framework</h1><p>Structure programme and project impacts, outcomes and outputs.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createFramework"><i class="fas fa-plus"></i> New Framework</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['frameworks','Frameworks','fa-diagram-project'],
['impacts','Impacts','fa-earth-africa'],
['outcomes','Outcomes','fa-bullseye'],
['outputs','Outputs','fa-box']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search framework title or description..."></div>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.results-framework.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Framework</th><th>Results</th><th>Impact</th><th>Outcome</th><th>Output</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($frameworks as $framework)
<tr>
<td><strong>{{ $framework->title }}</strong><small class="admin-cell-hint">{{ Str::limit($framework->description,90) }}</small></td>
<td>{{ $framework->results->count() }}</td>
<td>{{ $framework->results->where('result_level','impact')->count() }}</td>
<td>{{ $framework->results->where('result_level','outcome')->count() }}</td>
<td>{{ $framework->results->where('result_level','output')->count() }}</td>
<td class="table-actions"><button type="button" class="btn-icon" data-modal-open="addResult{{ $framework->id }}"><i class="fas fa-plus"></i></button></td>
</tr>
@if($framework->results->count())
<tr><td colspan="6"><details><summary><strong>View results hierarchy</strong></summary>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Level</th><th>Result</th><th>Description</th></tr></thead><tbody>
@foreach($framework->results->sortBy('result_level') as $result)
<tr><td>{{ ucfirst($result->result_level) }}</td><td>{{ $result->title }}</td><td>{{ Str::limit($result->description,120) }}</td></tr>
@endforeach
</tbody></table></div>
</details></td></tr>
@endif
@empty<tr><td colspan="6"><div class="admin-empty">No results frameworks found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $frameworks->links() }}</div>
</div>

<div class="eh-modal" id="createFramework" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>New Results Framework</h2><p>Create a programme or project results framework.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.results-framework.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Framework</button></div>
</form></div></div>

@foreach($frameworks as $framework)
<div class="eh-modal" id="addResult{{ $framework->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Result</h2><p>{{ $framework->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.results-framework.results.store',$framework) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Result Level *</label><select name="result_level">@foreach(['impact','outcome','output'] as $x)<option value="{{ $x }}">{{ ucfirst($x) }}</option>@endforeach</select></div>
<div class="form-group"><label>Parent Result</label><select name="parent_id"><option value="">None</option>@foreach($framework->results as $x)<option value="{{ $x->id }}">{{ ucfirst($x->result_level) }} — {{ $x->title }}</option>@endforeach</select></div>
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Result</button></div>
</form></div></div>
@endforeach
@endsection
