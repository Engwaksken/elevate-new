// Collapsible 4-per-row pickers (resources/css/assignment-grids.css):
// live "N selected" counts, a text filter and a "show selected only" toggle.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ag-scope]').forEach((scope) => {
        const items = [...scope.querySelectorAll('[data-ag-item]')];
        if (!items.length) return;

        const section = scope.closest('details') || scope;
        const counter = section.querySelector('[data-ag-selected]');
        const filter = scope.querySelector('[data-ag-filter]');
        const onlyBtn = scope.querySelector('[data-ag-only-selected]');
        const noMatch = scope.querySelector('[data-ag-no-match]');
        let onlySelected = false;

        const isChecked = (item) => item.querySelector('input[type=checkbox]')?.checked;

        const apply = () => {
            const q = (filter?.value || '').trim().toLowerCase();
            let shown = 0;
            items.forEach((item) => {
                const visible = (!q || (item.dataset.search || '').includes(q)) && (!onlySelected || isChecked(item));
                // Toggle the hidden property: Tailwind's layered [hidden] rule wins over CSS display.
                item.hidden = !visible;
                if (visible) shown++;
            });
            if (noMatch) noMatch.hidden = shown > 0;
            if (counter) counter.textContent = String(items.filter(isChecked).length);
        };

        filter?.addEventListener('input', apply);
        filter?.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        onlyBtn?.addEventListener('click', () => {
            onlySelected = !onlySelected;
            onlyBtn.textContent = onlySelected ? 'Show all' : 'Show selected only';
            apply();
        });
        items.forEach((item) => item.querySelector('input[type=checkbox]')?.addEventListener('change', apply));
        apply();
    });
});
