{{-- Vacancy fields for create and edit. $vacancy is null when creating. --}}
@php
    $v = $vacancy ?? null;
    $f = fn ($key, $default = null) => old($key, $v ? $v->{$key} : $default);
    $criteria = old('criteria', $v?->criteria ?: [
        ['label' => '', 'weight' => 3, 'must_have' => false],
        ['label' => '', 'weight' => 2, 'must_have' => false],
        ['label' => '', 'weight' => 1, 'must_have' => false],
    ]);
    $formId = $v ? 'vac-'.$v->id : 'vac-new';
    $tabs = [
        'job' => ['label' => 'Job details', 'icon' => 'fa-briefcase', 'fields' => ['title', 'reference', 'department_id', 'location', 'employment_type', 'salary_range', 'positions', 'closing_date', 'status']],
        'description' => ['label' => 'Description', 'icon' => 'fa-align-left', 'fields' => ['summary', 'description', 'responsibilities', 'requirements']],
        'criteria' => ['label' => 'Screening criteria', 'icon' => 'fa-robot', 'fields' => ['min_education', 'min_experience_years', 'skills', 'criteria']],
        'form' => ['label' => 'Application form', 'icon' => 'fa-file-lines', 'fields' => ['application_questions', 'cv_required']],
    ];
@endphp
<x-form-tabs :id="$formId" label="Vacancy details" :tabs="$tabs">
<x-form-tab name="job"><div class="modal-grid">
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-title">Job title *</label><input id="{{ $formId }}-title" name="title" value="{{ $f('title') }}" required maxlength="190"></div>
    <div class="form-group"><label for="{{ $formId }}-ref">Reference</label><input id="{{ $formId }}-ref" name="reference" value="{{ $f('reference') }}" placeholder="e.g. WITU/HR/2026/04"></div>
    <div class="form-group"><label for="{{ $formId }}-dept">Department</label><select id="{{ $formId }}-dept" name="department_id"><option value="">None</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) $f('department_id') === $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="form-group"><label for="{{ $formId }}-loc">Location</label><input id="{{ $formId }}-loc" name="location" value="{{ $f('location') }}" placeholder="e.g. Kampala / Remote"></div>
    <div class="form-group"><label for="{{ $formId }}-type">Employment type</label><select id="{{ $formId }}-type" name="employment_type"><option value="">Select</option>@foreach(\App\Models\RecruitmentVacancy::EMPLOYMENT_TYPES as $t)<option @selected($f('employment_type') === $t)>{{ $t }}</option>@endforeach</select></div>
    <div class="form-group"><label for="{{ $formId }}-sal">Salary range</label><input id="{{ $formId }}-sal" name="salary_range" value="{{ $f('salary_range') }}" placeholder="Optional - shown publicly"></div>
    <div class="form-group"><label for="{{ $formId }}-pos">Positions</label><input id="{{ $formId }}-pos" type="number" min="1" name="positions" value="{{ $f('positions', 1) }}"></div>
    <div class="form-group"><label for="{{ $formId }}-close">Closing date</label><input id="{{ $formId }}-close" type="date" name="closing_date" value="{{ optional($v?->closing_date)->format('Y-m-d') ?? old('closing_date') }}"></div>
    <div class="form-group"><label for="{{ $formId }}-status">Status *</label><select id="{{ $formId }}-status" name="status">@foreach(['draft' => 'Draft (not public)', 'open' => 'Open - accepting applications', 'closed' => 'Closed', 'filled' => 'Filled'] as $k => $l)<option value="{{ $k }}" @selected($f('status', 'draft') === $k)>{{ $l }}</option>@endforeach</select></div>
