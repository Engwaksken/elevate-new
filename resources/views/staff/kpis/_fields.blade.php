{{-- Contract KPI fields. Expects $prefix, optional $kpi and $kraSuggestions. --}}
@php $kpi ??= null; @endphp
<div class="modal-grid">
    <div class="form-group full">
        <label for="{{ $prefix }}-kra">Key result area (KRA) *</label>
        <input id="{{ $prefix }}-kra" name="kra" value="{{ $kpi?->kra }}" required maxlength="255" list="kra-suggestions" placeholder="e.g. Graduate placement">
        <small class="form-hint">The area of your contract this KPI belongs to. KPIs with the same KRA are grouped together in each quarterly appraisal.</small>
    </div>
    <div class="form-group full"><label for="{{ $prefix }}-title">KPI *</label><input id="{{ $prefix }}-title" name="title" value="{{ $kpi?->title }}" required maxlength="255" placeholder="e.g. Graduates placed in decent jobs"></div>
    <div class="form-group"><label for="{{ $prefix }}-target">Target</label><input id="{{ $prefix }}-target" name="target" value="{{ $kpi?->target }}" maxlength="255" placeholder="e.g. 40 per quarter"></div>
    <div class="form-group"><label for="{{ $prefix }}-unit">Unit</label><input id="{{ $prefix }}-unit" name="unit" value="{{ $kpi?->unit }}" maxlength="100" placeholder="e.g. graduates, %, reports"></div>
    <div class="form-group"><label for="{{ $prefix }}-measure">How it is measured</label><input id="{{ $prefix }}-measure" name="measurement_method" value="{{ $kpi?->measurement_method }}" maxlength="255" placeholder="e.g. Placement records in the jobs module"></div>
    <div class="form-group"><label for="{{ $prefix }}-weight">Weight % *</label><input id="{{ $prefix }}-weight" type="number" name="weight" min="0" max="100" step="0.5" value="{{ $kpi ? (float) $kpi->weight : '' }}" required></div>
    <div class="form-group full"><label for="{{ $prefix }}-description">Details</label><textarea id="{{ $prefix }}-description" name="description" rows="2" maxlength="5000">{{ $kpi?->description }}</textarea></div>
</div>
