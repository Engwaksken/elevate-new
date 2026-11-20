@extends('layouts.admin')
@section('title',(data_get($employee,'user.name','Employee')).' | Employee Directory')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('hr.manage');
    $user = $employee->user;
@endphp
<style>
.hr-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
@media(max-width:900px){.hr-grid{grid-template-columns:1fr}}
.hr-dl{display:grid;grid-template-columns:150px 1fr;gap:6px 10px;margin:0}
.hr-dl dt{font-weight:700;color:var(--ad-muted,#667085)}
.hr-dl dd{margin:0}
</style>
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('admin.hr.employees.index') }}">Employee Directory</a></span>
        <h1>{{ $user?->name ?? 'Employee' }}</h1>
        <p>{{ collect([$employee->employee_number, $position?->title, $department?->name])->filter()->join(' · ') }} <span class="status-chip {{ $employee->status }}">{{ ucfirst(str_replace('_',' ',$employee->status)) }}</span></p>
    </div>
    <div class="admin-page-actions">
        @if($canManage)
            @if($user && ($missing->isNotEmpty() || $policies->whereNull('acknowledged_at')->isNotEmpty()))
                <form method="POST" action="{{ route('admin.hr.compliance.remind') }}">@csrf<input type="hidden" name="scope" value="all"><input type="hidden" name="user_ids[]" value="{{ $user->id }}"><button class="btn btn-outline"><i class="fas fa-bell" aria-hidden="true"></i> Send reminder</button></form>
            @endif
            <button type="button" class="btn btn-primary" data-modal-open="editEmployee"><i class="fas fa-pen" aria-hidden="true"></i> Edit</button>
        @endif
    </div>
</div>
@include('admin.hr.partials.nav')

