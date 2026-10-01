@extends('layouts.admin')
@section('title','My KPIs | ElevateHer360')
@section('content')
@php
    $statusLabels = ['draft' => 'Draft', 'submitted' => 'Awaiting approval', 'approved' => 'Approved', 'returned' => 'Returned'];
    $totalWeight = (float) $kpis->sum('weight');
@endphp

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">People &amp; Performance</span>
    <h1>My KPIs</h1>
    <p>Set the KPIs for your contract, get them approved by your supervisor, link your tasks to them, and carry them into each quarterly appraisal.</p>
</div>
<div class="admin-page-actions">
    <a href="{{ route('staff.tasks.index') }}" class="btn btn-outline"><i class="fas fa-list-check"></i> My Tasks</a>
    @if($employee)<button type="button" class="btn btn-primary" data-modal-open="kpi-new"><i class="fas fa-plus"></i> Add KPI</button>@endif
</div>
</div>

@if($pendingReviews->isNotEmpty())
<section class="admin-panel kpi-review-panel">
    <div class="st-section-head"><h2><i class="fas fa-user-check"></i> Team KPIs awaiting your approval</h2></div>
    @foreach($pendingReviews as $employeeId => $teamKpis)
        @php $member = $teamKpis->first()->employee; @endphp
        <form method="POST" action="{{ route('staff.kpis.review', $member) }}" class="kpi-review">
            @csrf
            <h3>{{ $member->user?->name }} <small>{{ $teamKpis->first()->contract ? 'Contract from '.$teamKpis->first()->contract->start_date->format('d M Y') : 'No contract' }} · {{ (float) $teamKpis->sum('weight') }}% weight</small></h3>
            <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th style="width:34px"><input type="checkbox" data-select-all checked aria-label="Select all"></th><th>KRA</th><th>KPI</th><th>Target</th><th>Weight</th></tr></thead>
                <tbody>
                @foreach($teamKpis as $kpi)
                    <tr>
                        <td><input type="checkbox" name="kpi_ids[]" value="{{ $kpi->id }}" checked data-row-select aria-label="Select {{ $kpi->title }}"></td>
                        <td>{{ $kpi->kra }}</td>
                        <td><strong>{{ $kpi->title }}</strong>@if($kpi->measurement_method)<small class="admin-cell-hint">{{ $kpi->measurement_method }}</small>@endif</td>
                        <td>{{ $kpi->target ?: '—' }} {{ $kpi->unit }}</td>
                        <td>{{ (float) $kpi->weight }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div class="kpi-review-actions">
                <input name="review_comment" maxlength="2000" placeholder="Comment (required when returning)" aria-label="Review comment">
                <button name="decision" value="return" class="btn btn-outline btn-sm"><i class="fas fa-rotate-left"></i> Return</button>
                <button name="decision" value="approve" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Approve selected</button>
            </div>
        </form>
    @endforeach
</section>
@endif

@if(! $employee)
<div class="admin-panel"><div class="admin-empty"><i class="fas fa-id-badge"></i><strong>No employee record yet</strong><span>Ask HR to set up your employee record and contract, then set your KPIs here.</span></div></div>
@else

<section class="admin-panel">
    <div class="st-section-head">
        <h2><i class="fas fa-file-signature"></i>
            @if($contract)
                {{ $contract->contract_type ? ucfirst(str_replace('_', ' ', $contract->contract_type)).' contract' : 'Contract' }}
                · {{ $contract->start_date->format('d M Y') }} – {{ $contract->end_date?->format('d M Y') ?? 'open-ended' }}
                <span class="status-chip {{ $contract->status === 'active' ? 'active' : 'draft' }}">{{ ucfirst($contract->status) }}</span>
            @else
                KPIs (no contract on record)
            @endif
        </h2>
        @if($contracts->count() > 1)
            <form method="GET"><select name="contract" onchange="this.form.submit()" aria-label="Choose contract">
                @foreach($contracts as $option)
                    <option value="{{ $option->id }}" @selected($contract?->id === $option->id)>{{ $option->start_date->format('M Y') }} – {{ $option->end_date?->format('M Y') ?? 'open' }} ({{ $option->status }})</option>
                @endforeach
            </select></form>
        @endif
    </div>

    @if(! $contract)
        <p class="st-muted"><i class="fas fa-circle-info"></i> HR hasn’t recorded a contract for you yet. You can still set KPIs; they will apply until a contract is added.</p>
    @endif

    @if($kpis->isEmpty())
        <div class="admin-empty"><i class="fas fa-bullseye"></i><strong>No KPIs yet</strong><span>Add the KPIs your contract commits you to, grouped by key result area.</span></div>
    @else
        <div class="kpi-weight {{ abs($totalWeight - 100) < 0.01 ? 'is-ok' : 'is-off' }}">
            <i class="fas {{ abs($totalWeight - 100) < 0.01 ? 'fa-circle-check' : 'fa-scale-unbalanced' }}"></i>
            Total weight {{ rtrim(rtrim(number_format($totalWeight, 2), '0'), '.') }}%{{ abs($totalWeight - 100) < 0.01 ? '' : ' — weights should add up to 100%' }}
        </div>
        @foreach($kpis->groupBy('kra') as $kra => $kraKpis)
            <div class="kpi-kra">
                <h3>{{ $kra }} <span>{{ (float) $kraKpis->sum('weight') }}%</span></h3>
                @foreach($kraKpis as $kpi)
                    <article class="kpi-item">
                        <div>
                            <strong>{{ $kpi->title }}</strong>
                            <div class="st-task-meta">
                                @if($kpi->target)<span><i class="fas fa-flag-checkered"></i> {{ $kpi->target }} {{ $kpi->unit }}</span>@endif
                                @if($kpi->measurement_method)<span><i class="fas fa-ruler"></i> {{ $kpi->measurement_method }}</span>@endif
                                <span><i class="fas fa-weight-hanging"></i> {{ (float) $kpi->weight }}%</span>
                            </div>
                            @if($kpi->status === 'returned' && $kpi->review_comment)<p class="kpi-return-note"><i class="fas fa-comment"></i> {{ $kpi->reviewer?->name }}: {{ $kpi->review_comment }}</p>@endif
                        </div>
                        <span class="kpi-status kpi-status-{{ $kpi->status }}">{{ $statusLabels[$kpi->status] ?? $kpi->status }}</span>
                        <button type="button" class="btn btn-outline btn-sm" data-modal-open="kpi-edit-{{ $kpi->id }}"><i class="fas fa-pen"></i><span class="sr-only"> Edit {{ $kpi->title }}</span></button>
                    </article>
                @endforeach
            </div>
        @endforeach

        @if($canSubmit)
            <form method="POST" action="{{ route('staff.kpis.submit') }}" class="kpi-submit">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract?->id }}">
                <span>Draft or returned KPIs need your supervisor’s approval before they go into quarterly appraisals.</span>
                <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit for approval</button>
            </form>
        @endif
    @endif
