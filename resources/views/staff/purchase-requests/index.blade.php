@extends('layouts.admin')
@section('title','My Purchase Requests | ElevateHer360')
@section('content')
@php
    $stages = ['submitted' => 'Submitted', 'manager_approved' => 'Manager', 'finance_approved' => 'Finance', 'procurement_review' => 'Procurement', 'approved' => 'Approved'];
    $stageKeys = array_keys($stages);
    $openCreate = $errors->any();
@endphp

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Self-service</span>
    <h1>My Purchase Requests</h1>
    <p>Request items or services you need for your work and follow each request through approval.</p>
</div>
<div class="admin-page-actions">
    <x-export-buttons />
    <button type="button" class="btn btn-primary" data-modal-open="spr-new"><i class="fas fa-plus"></i> New request</button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([['draft','Drafts','fa-pen'],['pending','Awaiting approval','fa-hourglass-half'],['approved','Approved','fa-circle-check'],['rejected','Rejected','fa-circle-xmark']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search request number, item or justification..." aria-label="Search requests"></div>
    <select name="status" aria-label="Status"><option value="">All statuses</option>@foreach(\App\Http\Controllers\Staff\StaffPurchaseRequestController::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('staff.purchase-requests.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="spr-list">
@forelse($requests as $pr)
    @php $reached = array_search($pr->status, $stageKeys, true); @endphp
    <article class="spr-card spr-{{ $pr->status }}">
        <header class="spr-card-head">
            <div>
                <strong>{{ $pr->request_number }}</strong>
                <span class="status-chip {{ $pr->status }}">{{ ucfirst(str_replace('_',' ',$pr->status)) }}</span>
            </div>
            <div class="spr-total">{{ $pr->currency ?: 'UGX' }} {{ number_format((float)$pr->estimated_total,2) }}</div>
        </header>
        <p class="spr-items">{{ $pr->items->pluck('item_name')->take(4)->implode(', ') }}@if($pr->items->count() > 4) +{{ $pr->items->count() - 4 }} more @endif</p>
        <div class="spr-meta">
            <span><i class="fas fa-calendar-plus"></i> Raised {{ $pr->created_at->format('d M Y') }}</span>
            @if($pr->required_date)<span><i class="fas fa-calendar-day"></i> Needed by {{ $pr->required_date->format('d M Y') }}</span>@endif
            @if($pr->department)<span><i class="fas fa-building"></i> {{ $pr->department }}</span>@endif
        </div>

        @if($pr->status !== 'draft' && $pr->status !== 'rejected')
        <ol class="spr-steps" aria-label="Approval progress">
            @foreach($stages as $key => $label)
                @php $i = $loop->index; $state = $reached === false ? (in_array($pr->status, ['ordered','received'], true) ? 'done' : '') : ($i < $reached ? 'done' : ($i === $reached ? 'current' : '')); @endphp
                <li class="{{ $state }}">{{ $label }}</li>
            @endforeach
        </ol>
        @endif

        @php $lastDecision = $pr->approvals->sortByDesc('acted_at')->first(); @endphp
        @if($lastDecision && ($lastDecision->comments || in_array($lastDecision->decision, ['rejected','returned'], true)))
            <p class="spr-note"><i class="fas fa-comment-dots"></i> <strong>{{ ucfirst($lastDecision->approval_stage) }} · {{ ucfirst($lastDecision->decision) }}</strong>@if($lastDecision->user) by {{ $lastDecision->user->name }}@endif{{ $lastDecision->comments ? ': '.$lastDecision->comments : '' }}</p>
        @endif

        <details class="spr-details">
            <summary>View {{ $pr->items->count() }} {{ \Illuminate\Support\Str::plural('item', $pr->items->count()) }}</summary>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Item</th><th>Specification</th><th>Qty</th><th>Unit cost</th><th>Total</th></tr></thead><tbody>
            @foreach($pr->items as $item)
                <tr><td>{{ $item->item_name }}@if($item->is_asset) <span class="status-chip">Asset</span>@endif</td><td>{{ $item->specification ?: '—' }}</td><td>{{ rtrim(rtrim(number_format((float)$item->quantity,2),'0'),'.') }} {{ $item->unit }}</td><td>{{ number_format((float)$item->estimated_unit_cost,2) }}</td><td>{{ number_format((float)$item->estimated_total,2) }}</td></tr>
            @endforeach
            </tbody></table></div>
            @if($pr->justification)<p class="spr-justification"><strong>Justification:</strong> {{ $pr->justification }}</p>@endif
        </details>

        @if($pr->status === 'draft')
        <div class="spr-actions">
            <form method="POST" action="{{ route('staff.purchase-requests.destroy', $pr) }}" data-delete-form data-confirm="Delete draft {{ $pr->request_number }}?">@csrf @method('DELETE')<button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button></form>
            <form method="POST" action="{{ route('staff.purchase-requests.submit', $pr) }}">@csrf<button class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Submit for approval</button></form>
        </div>
        @endif
    </article>
@empty
    <div class="admin-empty"><i class="fas fa-cart-shopping"></i><strong>No purchase requests yet</strong><span>Use “New request” to ask for items or services you need.</span></div>
@endforelse
</div>
<div class="admin-pagination">{{ $requests->links() }}</div>
</div>

{{-- New request --}}
<div class="eh-modal" id="spr-new" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="spr-new-title" @if($openCreate) data-modal-autoopen @endif>
<div class="eh-modal-dialog eh-modal-lg">
<form method="POST" action="{{ route('staff.purchase-requests.store') }}">
    @csrf
    <div class="eh-modal-header">
        <div><h2 id="spr-new-title">New purchase request</h2><p>List what you need. It goes to your manager, finance and procurement for approval.</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any())<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
        <div class="modal-grid">
            <div class="form-group"><label for="spr-required">Needed by</label><input id="spr-required" type="date" name="required_date" value="{{ old('required_date') }}" min="{{ today()->toDateString() }}"></div>
            <div class="form-group"><label for="spr-department">Department</label><x-list-select id="spr-department" name="department" :options="$departmentOptions" :selected="old('department', $defaultDepartment)" placeholder="Select department" :manage-url="\App\Support\MasterListAccess::departmentsUrl(auth()->user())" manage-label="Manage departments" empty-hint="No departments have been set up yet." /></div>
            <div class="form-group"><label for="spr-programme">Programme</label><select id="spr-programme" name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}" @selected((int) old('programme_id') === $x->id)>{{ $x->name }}</option>@endforeach</select></div>
            <div class="form-group"><label for="spr-project">Project</label><select id="spr-project" name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}" @selected((int) old('project_id') === $x->id)>{{ $x->name }}</option>@endforeach</select></div>
            <div class="form-group"><label for="spr-funding">Funding source</label><x-list-select id="spr-funding" name="funding_source" :options="$fundingSourceOptions" :selected="old('funding_source')" placeholder="Select funding source" :manage-url="\App\Support\MasterListAccess::fundingSourcesUrl(auth()->user())" manage-label="Manage funding sources" empty-hint="No funding sources have been set up yet." /></div>
            <div class="form-group"><label for="spr-currency">Currency</label><input id="spr-currency" name="currency" value="{{ old('currency', 'UGX') }}" maxlength="3"></div>
            <div class="form-group full"><label for="spr-justification">Why is this needed?</label><textarea id="spr-justification" name="justification" rows="2">{{ old('justification') }}</textarea></div>
        </div>

        <div class="spr-items-head"><h3>Items</h3><button type="button" class="btn btn-outline btn-sm" data-spr-add><i class="fas fa-plus"></i> Add item</button></div>
        <div data-spr-items>
            @foreach(old('items', [['quantity' => 1]]) as $i => $item)
            <div class="spr-item-row">
                <div class="form-group"><label>Item / service *</label><input name="items[{{ $i }}][item_name]" value="{{ $item['item_name'] ?? '' }}" required maxlength="190"></div>
                <div class="form-group"><label>Qty *</label><input type="number" step=".01" min=".01" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></div>
                <div class="form-group"><label>Unit</label><input name="items[{{ $i }}][unit]" value="{{ $item['unit'] ?? '' }}" maxlength="50" placeholder="pcs"></div>
                <div class="form-group"><label>Unit cost</label><input type="number" step=".01" min="0" name="items[{{ $i }}][estimated_unit_cost]" value="{{ $item['estimated_unit_cost'] ?? '' }}"></div>
                <button type="button" class="st-icon-btn spr-remove" data-spr-remove aria-label="Remove item" @if($loop->count === 1) hidden @endif><i class="fas fa-xmark"></i></button>
                <div class="form-group spr-spec"><label>Specification</label><input name="items[{{ $i }}][specification]" value="{{ $item['specification'] ?? '' }}" placeholder="Model, size, colour…"></div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-outline" name="submit_now" value="0"><i class="fas fa-floppy-disk"></i> Save draft</button>
        <button class="btn btn-primary" name="submit_now" value="1"><i class="fas fa-paper-plane"></i> Submit for approval</button>
    </div>
</form>
</div>
</div>

<style>
.spr-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:12px}
.spr-card{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border:1px solid #e4e7ec;border-left:3px solid #d0d5dd;border-radius:10px;background:#fff;min-width:0}
.spr-card.spr-draft{border-left-color:#98a2b3}
.spr-card.spr-rejected{border-left-color:#d92d20}
.spr-card.spr-approved,.spr-card.spr-ordered,.spr-card.spr-received{border-left-color:#12b76a}
.spr-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.spr-card-head>div:first-child{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.spr-total{font-weight:800;color:#172033;white-space:nowrap}
.spr-items{margin:0;color:#344054;font-size:.85rem}
.spr-meta{display:flex;flex-wrap:wrap;gap:4px 14px;color:#667085;font-size:.74rem}
.spr-meta i{margin-right:3px}
.spr-steps{display:flex;margin:2px 0 0;padding:0;list-style:none;counter-reset:spr}
.spr-steps li{flex:1;position:relative;padding-top:16px;color:#98a2b3;font-size:.66rem;font-weight:700;text-align:center}
.spr-steps li::before{content:"";position:absolute;top:4px;left:50%;width:9px;height:9px;margin-left:-4.5px;border-radius:50%;background:#d0d5dd;z-index:1}
.spr-steps li::after{content:"";position:absolute;top:8px;left:-50%;width:100%;height:2px;background:#e4e7ec}
.spr-steps li:first-child::after{display:none}
.spr-steps li.done,.spr-steps li.current{color:#067647}
.spr-steps li.done::before{background:#12b76a}
.spr-steps li.done::after,.spr-steps li.current::after{background:#12b76a}
.spr-steps li.current::before{background:#fff;border:2px solid #12b76a;box-sizing:border-box}
.spr-note{margin:0;padding:6px 9px;border-radius:7px;background:#fffaeb;color:#7a2e0e;font-size:.76rem}
.spr-details summary{cursor:pointer;color:#800000;font-size:.78rem;font-weight:700}
.spr-details .admin-table-wrap{margin-top:8px}
.spr-justification{margin:8px 0 0;color:#475467;font-size:.78rem}
.spr-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:auto}
.spr-actions form{margin:0}
.spr-items-head{display:flex;justify-content:space-between;align-items:center;margin:16px 0 8px}
.spr-items-head h3{margin:0;font-size:.95rem}
.spr-item-row{display:grid;grid-template-columns:minmax(0,2.2fr) minmax(70px,.6fr) minmax(70px,.7fr) minmax(90px,.9fr) 26px;gap:8px;align-items:end;margin-bottom:10px;padding:10px;border:1px solid #eef0f3;border-radius:9px;background:#fcfcfd}
.spr-item-row .form-group{margin:0}
.spr-item-row label{font-size:.7rem}
.spr-item-row .spr-spec{grid-column:1/-1}
.spr-remove{margin-bottom:10px}
@media(max-width:900px){.spr-list{grid-template-columns:1fr}}
@media(max-width:560px){.spr-item-row{grid-template-columns:1fr 1fr}.spr-item-row .form-group:first-child{grid-column:1/-1}.spr-remove{grid-column:2;justify-self:end;margin:0}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-spr-items]');
    const add = document.querySelector('[data-spr-add]');
    if (!list || !add) return;
    let next = list.children.length;
    const syncRemove = () => list.querySelectorAll('[data-spr-remove]').forEach(b => { b.hidden = list.children.length === 1; });
    add.addEventListener('click', () => {
        const row = list.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/items\[\d+\]/, `items[${next}]`);
            input.value = input.type === 'number' && input.name.endsWith('[quantity]') ? '1' : '';
        });
        next++;
        list.appendChild(row);
        syncRemove();
        row.querySelector('input')?.focus();
    });
    list.addEventListener('click', e => {
        const btn = e.target.closest('[data-spr-remove]');
        if (btn && list.children.length > 1) { btn.closest('.spr-item-row').remove(); syncRemove(); }
    });
});
</script>
@endsection
