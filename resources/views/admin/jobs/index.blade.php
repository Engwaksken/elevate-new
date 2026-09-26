@extends('layouts.admin')
@section('title','Jobs Management | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
<span class="admin-eyebrow">Jobs & HR</span>
<h1>Available Jobs</h1>
<p>Admin and HR can add, upload, publish, update and archive job opportunities.</p>
</div>
<div class="admin-page-actions">
<button class="btn btn-outline" type="button" data-modal-open="importJobs"><i class="fas fa-file-import"></i> Upload Jobs</button>
<button class="btn btn-primary" type="button" data-modal-open="createJob"><i class="fas fa-plus"></i> Add Job</button>
</div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="admin-stats-grid compact">
@foreach([['total','Total Jobs','fa-briefcase'],['published','Published','fa-circle-check'],['draft','Draft / Pending','fa-pen'],['closed','Closed / Rejected','fa-ban']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search job, employer, industry or location..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['draft','pending','published','closed','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="employment_type"><option value="">All employment types</option>@foreach(['full_time'=>'Full time','part_time'=>'Part time','contract'=>'Contract','internship'=>'Internship','temporary'=>'Temporary'] as $v=>$l)<option value="{{ $v }}" @selected(request('employment_type')===$v)>{{ $l }}</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Job</th><th>Employer</th><th>Location</th><th>Deadline</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($jobs as $job)
<tr>
<td><strong>{{ $job->title }}</strong><small class="admin-cell-hint">{{ ucfirst(str_replace('_',' ',$job->employment_type)) }}</small></td>
<td>{{ $job->employer?->company_name ?: '—' }}</td>
<td>{{ trim(($job->location ?: '').($job->country ? ', '.$job->country : '')) ?: '—' }}</td>
<td>{{ optional($job->application_deadline)->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $job->status }}">{{ ucfirst($job->status) }}</span></td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" data-modal-open="editJob{{ $job->id }}" title="Edit"><i class="fas fa-pen"></i></button>
@if($job->status!=='published')<form method="POST" action="{{ route($routePrefix.'.publish',$job) }}">@csrf<button class="btn-icon" title="Publish"><i class="fas fa-upload"></i></button></form>@endif
<button type="button" class="btn-icon danger" data-modal-open="deleteJob{{ $job->id }}" title="Archive"><i class="fas fa-box-archive"></i></button>
</div></td>
</tr>
@empty<tr><td colspan="6"><div class="admin-empty">No jobs found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $jobs->links() }}</div>
</div>

<div class="eh-modal" id="importJobs" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Upload Available Jobs</h2><p>Upload CSV/XLSX with one job per row.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" enctype="multipart/form-data" action="{{ route($routePrefix.'.import') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Employer *</label><select name="employer_id" required><option value="">Select employer</option>@foreach($employers as $e)<option value="{{ $e->id }}">{{ $e->company_name }}</option>@endforeach</select><small class="form-hint">All imported rows will be linked to this employer.</small></div>
<div class="form-group full"><label>Jobs File *</label><input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required><small class="form-hint">Columns supported: title/job_title, category, industry, location, country, employment_type, work_arrangement, experience_level, education_level, salary_min, salary_max, salary_currency, description, responsibilities, requirements, skills, application_deadline, positions, status.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Upload Jobs</button></div>
</form></div></div>

@php($emptyJob=new \App\Models\Job(['country'=>'Uganda','salary_currency'=>'UGX','positions'=>1,'status'=>'draft','employment_type'=>'full_time','work_arrangement'=>'onsite']))
@include('admin.jobs.form-modal',['id'=>'createJob','title'=>'Add Job','job'=>$emptyJob,'action'=>route($routePrefix.'.store'),'method'=>'POST'])

@foreach($jobs as $job)
@include('admin.jobs.form-modal',['id'=>'editJob'.$job->id,'title'=>'Edit Job','job'=>$job,'action'=>route($routePrefix.'.update',$job),'method'=>'PUT'])
<div class="eh-modal" id="deleteJob{{ $job->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Archive Job?</h2><p>{{ $job->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>The job will be soft-deleted and removed from the active job list.</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route($routePrefix.'.destroy',$job) }}">@csrf @method('DELETE')<button class="btn btn-danger">Archive</button></form></div></div></div>
@endforeach
@endsection
