@extends('layouts.admin')
@section('title','Mentor Matches | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Delivery</span><h1>Mentor Matches</h1><p>Create and manage mentor-to-mentee assignments.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createMatchModal"><i class="fas fa-plus"></i> New Match</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Matches','fa-handshake'],['active','Active','fa-circle-check'],['completed','Completed','fa-flag-checkered'],['inactive','Inactive','fa-circle-pause']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<select name="status"><option value="">All statuses</option>@foreach(['active','completed','inactive','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select name="per_page">@foreach([10,20,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',20)===$size)>{{ $size }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.mentorship.matches.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mentor</th><th>Mentee</th><th>Period</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($matches as $match)
<tr>
<td>{{ data_get($match,'mentor.user.name',data_get($match,'mentor.name','—')) }}</td>
<td>{{ data_get($match,'mentee.user.name',data_get($match,'mentee.name','—')) }}</td>
<td>{{ optional($match->start_date)->format('d M Y') ?: '—' }} — {{ optional($match->end_date)->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $match->status }}">{{ ucfirst($match->status) }}</span></td>
<td class="table-actions"><div class="action-group"><button type="button" class="btn-icon" data-modal-open="editMatch{{ $match->id }}"><i class="fas fa-pen"></i></button><button type="button" class="btn-icon danger" data-modal-open="deleteMatch{{ $match->id }}"><i class="fas fa-trash"></i></button></div></td>
</tr>
@empty<tr><td colspan="5"><div class="admin-empty">No mentor matches found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $matches->links() }}</div>
</div>

<div class="eh-modal" id="createMatchModal" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>New Mentor Match</h2><p>Assign an approved mentor to a mentee.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.mentorship.matches.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Mentor *</label><select name="mentor_user_id" required><option value="">Select mentor</option>@foreach($mentors as $mentor)<option value="{{ data_get($mentor,'user.id') }}">{{ data_get($mentor,'user.name','Mentor') }}</option>@endforeach</select><small class="form-hint">Only approved mentors are listed.</small></div>
<div class="form-group"><label>Mentee *</label><select name="mentee_user_id" required><option value="">Select mentee</option>@foreach($mentees as $mentee)<option value="{{ data_get($mentee,'user.id') }}">{{ data_get($mentee,'user.name','Mentee') }}</option>@endforeach</select></div>
<div class="form-group"><label>Start date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>End date</label><input type="date" name="end_date"></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Match</button></div>
</form></div></div>

@foreach($matches as $match)
<div class="eh-modal" id="editMatch{{ $match->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Edit Mentor Match</h2><p>Update assignment details.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.mentorship.matches.update',$match) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Mentor *</label><select name="mentor_user_id" required>@foreach($mentors as $mentor)<option value="{{ data_get($mentor,'user.id') }}" @selected((int)data_get($match,'mentor_user_id')===(int)data_get($mentor,'user.id'))>{{ data_get($mentor,'user.name','Mentor') }}</option>@endforeach</select></div>
<div class="form-group"><label>Mentee *</label><select name="mentee_user_id" required>@foreach($mentees as $mentee)<option value="{{ data_get($mentee,'user.id') }}" @selected((int)data_get($match,'mentee_user_id')===(int)data_get($mentee,'user.id'))>{{ data_get($mentee,'user.name','Mentee') }}</option>@endforeach</select></div>
<div class="form-group"><label>Status</label><select name="status">@foreach(['active','completed','inactive','cancelled'] as $s)<option value="{{ $s }}" @selected($match->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group"><label>Start date</label><input type="date" name="start_date" value="{{ optional($match->start_date)->format('Y-m-d') }}"></div>
<div class="form-group"><label>End date</label><input type="date" name="end_date" value="{{ optional($match->end_date)->format('Y-m-d') }}"></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Changes</button></div>
</form></div></div>

<div class="eh-modal" id="deleteMatch{{ $match->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Delete Match?</h2><p>This cannot be undone.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Delete this mentor/mentee match?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.mentorship.matches.destroy',$match) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div>
</div></div>
@endforeach
@endsection
