// Phone layout helpers (styled by resources/css/mobile-compact.css, active at <= 640px):
// - filter bars: search stays visible, other fields go behind a "Filters" toggle
// - tables: each cell gets its column label so rows can show as cards; long rows get "More details"
// - page header actions: the main action stays visible, the rest go into a "More" menu
// - public header: menu button for the site navigation
// Opt out per element with data-no-mobile-compact.

const SEARCH_FIELD = 'input[type="search"], input[name="search"], input[name="q"], input[name$="_q"]';
const ROW_FIELDS_SHOWN = 4;

function text(element) {
    return (element?.textContent || '').replace(/\s+/g, ' ').trim();
}

function compactFilters(form) {
    const children = [...form.children].filter((child) => child.type !== 'hidden' && !child.matches('input[type="hidden"], script, style'));
    if (children.length < 3) return;

    const primary = children.find((child) => child.matches(SEARCH_FIELD) || child.querySelector(SEARCH_FIELD)) || null;
    const collapsible = children.filter((child) => child !== primary);
    // A filter counts as active only when the user applied it (it is in the page address),
    // so defaults such as "15 per page" do not open the panel.
    const query = new URLSearchParams(window.location.search);
    const active = collapsible.filter((child) => {
        const field = child.matches('select, input') ? child : child.querySelector('select, input:not([type="hidden"]):not([type="search"])');
        return field?.name && !/(^|_)(per_page|page)$/.test(field.name) && (query.get(field.name) || '').trim() !== '';
    }).length;

    form.classList.add('mc-filters');
    if (active > 0) form.classList.add('is-open');
    primary?.classList.add('mc-primary');
    collapsible.forEach((child) => child.classList.add('mc-collapsible'));

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'btn btn-outline btn-sm mc-filter-toggle';
    toggle.setAttribute('aria-expanded', form.classList.contains('is-open') ? 'true' : 'false');
    toggle.innerHTML = '<i class="fas fa-sliders" aria-hidden="true"></i> Filters' + (active ? ` <span class="mc-count">${active}</span>` : '');
    toggle.addEventListener('click', () => {
        const open = form.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    if (primary) primary.after(toggle);
    else form.prepend(toggle);
}

function compactTable(table) {
    const headers = [...table.querySelectorAll('thead tr:last-child th')];
    if (headers.length < 2) return;
    const labels = headers.map(text);
    const actionIndex = headers.findIndex((th) => th.classList.contains('table-actions') || /^(actions?|details|start)$/i.test(text(th)));

    table.classList.add('mc-cards');

    const labelRow = (row) => {
        if (row.dataset.mcDone) return;
        const cells = [...row.cells];
        if (cells.length !== headers.length) return; // empty-state rows use colspan
        row.dataset.mcDone = '1';

        const isSelect = (cell) => cell.querySelector('input[type="checkbox"][data-row-select], input[type="checkbox"].row-select') && text(cell) === '';
        const isAction = (cell, index) => index === actionIndex || cell.classList.contains('table-actions');
        const content = cells.filter((cell, index) => !isSelect(cell) && !isAction(cell, index));
        // The card title is the first cell with a bold name or link (e.g. the course, not its "Type" chip).
        const title = content.find((cell) => cell.querySelector(':scope > strong, :scope > a > strong, :scope > a')) || content[0];
        const details = content.filter((cell) => cell !== title);
        // Only collapse when it hides at least two fields; hiding one saves nothing.
        const extras = details.length - (ROW_FIELDS_SHOWN - 1) >= 2 ? details.slice(ROW_FIELDS_SHOWN - 1) : [];
        const hasExtra = extras.length > 0;

        cells.forEach((cell, index) => {
            cell.dataset.label = labels[index] || '';
            if (isSelect(cell)) cell.classList.add('mc-select');
            else if (isAction(cell, index)) cell.classList.add('mc-actions');
        });
        title?.classList.add('mc-title');
        extras.forEach((cell) => cell.classList.add('mc-extra'));
        // The title is shown first on phones with CSS order; the DOM is not reordered so desktop columns stay aligned.

        if (hasExtra) {
            const toggleCell = document.createElement('td');
            toggleCell.className = 'mc-row-toggle-cell';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mc-row-toggle';
            button.setAttribute('aria-expanded', 'false');
            button.textContent = 'More details';
            button.addEventListener('click', () => {
                const open = row.classList.toggle('is-expanded');
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
                button.textContent = open ? 'Fewer details' : 'More details';
            });
            toggleCell.append(button);
            const actions = row.querySelector('td.mc-actions');
            if (actions) actions.before(toggleCell);
            else row.append(toggleCell);
        }
    };

    [...table.tBodies].forEach((body) => {
        [...body.rows].forEach(labelRow);
        // Rows added later by page scripts (e.g. a newly approved step) get the same treatment.
        new MutationObserver(() => [...body.rows].forEach(labelRow)).observe(body, { childList: true });
    });
}

function compactHeaderActions(container) {
    const children = [...container.children].filter((child) => !child.matches('script, style, [hidden]'));
    if (children.length < 2) return;

    const keep = children.filter((child) => child.matches('.btn-primary') || child.querySelector(':scope > .btn-primary'));
    if (!keep.length) keep.push(children[children.length - 1]);
    const secondary = children.filter((child) => !keep.includes(child));
    if (!secondary.length) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'mc-secondary';
    secondary[0].before(wrapper);
    secondary.forEach((child) => wrapper.append(child));

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'btn btn-outline mc-more-toggle';
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'More actions');
    toggle.innerHTML = '<i class="fas fa-ellipsis-vertical" aria-hidden="true"></i> More';
    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = container.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    keep.forEach((child) => child.classList.add('mc-keep'));
    container.classList.add('mc-has-more');
    container.append(toggle);
    wrapper.addEventListener('click', () => container.classList.remove('is-open'));
    document.addEventListener('click', (event) => {
        if (!container.contains(event.target)) {
            container.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function publicNavigation() {
    const header = document.querySelector('.site-header');
    const toggle = header?.querySelector('[data-nav-toggle]');
    if (!toggle) return;

    toggle.addEventListener('click', () => {
        const open = header.classList.toggle('is-nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        toggle.querySelector('i').className = open ? 'fas fa-xmark' : 'fas fa-bars';
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && header.classList.contains('is-nav-open')) toggle.click();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const skip = (element) => element.closest('[data-no-mobile-compact], .eh-modal, .eh-global-modal');

    document.querySelectorAll('form.admin-toolbar, form.eh-filter-bar, .admin-toolbar.admin-client-toolbar').forEach((form) => {
        if (!skip(form)) compactFilters(form);
    });
    document.querySelectorAll('table.admin-table').forEach((table) => {
        if (!skip(table)) compactTable(table);
    });
    document.querySelectorAll('.page-actions, .admin-page-actions, .page-header-actions').forEach((container) => {
        if (!skip(container)) compactHeaderActions(container);
    });
    publicNavigation();
});
