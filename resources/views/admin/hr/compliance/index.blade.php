@extends('layouts.admin')
@section('title','Documents & Compliance | ElevateHer360 Administration')
@section('content')
@php $canManage = auth()->user()->hasPermission('hr.manage'); @endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Human Resources</span><h1>Staff Documents &amp; Compliance</h1><p>See who still owes HR documents or policy acknowledgements, review uploads and send reminders. Automatic reminders go out on weekday mornings (at most every {{ \App\Services\HR\HrComplianceService::AUTO_REMINDER_COOLDOWN_DAYS }} days per person).</p></div>
    <div class="admin-page-actions">@if($canManage)<button type="button" class="btn btn-outline" data-modal-open="requirementsModal"><i class="fas fa-list-check" aria-hidden="true"></i> Required documents ({{ $requirements->where('is_active', true)->count() }})</button>@endif</div>
</div>
@include('admin.hr.partials.nav')

<div class="admin-stats-grid compact">
@foreach([['staff','Active staff','fa-users'],['compliant','Fully compliant','fa-circle-check'],['missing_docs','Missing documents','fa-file-circle-exclamation'],['pending_policies','Unacknowledged policies','fa-scale-balanced']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

@if($requirements->where('is_active', true)->isEmpty())
<div class="alert alert-warning" role="status">No required documents are set up yet. @if($canManage)Use <strong>Required documents</strong> to add items such as National ID, academic certificates or tax PIN.@endif</div>
@endif

@if($pending->isNotEmpty())
<section class="admin-panel" id="review" aria-labelledby="rev-h">
    <h2 id="rev-h" style="margin-top:0;font-size:1.05rem">Documents awaiting review ({{ $pending->count() }})</h2>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Staff member</th><th>Document</th><th>Uploaded</th><th class="table-actions">Review</th></tr></thead>
        <tbody>
        @foreach($pending as $doc)
            <tr>
                <td><strong>{{ $doc->user?->name }}</strong><small class="admin-cell-hint">{{ $doc->user?->email }}</small></td>
                <td><a href="{{ route('admin.hr.compliance.documents.download', $doc) }}">{{ $doc->title }}</a><small class="admin-cell-hint">{{ $doc->original_name }}</small></td>
                <td>{{ $doc->created_at->format('d M Y') }}</td>
                <td class="table-actions">
                    @if($canManage)
                    <form method="POST" action="{{ route('admin.hr.compliance.documents.review', $doc) }}" style="display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap">@csrf
                        <input name="review_note" placeholder="Note (required to reject)" aria-label="Review note" style="max-width:220px">
                        <button name="status" value="approved" class="btn btn-primary btn-sm">Approve</button>
                        <button name="status" value="rejected" class="btn btn-outline btn-sm">Reject</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<section class="admin-panel" aria-labelledby="staff-h">
    <h2 id="staff-h" style="margin-top:0;font-size:1.05rem">Staff compliance</h2>
    <form method="GET" class="admin-toolbar" data-auto-filter>
        <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search staff..." aria-label="Search staff"></div>
        <select name="department" aria-label="Department"><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) request('department') === $d->id)>{{ $d->name }}</option>@endforeach</select>
        <select name="filter" aria-label="Show"><option value="">Everyone</option><option value="outstanding" @selected(request('filter') === 'outstanding')>Only with outstanding items</option></select>
        <button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.hr.compliance.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <form method="POST" action="{{ route('admin.hr.compliance.remind') }}" id="remindForm">@csrf
    @if($canManage)
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
        <label for="r-scope" class="admin-cell-hint" style="display:inline">Remind</label>
        <select id="r-scope" name="scope" style="width:auto"><option value="all">about documents and policies</option><option value="documents">about documents only</option><option value="policies">about policies only</option></select>
        <input name="note" placeholder="Optional personal note" maxlength="500" style="max-width:280px" aria-label="Note">
        <button class="btn btn-primary btn-sm" name="selected" value="1"><i class="fas fa-bell" aria-hidden="true"></i> Remind selected</button>
        <button class="btn btn-outline btn-sm" type="submit" formaction="{{ route('admin.hr.compliance.remind') }}" onclick="document.querySelectorAll('#remindForm [name=&quot;user_ids[]&quot;]').forEach(c=>c.checked=false)">Remind everyone outstanding</button>
    </div>
    @endif
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr>@if($canManage)<th style="width:34px"><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('#remindForm [name=&quot;user_ids[]&quot;]').forEach(c=>c.checked=this.checked)"></th>@endif<th>Staff member</th><th>Missing documents</th><th>Policies to acknowledge</th><th>Last reminded</th><th>Status</th>@if($canManage)<th class="table-actions">Upload</th>@endif</tr></thead>
        <tbody>
        @forelse($rows as $row)
            @php $ok = $row['documents'] + $row['policies'] === 0; @endphp
            <tr>
                @if($canManage)<td>@unless($ok)<input type="checkbox" name="user_ids[]" value="{{ $row['user']->id }}" aria-label="Select {{ $row['user']->name }}">@endunless</td>@endif
                <td><strong>{{ $row['user']->name }}</strong><small class="admin-cell-hint">{{ $row['user']->email }}</small></td>
                <td>{{ $row['missing'] ? implode(', ', $row['missing']) : '-' }}</td>
                <td>{{ $row['pending_policies'] ? implode(', ', $row['pending_policies']) : '-' }}</td>
                <td>{{ ! empty($lastReminded[$row['user']->id]) ? \Illuminate\Support\Carbon::parse($lastReminded[$row['user']->id])->diffForHumans() : '-' }}</td>
                <td><span class="status-chip {{ $ok ? 'active' : 'on_hold' }}">{{ $ok ? 'Compliant' : 'Outstanding' }}</span></td>
                @if($canManage)<td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="up{{ $row['user']->id }}" title="Upload a document received on paper">Upload</button></td>@endif
            </tr>
        @empty
            <tr><td colspan="7"><div class="admin-empty">No staff match.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    </form>