</section>

<section class="admin-panel">
    <div class="st-section-head"><h2><i class="fas fa-calendar-days"></i> Quarterly progress &amp; appraisals</h2></div>
    @if($quarters->isEmpty())
        <div class="admin-empty"><i class="fas fa-calendar-plus"></i><strong>No quarterly cycles yet</strong><span>HR creates quarterly appraisal cycles under HR › Appraisals (type “Quarterly”). They appear here once they fall within your contract.</span></div>
    @else
    <div class="kpi-quarters">
        @foreach($quarters as $quarter)
            @php $cycle = $quarter['cycle']; $appraisal = $quarter['appraisal']; @endphp
            <article class="kpi-quarter {{ $quarter['current'] ? 'is-current' : '' }}">
                <header>
                    <strong>{{ $cycle->name }}</strong>
                    <span>{{ $cycle->start_date->format('d M') }} – {{ $cycle->end_date->format('d M Y') }}</span>
                    @if($quarter['current'])<em>Current quarter</em>@endif
                </header>
                <div class="kpi-quarter-total">{{ $quarter['done'] }}/{{ $quarter['total'] }} linked tasks done</div>
                <ul>
                    @foreach($kpis as $kpi)
                        @php $p = $quarter['per_kpi'][$kpi->id]; @endphp
                        <li><span>{{ $kpi->title }}</span><b>{{ $p['done'] }}/{{ $p['total'] }}</b></li>
                    @endforeach
                </ul>
                <footer>
                    @if($appraisal)
                        <a class="btn btn-outline btn-sm" href="{{ route('staff.performance.show', $appraisal) }}"><i class="fas fa-clipboard-check"></i> Appraisal · {{ ucfirst(str_replace('_', ' ', $appraisal->status)) }}</a>
                    @elseif($kpis->where('status', 'approved')->isNotEmpty())
                        <form method="POST" action="{{ route('staff.kpis.quarters.start', $cycle) }}">
                            @csrf
                            <input type="hidden" name="contract_id" value="{{ $contract?->id }}">
                            <button class="btn btn-primary btn-sm"><i class="fas fa-play"></i> Start {{ $cycle->name }} appraisal</button>
                        </form>
                    @else
                        <span class="st-muted">Get your KPIs approved to start this appraisal.</span>
                    @endif
                </footer>
            </article>
        @endforeach
    </div>
    @endif
