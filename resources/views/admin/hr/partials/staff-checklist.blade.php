{{--
    Searchable staff checklist.
    @param string $name      input name without [] (e.g. panel_ids)
    @param Collection $staff  users with id, name, email
    @param array $checked    pre-selected ids
    @param array $exclude    ids not offered
--}}
@php
    $listId = 'sc-'.\Illuminate\Support\Str::random(6);
    $checked = array_map('intval', $checked ?? []);
    $exclude = array_map('intval', $exclude ?? []);
@endphp
<div class="hr-staff-checklist" id="{{ $listId }}">
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
        <input type="search" placeholder="Filter staff by name or email..." aria-label="Filter staff" data-sc-filter style="flex:1">
        <button type="button" class="btn btn-outline btn-sm" data-sc-all>Select shown</button>
        <button type="button" class="btn btn-outline btn-sm" data-sc-none>Clear</button>
    </div>
    <div class="hr-sc-list" style="max-height:220px;overflow:auto;border:1px solid var(--ad-border,#e3e5ea);border-radius:8px;padding:6px 10px">
        @foreach($staff as $member)
            @continue(in_array((int) $member->id, $exclude, true))
            <label data-sc-item data-text="{{ \Illuminate\Support\Str::lower($member->name.' '.$member->email) }}" style="display:flex;gap:8px;align-items:center;padding:4px 0;font-weight:500">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $member->id }}" @checked(in_array((int) $member->id, $checked, true)) style="width:auto">
                <span>{{ $member->name }} <small class="admin-cell-hint" style="display:inline">{{ $member->email }}</small></span>
            </label>
        @endforeach
    </div>
    <small class="form-hint" data-sc-count></small>
</div>
<script>
(function () {
    var root = document.getElementById(@json($listId));
    if (!root) return;
    var items = root.querySelectorAll('[data-sc-item]');
    var count = root.querySelector('[data-sc-count]');
    function update() { count.textContent = root.querySelectorAll('input[type=checkbox]:checked').length + ' selected'; }
    root.querySelector('[data-sc-filter]').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        items.forEach(function (el) { el.style.display = !q || el.dataset.text.indexOf(q) !== -1 ? '' : 'none'; });
    });
    root.querySelector('[data-sc-all]').addEventListener('click', function () {
        items.forEach(function (el) { if (el.style.display !== 'none') el.querySelector('input').checked = true; }); update();
    });
    root.querySelector('[data-sc-none]').addEventListener('click', function () {
        items.forEach(function (el) { el.querySelector('input').checked = false; }); update();
    });
    root.addEventListener('change', update); update();
})();
</script>
