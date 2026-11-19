@props([
    // Input name without brackets, e.g. "participant_ids". Multiple pickers post name[].
    'name',
    // JSON endpoint: ?q=term | ?group=key, returns {people:[{id,name,meta}]}.
    'source',
    'multiple' => true,
    // Quick-add buttons: key => label (e.g. "staff" => "All staff").
    'groups' => [],
    // Pre-selected people: [['id'=>1,'name'=>'…','meta'=>'…'], …].
    'selected' => [],
    // Ids never offered (e.g. people already in the group).
    'exclude' => [],
    'placeholder' => 'Search by name or email…',
    'required' => false,
    'id' => null,
])
@php
    $pickerId = $id ?: 'pp-'.\Illuminate\Support\Str::random(6);
    $selectedPeople = collect($selected)->map(fn ($p) => is_array($p) ? $p : ['id' => $p->id, 'name' => $p->name, 'meta' => ''])->values();
@endphp
<div class="ehp" data-people-picker id="{{ $pickerId }}"
     data-source="{{ $source }}" data-name="{{ $name }}{{ $multiple ? '[]' : '' }}"
     data-multiple="{{ $multiple ? '1' : '0' }}" data-exclude="{{ collect($exclude)->join(',') }}"
     @if($required) data-required="1" @endif>
    <div class="ehp-chips" data-ehp-chips aria-live="polite">
        @foreach($selectedPeople as $person)
            <span class="ehp-chip" data-id="{{ $person['id'] }}">
                <input type="hidden" name="{{ $name }}{{ $multiple ? '[]' : '' }}" value="{{ $person['id'] }}">
                <span>{{ $person['name'] }}</span>
                <button type="button" data-ehp-remove aria-label="Remove {{ $person['name'] }}"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            </span>
        @endforeach
    </div>
    <div class="ehp-search">
        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" data-ehp-input placeholder="{{ $placeholder }}" autocomplete="off" aria-label="{{ $placeholder }}" aria-controls="{{ $pickerId }}-results">
    </div>
    @if($multiple && count($groups))
        <div class="ehp-groups">
            @foreach($groups as $key => $label)
                <button type="button" class="ehp-group" data-ehp-group="{{ $key }}"><i class="fas fa-plus" aria-hidden="true"></i> {{ $label }}</button>
            @endforeach
        </div>
    @endif
    <ul class="ehp-results" id="{{ $pickerId }}-results" data-ehp-results role="listbox" @if($multiple) aria-multiselectable="true" @endif></ul>
    <small class="ehp-status" data-ehp-status></small>
</div>

