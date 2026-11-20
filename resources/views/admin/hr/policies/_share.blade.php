{{-- Audience picker for sharing a policy. $prefix keeps ids unique per form. --}}
@php $p = $prefix ?? 'share'; @endphp
<fieldset style="border:0;padding:0;margin:0" data-audience="{{ $p }}">
    <legend style="font-weight:700;margin-bottom:6px">Share with</legend>
    @foreach(($allowNone ?? true ? ['none' => 'Nobody yet'] : []) + ['all' => 'All active staff', 'new_staff' => 'New staff (joined recently)', 'department' => 'Specific departments', 'selected' => 'Selected staff'] as $value => $label)
        <label style="display:flex;gap:8px;align-items:center;font-weight:500;padding:2px 0"><input type="radio" name="audience" value="{{ $value }}" @checked($loop->first) style="width:auto"> {{ $label }}</label>
    @endforeach
    <div data-audience-panel="new_staff" hidden class="form-group" style="margin-top:8px"><label for="{{ $p }}-days">Joined in the last (days)</label><input id="{{ $p }}-days" type="number" name="new_staff_days" value="30" min="1" max="365" style="max-width:140px"></div>
    <div data-audience-panel="department" hidden class="form-group" style="margin-top:8px">
        @foreach($departments as $d)<label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="department_ids[]" value="{{ $d->id }}" style="width:auto"> {{ $d->name }}</label>@endforeach
    </div>
    <div data-audience-panel="selected" hidden style="margin-top:8px">@include('admin.hr.partials.staff-checklist', ['name' => 'user_ids', 'staff' => $staff])</div>
</fieldset>
<script>
(function () {
    var root = document.querySelector('[data-audience="{{ $p }}"]');
    if (!root) return;
    function sync() {
        var v = (root.querySelector('input[name=audience]:checked') || {}).value;
        root.querySelectorAll('[data-audience-panel]').forEach(function (el) { el.hidden = el.dataset.audiencePanel !== v; });
    }
    root.addEventListener('change', sync); sync();
})();
</script>
