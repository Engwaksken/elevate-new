@extends('layouts.admin')
@section('title','Employers | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Opportunities</span><h1>Employers</h1><p>Review employer registrations and approve access to employer services.</p></div></div>
<div class="admin-stats-grid compact">
@foreach([['total','Total Employers','fa-building'],['pending','Pending','fa-clock'],['approved','Approved','fa-circle-check'],['rejected','Rejected','fa-circle-xmark']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>@endforeach
</div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar"><div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search company, owner or email..."></div><select name="status"><option value="">All statuses</option>@foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select><select name="per_page">@foreach([10,20,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',20)===$size)>{{ $size }}/page</option>@endforeach</select><button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.jobs.employers.index') }}" class="btn btn-outline btn-sm">Reset</a></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Company</th><th>Owner</th><th>Status</th><th>Registered</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($employers as $employer)
<tr><td><strong>{{ $employer->company_name }}</strong></td><td>{{ data_get($employer,'owner.name','—') }}<small class="admin-cell-hint">{{ data_get($employer,'owner.email','') }}</small></td><td><span class="status-chip {{ $employer->status }}">{{ ucfirst($employer->status) }}</span></td><td>{{ optional($employer->created_at)->format('d M Y') ?: '—' }}</td><td class="table-actions"><div class="action-group">@if($employer->status==='pending')<button type="button" class="btn-icon" data-modal-open="approveEmployer{{ $employer->id }}"><i class="fas fa-check"></i></button><button type="button" class="btn-icon danger" data-modal-open="rejectEmployer{{ $employer->id }}"><i class="fas fa-xmark"></i></button>@endif</div></td></tr>
@empty<tr><td colspan="5"><div class="admin-empty">No employers found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $employers->links() }}</div></div>

@foreach($employers as $employer)
<div class="eh-modal" id="approveEmployer{{ $employer->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Approve Employer?</h2><p>{{ $employer->company_name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Approve this employer account?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.jobs.employers.approve',$employer) }}">@csrf<button class="btn btn-primary">Approve</button></form></div></div></div>
<div class="eh-modal" id="rejectEmployer{{ $employer->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Reject Employer?</h2><p>{{ $employer->company_name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Reject this employer registration?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.jobs.employers.reject',$employer) }}">@csrf<button class="btn btn-danger">Reject</button></form></div></div></div>
@endforeach
@endsection
