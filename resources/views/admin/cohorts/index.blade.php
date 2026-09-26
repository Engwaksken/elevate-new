@extends('layouts.admin')
@section('title','Cohorts | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Programme Management</span><h1>Cohorts</h1><p>Manage programme cohorts, delivery locations, dates and status.</p></div><div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="cohortCreate"><i class="fas fa-plus"></i> Add Cohort</button></div></div>
@include('admin.shared.feedback')
<div class="admin-stats-grid compact">@foreach([['total','Total Cohorts','fa-users-rectangle'],['active','Active','fa-circle-check'],['open','Open','fa-door-open'],['completed','Completed','fa-flag-checkered']] as [$k,$l,$i])<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>@endforeach</div>
<div class="admin-panel"><form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search cohort name or code..."></div>
<select name="programme_id"><option value="">All programmes</option>@foreach($programmes as $p)<option value="{{ $p->id }}" @selected((string)request('programme_id')===(string)$p->id)>{{ $p->name }}</option>@endforeach</select>
<select name="branch_id"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected((string)request('branch_id')===(string)$b->id)>{{ $b->name }}</option>@endforeach</select>
<select name="status"><option value="">All statuses</option>@foreach(['planned','open','active','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.cohorts.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Cohort</th><th>Programme / Project</th><th>Branch</th><th>Dates</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($cohorts as $cohort)
<tr><td><strong>{{ $cohort->name }}</strong><small class="admin-cell-hint">{{ $cohort->code ?: 'No code' }}</small></td><td>{{ $cohort->programme?->name ?: '—' }}<small class="admin-cell-hint">{{ $cohort->project?->name ?: '' }}</small></td><td>{{ $cohort->branch?->name ?: '—' }}</td><td>{{ optional($cohort->start_date)->format('d M Y') ?: '—' }} — {{ optional($cohort->end_date)->format('d M Y') ?: '—' }}</td><td><span class="status-chip {{ $cohort->status }}">{{ ucfirst($cohort->status) }}</span></td><td class="table-actions"><div class="action-group"><button type="button" class="btn-icon" data-modal-open="cohort{{ $cohort->id }}"><i class="fas fa-pen"></i></button><button type="button" class="btn-icon danger" data-modal-open="cohortDelete{{ $cohort->id }}"><i class="fas fa-trash"></i></button></div></td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No cohorts found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $cohorts->links() }}</div></div>

@php($blank=new \App\Models\Cohort(['status'=>'planned']))
@include('admin.cohorts.modal',['id'=>'cohortCreate','title'=>'Add Cohort','cohort'=>$blank,'action'=>route('admin.cohorts.store'),'method'=>'POST'])
@foreach($cohorts as $cohort)
@include('admin.cohorts.modal',['id'=>'cohort'.$cohort->id,'title'=>'Edit Cohort','cohort'=>$cohort,'action'=>route('admin.cohorts.update',$cohort),'method'=>'PUT'])
<div class="eh-modal" id="cohortDelete{{ $cohort->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Delete Cohort?</h2><p>{{ $cohort->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Delete only when no enrolments, courses or related records depend on this cohort.</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.cohorts.destroy',$cohort) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div></div></div>
@endforeach
@endsection