</div></x-form-tab>
<x-form-tab name="description"><div class="modal-grid">
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-sum">Short summary</label><textarea id="{{ $formId }}-sum" name="summary" rows="2" maxlength="1000" placeholder="One or two sentences shown on the job list">{{ $f('summary') }}</textarea></div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-desc">About the role</label><textarea id="{{ $formId }}-desc" name="description" rows="4">{{ $f('description') }}</textarea></div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-resp">Key responsibilities</label><textarea id="{{ $formId }}-resp" name="responsibilities" rows="5" placeholder="One per line">{{ $f('responsibilities') }}</textarea></div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-req">Requirements</label><textarea id="{{ $formId }}-req" name="requirements" rows="5" placeholder="Qualifications, experience, competencies...">{{ $f('requirements') }}</textarea></div>
</div></x-form-tab>
<x-form-tab name="criteria">
    <p class="form-hint" style="margin-top:0">AI screening scores every applicant 0-100 against these criteria (weight 1-10). A <strong>must-have</strong> criterion that is clearly not met marks the applicant as a weak match. Names and contact details are never sent to the AI.</p>
    <div class="modal-grid">
        <div class="form-group"><label for="{{ $formId }}-edu">Minimum education</label><select id="{{ $formId }}-edu" name="min_education"><option value="">Not required</option>@foreach(\App\Models\RecruitmentVacancy::EDUCATION_LEVELS as $k => $l)<option value="{{ $k }}" @selected($f('min_education') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="form-group"><label for="{{ $formId }}-exp">Minimum years of experience</label><input id="{{ $formId }}-exp" type="number" min="0" max="40" name="min_experience_years" value="{{ $f('min_experience_years') }}"></div>
        <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-skills">Key skills</label><input id="{{ $formId }}-skills" name="skills" value="{{ old('skills', implode(', ', $v?->skills ?? [])) }}" placeholder="Comma separated, e.g. M&E, Excel, report writing, KoboToolbox"></div>
    </div>
    <fieldset style="border:0;padding:0;margin:0">
        <legend style="font-weight:700;margin-bottom:6px">Job criteria</legend>
        <div data-criteria-rows="{{ $formId }}">
            @foreach($criteria as $i => $c)
                <div class="hr-criterion" style="display:grid;grid-template-columns:1fr 90px auto;gap:8px;align-items:center;margin-bottom:6px">
                    <input name="criteria[{{ $i }}][label]" value="{{ $c['label'] ?? '' }}" placeholder="e.g. Experience managing donor-funded projects" aria-label="Criterion {{ $i + 1 }}">
                    <select name="criteria[{{ $i }}][weight]" aria-label="Weight for criterion {{ $i + 1 }}">@for($w = 1; $w <= 10; $w++)<option value="{{ $w }}" @selected((int) ($c['weight'] ?? 1) === $w)>Weight {{ $w }}</option>@endfor</select>
                    <label style="display:flex;gap:6px;align-items:center;font-weight:500;white-space:nowrap"><input type="checkbox" name="criteria[{{ $i }}][must_have]" value="1" @checked(! empty($c['must_have'])) style="width:auto"> Must have</label>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline btn-sm" data-add-criterion="{{ $formId }}"><i class="fas fa-plus" aria-hidden="true"></i> Add criterion</button>
    </fieldset>
</x-form-tab>
<x-form-tab name="form"><div class="modal-grid">
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $formId }}-aq">Extra application questions</label><textarea id="{{ $formId }}-aq" name="application_questions" rows="4" placeholder="One question per line, e.g.&#10;Why do you want to work with women and girls?&#10;What is your notice period?">{{ old('application_questions', implode("\n", $v?->application_questions ?? [])) }}</textarea><small class="form-hint">Every applicant must answer these. Answers are included in AI screening.</small></div>
    <div class="form-group" style="grid-column:1/-1"><label style="display:flex;gap:8px;align-items:center"><input type="hidden" name="cv_required" value="0"><input type="checkbox" name="cv_required" value="1" @checked($f('cv_required', true)) style="width:auto"> Require a CV upload (PDF or Word)</label></div>
</div></x-form-tab>
</x-form-tabs>
<script>
(function () {
    var btn = document.querySelector('[data-add-criterion="{{ $formId }}"]');
    var rows = document.querySelector('[data-criteria-rows="{{ $formId }}"]');
    if (!btn || !rows) return;
    btn.addEventListener('click', function () {
        var i = rows.children.length;
        if (i >= 20) return;
        var row = rows.children[0].cloneNode(true);
        row.querySelectorAll('input,select').forEach(function (el) {
            el.name = el.name.replace(/criteria\[\d+\]/, 'criteria[' + i + ']');
            if (el.type === 'checkbox') el.checked = false; else if (el.tagName === 'INPUT') el.value = '';
            if (el.tagName === 'SELECT') el.value = '1';
            if (el.hasAttribute('aria-label')) el.setAttribute('aria-label', el.getAttribute('aria-label').replace(/\d+$/, i + 1));
        });
        rows.appendChild(row);
        row.querySelector('input').focus();
    });
})();
</script>