</section>

<datalist id="kra-suggestions">
    @foreach($kpis->pluck('kra')->unique() as $kra)<option value="{{ $kra }}">@endforeach
</datalist>

<div class="eh-modal" id="kpi-new" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kpi-new-title" @if($errors->any() && ! old('kpi_id')) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('staff.kpis.store') }}">
    @csrf
    <input type="hidden" name="contract_id" value="{{ $contract?->id }}">
    <div class="eh-modal-header">
        <div><h2 id="kpi-new-title">Add KPI</h2><p>{{ $contract ? 'For your contract starting '.$contract->start_date->format('d M Y') : 'No contract on record' }}</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any() && ! old('kpi_id'))<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
        @include('staff.kpis._fields', ['prefix' => 'kpi-new'])
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add KPI</button>
    </div>
</form>
</div>
</div>

@foreach($kpis as $kpi)
<div class="eh-modal" id="kpi-edit-{{ $kpi->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kpi-edit-{{ $kpi->id }}-title">
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('staff.kpis.update', $kpi) }}">
    @csrf @method('PUT')
    <input type="hidden" name="kpi_id" value="{{ $kpi->id }}">
    <div class="eh-modal-header">
        <div><h2 id="kpi-edit-{{ $kpi->id }}-title">Edit KPI</h2><p>{{ $kpi->title }}</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @unless($kpi->isEditableByOwner())<div class="icm-notice"><span><i class="fas fa-circle-info"></i> This KPI is {{ strtolower($statusLabels[$kpi->status]) }}. Saving changes moves it back to draft for re-approval. Quarterly appraisals already started keep their copy.</span></div>@endunless
        @include('staff.kpis._fields', ['prefix' => 'kpi-'.$kpi->id, 'kpi' => $kpi])
    </div>
    <div class="eh-modal-footer">
        @if($kpi->status !== 'approved')
            <button type="submit" form="kpi-delete-{{ $kpi->id }}" class="btn btn-outline st-delete"><i class="fas fa-trash"></i> Delete</button>
        @endif
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </div>
</form>
<form method="POST" action="{{ route('staff.kpis.destroy', $kpi) }}" id="kpi-delete-{{ $kpi->id }}" data-delete-form data-confirm="Delete the KPI “{{ $kpi->title }}”? Tasks linked to it stay, unlinked.">@csrf @method('DELETE')</form>
</div>
</div>
@endforeach
@endif
@endsection
