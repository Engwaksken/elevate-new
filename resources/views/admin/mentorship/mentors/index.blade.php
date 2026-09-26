@extends('layouts.admin')
@section('title','Mentors | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Mentors</h1><p>Review mentor applications and manage approval status.</p></div>
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

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Mentor</th><th>Organisation</th><th>Job Title</th><th>Status</th><th>Applied</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($mentors as $mentor)
<tr>
<td><strong>{{ data_get($mentor,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($mentor,'user.email','') }}</small></td>
<td>{{ $mentor->organisation ?: '—' }}</td>
<td>{{ $mentor->job_title ?: '—' }}</td>
<td><span class="status-chip {{ $mentor->status }}">{{ ucfirst($mentor->status) }}</span></td>
<td>{{ optional($mentor->created_at)->format('d M Y') ?: '—' }}</td>
<td class="table-actions"><div class="action-group">
@if($mentor->status==='pending')
<button type="button" class="btn-icon" title="Approve" data-modal-open="approveMentor{{ $mentor->id }}"><i class="fas fa-check"></i></button>
<button type="button" class="btn-icon danger" title="Reject" data-modal-open="rejectMentor{{ $mentor->id }}"><i class="fas fa-xmark"></i></button>
@endif
</div></td>
</tr>
@empty<tr><td colspan="6"><div class="admin-empty">No mentor applications found.</div></td></tr>@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $mentors->links() }}</div>
</div>

@foreach($mentors as $mentor)
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
@endsection
