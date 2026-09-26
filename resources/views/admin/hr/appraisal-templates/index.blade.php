@extends('layouts.admin')
@section('title','KPI & Appraisal Templates | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Human Resources</span>
    <h1>KPI & Appraisal Templates</h1>
    <p>Upload and manage WITU appraisal, OKR/KPI and behavioural workbook formats for use in staff appraisals.</p>
</div>

<div class="admin-page-actions">
    <button class="btn btn-primary" type="button" data-modal-open="uploadKpiTemplate">
        <i class="fas fa-file-excel"></i> Upload Template
    </button>
</div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-error">
@foreach($errors->all() as $error)
<div>{{ $error }}</div>
@endforeach
</div>
@endif

<div class="admin-panel">
<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Template</th>
    <th>Type</th>
    <th>Quarter</th>
    <th>Year</th>
    <th>Items</th>
    <th>Source</th>
    <th>Status</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>

<tbody>
@forelse($templates as $template)
<tr>
    <td><strong>{{ $template->name }}</strong></td>

    <td>{{ ucwords(str_replace('_',' ',$template->template_type)) }}</td>

    <td>{{ $template->quarter ?: '—' }}</td>

    <td>{{ $template->year ?: '—' }}</td>

    <td>{{ $template->items_count }}</td>

    <td>
        <span>{{ $template->source_file ?: '—' }}</span>
        @if($template->source_sheet)
            <small class="admin-cell-hint">{{ $template->source_sheet }}</small>
        @endif
    </td>

    <td>
        <span class="status-chip {{ $template->is_active ? 'active' : 'inactive' }}">
            {{ $template->is_active ? 'Active' : 'Inactive' }}
        </span>
    </td>

    <td class="table-actions">
        <div class="action-group">

            <button
                type="button"
                class="btn-icon"
                title="Edit template"
                data-modal-open="editKpiTemplate{{ $template->id }}"
            >
                <i class="fas fa-pen"></i>
            </button>

            <button
                type="button"
                class="btn-icon danger"
                title="Delete template"
                data-modal-open="deleteKpiTemplate{{ $template->id }}"
            >
                <i class="fas fa-trash"></i>
            </button>

        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8">
        <div class="admin-empty">No KPI/Appraisal templates uploaded yet.</div>
    </td>
</tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">
    {{ $templates->links() }}
</div>
</div>

{{-- Upload Template --}}
<div class="eh-modal" id="uploadKpiTemplate" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">

<div class="eh-modal-header">
<div>
    <h2>Upload WITU KPI/Appraisal Workbook</h2>
    <p>Upload the Excel format used by HR. The system reads KRA/KPI/OKR structure from the workbook.</p>
</div>

<button type="button" class="eh-modal-close" data-modal-close>
    <i class="fas fa-xmark"></i>
</button>
</div>

<form
    method="POST"
    enctype="multipart/form-data"
    action="{{ route('admin.hr.kpi-templates.store') }}"
>
@csrf

<div class="eh-modal-body">
<div class="modal-grid">

<div class="form-group full">
    <label>Template Name *</label>

    <input
        name="name"
        value="{{ old('name') }}"
        required
        placeholder="e.g. WITU Performance Appraisal Q2 2026"
    >

    <small class="form-hint">
        Use a clear name showing the appraisal or KPI period.
    </small>
</div>

<div class="form-group">
    <label>Template Type *</label>

    <select name="template_type" required>
        <option value="performance_appraisal">Performance Appraisal</option>
        <option value="okr_scorecard">OKR / KPI Scorecard</option>
        <option value="behavioral">Behavioural Competencies</option>
    </select>

    <small class="form-hint">
        Choose the structure that should be imported from the workbook.
    </small>
</div>

<div class="form-group">
    <label>Quarter</label>

    <input
        name="quarter"
        value="{{ old('quarter') }}"
        placeholder="e.g. Q2"
    >
</div>

<div class="form-group">
    <label>Year</label>

    <input
        type="number"
        name="year"
        value="{{ old('year') }}"
        min="2020"
        max="2100"
        placeholder="e.g. 2026"
    >
</div>

<div class="form-group full">
    <label>Excel Workbook *</label>

    <input
        type="file"
        name="file"
        accept=".xlsx,.xls"
        required
    >

    <small class="form-hint">
        Upload the approved WITU appraisal/KPI workbook.
    </small>
</div>

</div>
</div>

<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>
        Cancel
    </button>

    <button class="btn btn-primary">
        Import Template
    </button>
</div>

</form>
</div>
</div>

{{-- Edit + Delete Modals --}}
@foreach($templates as $template)

<div
    class="eh-modal"
    id="editKpiTemplate{{ $template->id }}"
    aria-hidden="true"
>
<div class="eh-modal-dialog">

<div class="eh-modal-header">
<div>
    <h2>Edit KPI/Appraisal Template</h2>
    <p>{{ $template->name }}</p>
</div>

<button type="button" class="eh-modal-close" data-modal-close>
    <i class="fas fa-xmark"></i>
</button>
</div>

<form
    method="POST"
    action="{{ route('admin.hr.kpi-templates.update',$template) }}"
>
@csrf
@method('PUT')

<div class="eh-modal-body">
<div class="modal-grid">

<div class="form-group full">
    <label>Template Name *</label>

    <input
        name="name"
        value="{{ old('name',$template->name) }}"
        required
        placeholder="e.g. WITU Performance Appraisal Q2 2026"
    >

    <small class="form-hint">
        Update the display name used by HR when assigning the template.
    </small>
</div>

<div class="form-group">
    <label>Template Type *</label>

    <select name="template_type" required>
        <option
            value="performance_appraisal"
            @selected($template->template_type==='performance_appraisal')
        >
            Performance Appraisal
        </option>

        <option
            value="okr_scorecard"
            @selected($template->template_type==='okr_scorecard')
        >
            OKR / KPI Scorecard
        </option>

        <option
            value="behavioral"
            @selected($template->template_type==='behavioral')
        >
            Behavioural Competencies
        </option>
    </select>

    <small class="form-hint">
        Changing this updates the template classification only; it does not re-import the workbook.
    </small>
</div>

<div class="form-group">
    <label>Quarter</label>

    <input
        name="quarter"
        value="{{ old('quarter',$template->quarter) }}"
        placeholder="e.g. Q2"
    >

    <small class="form-hint">
        Optional. Identify the appraisal or KPI quarter.
    </small>
</div>

<div class="form-group">
    <label>Year</label>

    <input
        type="number"
        name="year"
        value="{{ old('year',$template->year) }}"
        min="2020"
        max="2100"
        placeholder="e.g. 2026"
    >

    <small class="form-hint">
        Optional. Enter the reporting year.
    </small>
</div>

<div class="form-group full">
    <label class="eh-choice-card">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked($template->is_active)
        >

        <span class="eh-choice-card__text">
            Active template
        </span>
    </label>

    <small class="form-hint">
        Inactive templates remain stored but should not be offered for new appraisal assignments.
    </small>
</div>

<div class="form-group full">
    <div class="template-source-note">
        <strong>Source workbook</strong>
        <span>{{ $template->source_file ?: 'No source file recorded' }}</span>

        @if($template->source_sheet)
            <small>{{ $template->source_sheet }}</small>
        @endif
    </div>

    <small class="form-hint">
        Editing metadata does not change imported KPI items. Upload a new template when the workbook structure itself changes.
    </small>
</div>

</div>
</div>

<div class="eh-modal-footer">
    <button
        type="button"
        class="btn btn-outline"
        data-modal-close
    >
        Cancel
    </button>

    <button class="btn btn-primary">
        <i class="fas fa-floppy-disk"></i>
        Save Changes
    </button>
</div>

</form>
</div>
</div>

<div
    class="eh-modal"
    id="deleteKpiTemplate{{ $template->id }}"
    aria-hidden="true"
>
<div class="eh-modal-dialog eh-modal-sm">

<div class="eh-modal-header">
<div>
    <h2>Delete Template?</h2>
    <p>{{ $template->name }}</p>
</div>

<button type="button" class="eh-modal-close" data-modal-close>
    <i class="fas fa-xmark"></i>
</button>
</div>

<div class="eh-modal-body">
    <p>
        Only delete templates that are no longer needed. Existing appraisal data may depend on this template.
    </p>
</div>

<div class="eh-modal-footer">
    <button
        type="button"
        class="btn btn-outline"
        data-modal-close
    >
        Cancel
    </button>

    <form
        method="POST"
        action="{{ route('admin.hr.kpi-templates.destroy',$template) }}"
    >
        @csrf
        @method('DELETE')

        <button class="btn btn-danger">
            Delete
        </button>
    </form>
</div>

</div>
</div>

@endforeach

@endsection