<div class="hr-grid">
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Employment</h2>
        <dl class="hr-dl">
            <dt>Email</dt><dd>{{ $user?->email ?? '-' }}</dd>
            <dt>Phone</dt><dd>{{ $user?->phone ?? '-' }}</dd>
            <dt>Department</dt><dd>{{ $department?->name ?? '-' }}</dd>
            <dt>Position</dt><dd>{{ $position?->title ?? '-' }}{{ $position?->grade ? ' (grade '.$position->grade.')' : '' }}</dd>
            <dt>Supervisor</dt><dd>{{ $employee->supervisor?->name ?? '-' }}</dd>
            <dt>Employment type</dt><dd>{{ $employee->employment_type ?: '-' }}</dd>
            <dt>Work location</dt><dd>{{ $employee->work_location ?: '-' }}</dd>
            <dt>Start date</dt><dd>{{ optional($employee->start_date)->format('d M Y') ?: '-' }}</dd>
            <dt>Probation ends</dt><dd>{{ optional($employee->probation_end_date)->format('d M Y') ?: '-' }}</dd>
            <dt>Emergency contact</dt><dd>{{ collect([$employee->emergency_contact_name, $employee->emergency_contact_phone])->filter()->join(' · ') ?: '-' }}</dd>
        </dl>
    </section>

    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Contracts</h2>
        @forelse($employee->contracts->sortByDesc('start_date') as $c)
            <div style="padding:6px 0;border-bottom:1px solid var(--ad-border,#eee)"><strong>{{ $c->contract_type ?: 'Contract' }}</strong> <span class="status-chip {{ $c->status }}">{{ ucfirst($c->status) }}</span>
                <small class="admin-cell-hint">{{ optional($c->start_date)->format('d M Y') }} - {{ optional($c->end_date)->format('d M Y') ?: 'open-ended' }}</small></div>
        @empty
            <div class="admin-empty">No contracts recorded. Add one from the directory list.</div>
        @endforelse
    </section>

    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">HR documents</h2>
        @if($missing->isNotEmpty())
            <div class="alert alert-warning" role="status">Missing: {{ $missing->pluck('name')->join(', ') }}</div>
        @endif
        @forelse($documents as $doc)
            <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid var(--ad-border,#eee)">
                <div><a href="{{ route('admin.hr.compliance.documents.download', $doc) }}">{{ $doc->title }}</a><small class="admin-cell-hint">{{ $doc->original_name }} · {{ $doc->created_at->format('d M Y') }}{{ $doc->review_note ? ' · '.$doc->review_note : '' }}</small></div>
                <span class="status-chip {{ ['approved' => 'active', 'rejected' => 'cancelled'][$doc->status] ?? 'draft' }}">{{ ucfirst($doc->status) }}</span>
            </div>
        @empty
            <div class="admin-empty">No documents uploaded.</div>
        @endforelse
        @if($canManage && $user)
            <form method="POST" action="{{ route('admin.hr.compliance.documents.upload', $user) }}" enctype="multipart/form-data" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">@csrf
                <div><label for="ed-r" class="admin-cell-hint">Type</label><select id="ed-r" name="requirement_id"><option value="">Other</option>@foreach($requirements as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                <div><label for="ed-t" class="admin-cell-hint">Name (if Other)</label><input id="ed-t" name="title"></div>
                <div><label for="ed-f" class="admin-cell-hint">File</label><input id="ed-f" type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
                <button class="btn btn-outline btn-sm">Upload</button>
            </form>
        @endif
    </section>

    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Policies</h2>
        @forelse($policies as $a)
            <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid var(--ad-border,#eee)">
                <div><a href="{{ route('admin.hr.policies.show', $a->policy_id) }}">{{ $a->policy?->title }}</a><small class="admin-cell-hint">Shared {{ $a->created_at->format('d M Y') }}{{ $a->due_date ? ' · due '.$a->due_date->format('d M Y') : '' }}</small></div>
                @if($a->acknowledged_at)<span class="status-chip active">Acknowledged {{ $a->acknowledged_at->format('d M Y') }}</span>@elseif($a->policy?->requires_acknowledgement)<span class="status-chip draft">Pending</span>@else<span class="admin-cell-hint">For information</span>@endif
            </div>
        @empty
            <div class="admin-empty">No policies shared yet.</div>
        @endforelse
    </section>
</div>

@if($canManage)
<div class="eh-modal" id="editEmployee" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Edit employee</h2><p>{{ $user?->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}">@csrf @method('PUT')
        <input type="hidden" name="user_id" value="{{ $employee->user_id }}">
        <div class="eh-modal-body"><div class="modal-grid">
            <div class="form-group"><label>Employee number *</label><input name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" required></div>
            <div class="form-group"><label>Status *</label><select name="status">@foreach(['active','probation','on_leave','suspended','exiting','exited'] as $s)<option value="{{ $s }}" @selected($employee->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
            <div class="form-group"><label>Department</label><select name="department_id"><option value="">None</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected($employee->department_id === $d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div class="form-group"><label>Position</label><select name="position_id"><option value="">None</option>@foreach($positions as $p)<option value="{{ $p->id }}" @selected($employee->position_id === $p->id)>{{ $p->title }}</option>@endforeach</select></div>
            <div class="form-group"><label>Supervisor</label><select name="supervisor_user_id"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($employee->supervisor_user_id === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div class="form-group"><label>Employment type</label><input name="employment_type" value="{{ $employee->employment_type }}"></div>
            <div class="form-group"><label>Work location</label><input name="work_location" value="{{ $employee->work_location }}"></div>
            <div class="form-group"><label>Start date</label><input type="date" name="start_date" value="{{ optional($employee->start_date)->format('Y-m-d') }}"></div>
            <div class="form-group"><label>Probation end date</label><input type="date" name="probation_end_date" value="{{ optional($employee->probation_end_date)->format('Y-m-d') }}"></div>
            <div class="form-group"><label>Emergency contact name</label><input name="emergency_contact_name" value="{{ $employee->emergency_contact_name }}"></div>
            <div class="form-group"><label>Emergency contact phone</label><input name="emergency_contact_phone" value="{{ $employee->emergency_contact_phone }}"></div>
        </div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
</div></div>
@endif
@endsection