@once
<style>
.ehp{display:flex;flex-direction:column;gap:8px}
.ehp-chips{display:flex;flex-wrap:wrap;gap:6px}
.ehp-chips:empty{display:none}
.ehp-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 6px 4px 11px;border-radius:999px;background:#f8eaea;color:#5c0000;font-size:.82rem;font-weight:600}
.ehp-chip button{display:inline-grid;place-items:center;width:20px;height:20px;border:0;border-radius:50%;background:transparent;color:inherit;cursor:pointer;font-size:.72rem}
.ehp-chip button:hover{background:#80000022}
.ehp-search{position:relative}
.ehp-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#98a2b3;font-size:.82rem}
.ehp .ehp-search input[data-ehp-input]{width:100%;padding:9px 12px 9px 34px !important;border:1px solid #d0d5dd;border-radius:10px;font:inherit;font-size:.9rem;background:#fff}
.ehp .ehp-search input[data-ehp-input]:focus{outline:none;border-color:#800000;box-shadow:0 0 0 3px #80000014}
.ehp-groups{display:flex;flex-wrap:wrap;gap:6px}
.ehp-group{border:1px dashed #b9a0a0;background:#fff;color:#800000;border-radius:999px;padding:4px 10px;font:inherit;font-size:.78rem;font-weight:600;cursor:pointer}
.ehp-group:hover{background:#f8eaea}
.ehp-results{list-style:none;margin:0;padding:0;max-height:220px;overflow-y:auto;border:1px solid #e6e8ec;border-radius:10px}
.ehp-results:empty{display:none}
.ehp-results li{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;cursor:pointer;font-size:.88rem;border-bottom:1px solid #f2f4f7}
.ehp-results li:last-child{border-bottom:0}
.ehp-results li:hover,.ehp-results li.is-focus{background:#f9fafb}
.ehp-results li[aria-selected=true]{background:#f8eaea}
.ehp-results li[aria-selected=true]::after{content:"\2713";color:#800000;font-weight:800}
.ehp-results small{color:#667085;font-size:.74rem;margin-left:auto}
.ehp-status{color:#667085;font-size:.78rem;min-height:1em}
.ehp-status.is-error{color:#b42318}
</style>
<script>
(() => {
    const init = (root) => {
        if (root.dataset.ehpReady) return;
        root.dataset.ehpReady = '1';
        const input = root.querySelector('[data-ehp-input]');
        const results = root.querySelector('[data-ehp-results]');
        const chips = root.querySelector('[data-ehp-chips]');
        const status = root.querySelector('[data-ehp-status]');
        const multiple = root.dataset.multiple === '1';
        const exclude = (root.dataset.exclude || '').split(',').filter(Boolean);
        let timer = null, seq = 0;

        const selectedIds = () => [...chips.querySelectorAll('.ehp-chip')].map(c => c.dataset.id);
        const say = (text, error) => { status.textContent = text || ''; status.classList.toggle('is-error', !!error); };
        const count = () => { const n = selectedIds().length; say(multiple && n ? n + ' selected' : ''); };

        const addChip = (p) => {
            if (selectedIds().includes(String(p.id))) return;
            if (!multiple) chips.innerHTML = '';
            const chip = document.createElement('span');
            chip.className = 'ehp-chip'; chip.dataset.id = p.id;
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = root.dataset.name; hidden.value = p.id;
            hidden.disabled = input.disabled;
            const label = document.createElement('span'); label.textContent = p.name;
            const remove = document.createElement('button');
            remove.type = 'button'; remove.dataset.ehpRemove = ''; remove.setAttribute('aria-label', 'Remove ' + p.name);
            remove.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
            chip.append(hidden, label, remove);
            chips.append(chip);
        };

        const render = (people) => {
            results.innerHTML = '';
            const chosen = selectedIds();
            people.filter(p => !exclude.includes(String(p.id))).forEach(p => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option'); li.dataset.id = p.id;
                li.setAttribute('aria-selected', chosen.includes(String(p.id)) ? 'true' : 'false');
                const name = document.createElement('span'); name.textContent = p.name;
                const meta = document.createElement('small'); meta.textContent = p.meta || '';
                li.append(name, meta);
                li._person = p;
                results.append(li);
            });
            if (!results.children.length) say(input.value.trim() ? 'No matching people.' : '');
        };

        const fetchPeople = async (params) => {
            const url = new URL(root.dataset.source, window.location.origin);
            Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
            const res = await fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            if (!res.ok) throw new Error(res.status);
            return (await res.json()).people || [];
        };

        const search = async () => {
            const mine = ++seq;
            try {
                const people = await fetchPeople({q: input.value.trim()});
                if (mine === seq) { render(people); if (people.length) count(); }
            } catch (e) { say('Could not load people. Try again.', true); }
        };

        input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(search, 250); });
        input.addEventListener('focus', () => { if (!results.children.length) search(); });
        input.addEventListener('keydown', (e) => {
            const items = [...results.children];
            if (!items.length) return;
            const i = items.findIndex(li => li.classList.contains('is-focus'));
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                items.forEach(li => li.classList.remove('is-focus'));
                const next = items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length];
                next.classList.add('is-focus'); next.scrollIntoView({block: 'nearest'});
            } else if (e.key === 'Enter') {
                e.preventDefault();
                (items[i] || items[0]).click();
            }
        });

        results.addEventListener('click', (e) => {
            const li = e.target.closest('li'); if (!li) return;
            if (li.getAttribute('aria-selected') === 'true') {
                chips.querySelector(`.ehp-chip[data-id="${li.dataset.id}"]`)?.remove();
                li.setAttribute('aria-selected', 'false');
            } else {
                addChip(li._person);
                if (!multiple) { results.innerHTML = ''; input.value = ''; }
                else li.setAttribute('aria-selected', 'true');
            }
            count();
        });

        chips.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-ehp-remove]'); if (!btn) return;
            const chip = btn.closest('.ehp-chip');
            results.querySelector(`li[data-id="${chip.dataset.id}"]`)?.setAttribute('aria-selected', 'false');
            chip.remove(); count();
        });

        root.querySelectorAll('[data-ehp-group]').forEach(btn => btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const people = (await fetchPeople({group: btn.dataset.ehpGroup})).filter(p => !exclude.includes(String(p.id)));
                people.forEach(addChip);
                render(people);
                say(people.length ? people.length + ' added · ' + selectedIds().length + ' selected' : 'Nobody to add from this group.');
            } catch (e) { say('Could not load that group. Try again.', true); }
            btn.disabled = false;
        }));

        // Block submit when a required picker is empty (hidden inputs cannot use the required attribute).
        root.closest('form')?.addEventListener('submit', (e) => {
            if (root.dataset.required && !input.disabled && !selectedIds().length) {
                e.preventDefault(); e.stopImmediatePropagation();
                say(multiple ? 'Choose at least one person.' : 'Choose a person.', true);
                input.focus();
            }
        }, true);

        // Keep chip inputs in step when a surrounding panel disables the picker.
        new MutationObserver(() => chips.querySelectorAll('input').forEach(h => { h.disabled = input.disabled; }))
            .observe(input, {attributes: true, attributeFilter: ['disabled']});
        count();
    };

    const boot = () => document.querySelectorAll('[data-people-picker]').forEach(init);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
@endonce
