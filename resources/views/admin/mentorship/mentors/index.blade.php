@extends('layouts.admin')
@section('title','Mentors | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Mentors</h1><p>Review mentor applications and manage approval status.</p></div>
<button type="button" class="btn btn-primary" data-modal-open="createMentorModal"><i class="fas fa-plus"></i> Add Mentor</button>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Mentors','fa-user-tie'],['pending','Pending','fa-clock'],['approved','Approved','fa-circle-check'],['rejected','Rejected','fa-circle-xmark']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search mentor, email, organisation or job title..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',20)===$size)>{{ $size }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
<a href="{{ route('admin.mentorship.mentors.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@php
$bulkRoute = route('admin.mentorship.mentors.bulk-destroy');
$bulkTableId = 'mentorsTable';
@endphp
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])

<div class="admin-table-wrap">
<table class="admin-table" id="{{ $bulkTableId }}">
<thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Mentor</th><th>Organisation</th><th>Job Title</th><th>Status</th><th>Applied</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($mentors as $mentor)
<tr>
<td><input type="checkbox" data-row-select value="{{ $mentor->id }}" aria-label="Select {{ data_get($mentor,'user.name','Mentor') }}"></td>
<td><strong>{{ data_get($mentor,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($mentor,'user.email','') }}</small></td>
<td>{{ $mentor->organisation ?: '—' }}</td>
<td>{{ $mentor->job_title ?: '—' }}</td>
<td><span class="status-chip {{ $mentor->status }}">{{ ucfirst($mentor->status) }}</span></td>
<td>{{ optional($mentor->created_at)->format('d M Y') ?: '—' }}</td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" title="Edit mentor" data-modal-open="editMentor{{ $mentor->id }}"><i class="fas fa-pen"></i></button>
@if($mentor->status==='pending')
<button type="button" class="btn-icon" title="Approve" data-modal-open="approveMentor{{ $mentor->id }}"><i class="fas fa-check"></i></button>
<button type="button" class="btn-icon danger" title="Reject" data-modal-open="rejectMentor{{ $mentor->id }}"><i class="fas fa-xmark"></i></button>
@endif
<button type="button" class="btn-icon danger" title="Delete" data-modal-open="deleteMentor{{ $mentor->id }}"><i class="fas fa-trash"></i></button>
</div></td>
</tr>
@empty<tr><td colspan="7"><div class="admin-empty">No mentor applications found.</div></td></tr>@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $mentors->links() }}</div>
</div>

@php($mentorUsers = \App\Models\User::where('user_type', 'participant')->orderBy('name')->get())
<div class="eh-modal" id="createMentorModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Add Mentor</h2><p>Enter details directly or link a participant account.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
@include('admin.partners.form-modal', ['type'=>'mentor', 'profile'=>new \App\Models\MentorProfile(), 'users'=>$mentorUsers, 'prefix'=>'admin.mentorship.mentors.', 'fieldLabels'=>[]])
</div></div>

@foreach($mentors as $mentor)
<div class="eh-modal" id="editMentor{{ $mentor->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Edit Mentor</h2><p>{{ data_get($mentor,'user.name','Mentor') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
@include('admin.partners.form-modal', ['type'=>'mentor', 'profile'=>$mentor->load('user'), 'users'=>collect(), 'prefix'=>'admin.mentorship.mentors.', 'fieldLabels'=>[]])
</div></div>

<div class="eh-modal" id="deleteMentor{{ $mentor->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Delete Mentor?</h2><p>{{ data_get($mentor,'user.name','Mentor') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Delete this mentor profile? This cannot be undone.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.mentorship.mentors.destroy',$mentor) }}">@csrf @method('DELETE')<button class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button></form></div>
</div></div>

<div class="eh-modal" id="approveMentor{{ $mentor->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Approve Mentor?</h2><p>{{ data_get($mentor,'user.name','Mentor') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Approve this mentor application and make the mentor available for matching?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.mentorship.mentors.approve',$mentor) }}">@csrf<button class="btn btn-primary"><i class="fas fa-check"></i> Approve</button></form></div>
</div></div>

<div class="eh-modal" id="rejectMentor{{ $mentor->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Reject Mentor?</h2><p>{{ data_get($mentor,'user.name','Mentor') }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Reject this mentor application?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.mentorship.mentors.reject',$mentor) }}">@csrf<button class="btn btn-danger"><i class="fas fa-xmark"></i> Reject</button></form></div>
</div></div>
@endforeach
@php($mentorModal = old('_partner_modal'))
@if($mentorModal)
<script>
document.addEventListener('DOMContentLoaded',function(){
    const value=@json($mentorModal);
    const id=value==='create' ? 'createMentorModal' : (value.startsWith('edit-') ? 'editMentor'+value.substring(5) : null);
    if(id) document.querySelector(`[data-modal-open="${id}"]`)?.click();
});
</script>
@endif
@endsection
