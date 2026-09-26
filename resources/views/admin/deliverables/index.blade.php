@extends('layouts.admin')
@section('title','Deliverables | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Planning & Delivery</span><h1>Deliverables</h1><p>Track activity deliverables, owners, due dates and completion progress.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createDeliverableModal"><i class="fas fa-plus"></i> New Deliverable</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Deliverables','fa-box'],['in_progress','In Progress','fa-bars-progress'],['completed','Completed','fa-circle-check'],['overdue','Overdue','fa-triangle-exclamation']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search deliverable, activity, owner or description..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['not_started','in_progress','returned_for_revision','completed','overdue'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}" title="Due date from">
<input type="date" name="to" value="{{ request('to') }}" title="Due date to">
<select name="per_page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.deliverables.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Deliverable</th><th>Activity</th><th>Owner</th><th>Due</th><th>Progress</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($deliverables as $deliverable)
<tr>
<td><strong>{{ $deliverable->title }}</strong><small class="admin-cell-hint">{{ Str::limit($deliverable->description,90) }}</small></td>
<td>{{ data_get($deliverable,'activity.title','—') }}</td>
<td>{{ data_get($deliverable,'owner.name','—') }}</td>
<td>{{ optional($deliverable->due_date)->format('d M Y') ?: '—' }}</td>
<td><strong>{{ number_format((float)$deliverable->progress_percent,0) }}%</strong><div style="height:5px;background:#eee;border-radius:5px;margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ min(100,(float)$deliverable->progress_percent) }}%;background:#800000"></div></div></td>
<td><span class="status-chip {{ $deliverable->status }}">{{ ucfirst(str_replace('_',' ',$deliverable->status)) }}</span></td>
<td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="updateDeliverable{{ $deliverable->id }}"><i class="fas fa-pen"></i> Update</button></td>
</tr>
@empty<tr><td colspan="7"><div class="admin-empty">No deliverables found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $deliverables->links() }}</div>
</div>

<div class="eh-modal" id="createDeliverableModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Deliverable</h2><p>Create a deliverable under an existing activity.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" id="createDeliverableForm" action="">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Activity *</label><select id="deliverableActivity" required><option value="">Select activity</option>@foreach($activities as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach</select></div>
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Owner</label><select name="owner_user_id"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
<div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Deliverable</button></div>
</form></div></div>

@foreach($deliverables as $deliverable)
<div class="eh-modal" id="updateDeliverable{{ $deliverable->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Update Deliverable</h2><p>{{ $deliverable->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.deliverables.update',$deliverable) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Status *</label><select name="status">@foreach(['not_started','in_progress','returned_for_revision','completed','overdue'] as $s)<option value="{{ $s }}" @selected($deliverable->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group"><label>Progress % *</label><input type="number" name="progress_percent" value="{{ $deliverable->progress_percent }}" min="0" max="100" required></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Progress</button></div>
</form></div></div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select=document.getElementById('deliverableActivity');
    const form=document.getElementById('createDeliverableForm');
    if(!select || !form) return;
    select.addEventListener('change', function(){
        form.action = this.value ? `/admin/activities/${this.value}/deliverables` : '';
    });
});
</script>
@endsection