</section>

@if($canManage)
@foreach($rows as $row)
<div class="eh-modal" id="up{{ $row['user']->id }}" aria-hidden="true"><div class="eh-modal-dialog">
    <div class="eh-modal-header"><div><h2>Upload document</h2><p>For {{ $row['user']->name }} - saved as approved.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.compliance.documents.upload', $row['user']) }}" enctype="multipart/form-data">@csrf
        <div class="eh-modal-body"><div class="modal-grid">
            <div class="form-group"><label>Document type</label><select name="requirement_id"><option value="">Other (name it)</option>@foreach($requirements->where('is_active', true) as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
            <div class="form-group"><label>Name (if Other)</label><input name="title"></div>
            <div class="form-group" style="grid-column:1/-1"><label>File *</label><input type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
        </div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Upload</button></div>
    </form>
</div></div>
@endforeach

<div class="eh-modal" id="requirementsModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Required staff documents</h2><p>Every active staff member is asked to upload the mandatory ones.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <div class="eh-modal-body">
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Document</th><th>Mandatory</th><th>Approved</th><th>Active</th><th class="table-actions"></th></tr></thead>
            <tbody>
            @forelse($requirements as $r)
                <tr>
                    <td colspan="5" style="padding:0">
                        <form method="POST" action="{{ route('admin.hr.compliance.requirements.update', $r) }}" style="display:grid;grid-template-columns:2fr auto auto auto auto;gap:8px;align-items:center;padding:8px">@csrf @method('PUT')
                            <div><input name="name" value="{{ $r->name }}" aria-label="Name" required><input name="description" value="{{ $r->description }}" placeholder="Description / guidance" aria-label="Description" style="margin-top:4px"></div>
                            <label style="display:flex;gap:4px;align-items:center;font-weight:500"><input type="checkbox" name="is_mandatory" value="1" @checked($r->is_mandatory) style="width:auto"> Mandatory</label>
                            <span class="admin-cell-hint">{{ $r->approved_count }} approved</span>
                            <label style="display:flex;gap:4px;align-items:center;font-weight:500"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($r->is_active) style="width:auto"> Active</label>
                            <button class="btn btn-outline btn-sm">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="admin-empty">None yet.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <h3 style="font-size:1rem">Add a required document</h3>
        <form method="POST" action="{{ route('admin.hr.compliance.requirements.store') }}">@csrf
            <div class="modal-grid">
                <div class="form-group"><label for="rq-n">Name *</label><input id="rq-n" name="name" required placeholder="e.g. National ID copy"></div>
                <div class="form-group"><label for="rq-d">Description</label><input id="rq-d" name="description" placeholder="e.g. Both sides, clearly readable"></div>
                <div class="form-group"><label style="display:flex;gap:8px;align-items:center;margin-top:24px"><input type="checkbox" name="is_mandatory" value="1" checked style="width:auto"> Mandatory</label></div>
            </div>
            <button class="btn btn-primary btn-sm">Add</button>
        </form>
    </div>
</div></div>
@endif
@endsection
