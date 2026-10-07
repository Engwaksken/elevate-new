@php
    $selectedBranchIds = collect(old('branch_ids', $cohort->exists
        ? $cohort->branches->pluck('id')->all()
        : ($cohort->branch_id ? [$cohort->branch_id] : [])))
        ->map(fn ($branchId) => (string) $branchId)
        ->all();
@endphp
<div class="eh-modal" id="{{ $id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>{{ $title }}</h2><p>Complete the cohort setup details.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method==='PUT') @method('PUT') @endif
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Name *</label><input name="name" value="{{ $cohort->name }}" placeholder="e.g. Kampala Cohort 1 - 2026" required><small class="form-hint">Required. Use a clear cohort name that participants and staff can recognise.</small></div>
<div class="form-group"><label>Code</label><input name="code" value="{{ $cohort->code }}" placeholder="e.g. KLA-C1-26"><small class="form-hint">Optional. Enter a unique short cohort code.</small></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['planned','open','active','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(($cohort->status ?: 'planned')===$s)>{{ ucfirst($s) }}</option>@endforeach</select><small class="form-hint">Required. Select the current cohort lifecycle status.</small></div>
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $p)<option value="{{ $p->id }}" @selected((string)$cohort->programme_id===(string)$p->id)>{{ $p->name }}</option>@endforeach</select><small class="form-hint">Optional. Link the cohort to its programme.</small></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $p)<option value="{{ $p->id }}" @selected((string)$cohort->project_id===(string)$p->id)>{{ $p->name }}</option>@endforeach</select><small class="form-hint">Optional. Link the cohort to a specific project.</small></div>
<div class="form-group full"><label>Branches</label><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:8px;padding:10px;border:1px solid var(--border-color,#d0d5dd);border-radius:8px;max-height:180px;overflow:auto">@forelse($branches as $b)<label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" @checked(in_array((string)$b->id,$selectedBranchIds,true))> {{ $b->name }}</label>@empty<span class="form-hint">No branches are available.</span>@endforelse</div><small class="form-hint">Optional. Select every branch where this cohort will be delivered.</small></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="{{ optional($cohort->start_date)->format('Y-m-d') }}"><small class="form-hint">Optional. Choose the cohort start date.</small></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date" value="{{ optional($cohort->end_date)->format('Y-m-d') }}"><small class="form-hint">Optional. Must be on or after the start date.</small></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method==='POST' ? 'Create Cohort':'Save Changes' }}</button></div>
</form></div></div>
