document.addEventListener('DOMContentLoaded', () => {
    const adminContent = document.querySelector('.admin-content');
    if (!adminContent) return;

    /* =========================================================
       Global create/edit modal loader
       Existing admin form pages can now open inside a modal
       without rewriting every workflow-specific controller.
       ========================================================= */

    let globalModal = document.getElementById('ehGlobalAdminModal');

    if (!globalModal) {
        globalModal = document.createElement('div');
        globalModal.id = 'ehGlobalAdminModal';
        globalModal.className = 'eh-global-modal';
        globalModal.setAttribute('aria-hidden', 'true');

        globalModal.innerHTML = `
            <div class="eh-global-modal-dialog">
                <div class="eh-global-modal-header">
                    <div>
                        <h2 data-global-modal-title>Manage Record</h2>
                        <p data-global-modal-subtitle>Complete the form below.</p>
                    </div>

                    <button
                        type="button"
                        class="eh-global-modal-close"
                        data-global-modal-close
                        aria-label="Close"
                    >
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <div class="eh-global-modal-body" data-global-modal-body>
                    <div class="admin-empty">
                        Loading...
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(globalModal);
    }

    const closeGlobalModal = () => {
        globalModal.classList.remove('is-open');
        globalModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openGlobalModal = () => {
        globalModal.classList.add('is-open');
        globalModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    globalModal
        .querySelector('[data-global-modal-close]')
        ?.addEventListener('click', closeGlobalModal);

    globalModal.addEventListener('click', event => {
        if (event.target === globalModal) {
            closeGlobalModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeGlobalModal();
        }
    });

    const isCreateEditLink = link => {
        const href = link.getAttribute('href');

        if (!href || href === '#' || link.classList.contains('no-modal')) {
            return false;
        }

        let url;

        try {
            url = new URL(href, window.location.origin);
        } catch {
            return false;
        }

        if (url.origin !== window.location.origin) {
            return false;
        }

        if (!url.pathname.startsWith('/admin/')) {
            return false;
        }

        return (
            /\/create\/?$/.test(url.pathname) ||
            /\/edit\/?$/.test(url.pathname)
        );
    };

    adminContent.addEventListener('click', async event => {
        const link = event.target.closest('a');

        if (!link || !isCreateEditLink(link)) {
            return;
        }

        event.preventDefault();

        const body = globalModal.querySelector('[data-global-modal-body]');
        const title = globalModal.querySelector('[data-global-modal-title]');

        body.innerHTML = `
            <div class="admin-empty">
                <i class="fas fa-spinner fa-spin"></i>
                <strong>Loading form</strong>
            </div>
        `;

        title.textContent =
            link.textContent.trim() ||
            'Manage Record';

        openGlobalModal();

        try {
            const response = await fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();
            const documentCopy =
                new DOMParser().parseFromString(
                    html,
                    'text/html'
                );

            const sourceForm =
                documentCopy.querySelector(
                    '.admin-content form'
                ) ||
                documentCopy.querySelector(
                    'main form'
                ) ||
                documentCopy.querySelector(
                    'form'
                );

            if (!sourceForm) {
                window.location.href = link.href;
                return;
            }

            const heading =
                documentCopy.querySelector(
                    '.admin-page-header h1'
                ) ||
                documentCopy.querySelector('h1');

            if (heading?.textContent.trim()) {
                title.textContent =
                    heading.textContent.trim();
            }

            const imported =
                document.importNode(
                    sourceForm,
                    true
                );

            body.replaceChildren(imported);

            body
                .querySelector(
                    'input:not([type="hidden"]), select, textarea'
                )
                ?.focus();
        } catch (error) {
            console.error(
                'Could not load admin form modal.',
                error
            );

            window.location.href = link.href;
        }
    });


    /* =========================================================
       Delete confirmation modal for existing DELETE forms
       ========================================================= */

    let pendingDeleteForm = null;

    const deleteModal =
        document.createElement('div');

    deleteModal.className = 'eh-global-modal';
    deleteModal.setAttribute('aria-hidden', 'true');

    deleteModal.innerHTML = `
        <div
            class="eh-global-modal-dialog"
            style="max-width:520px"
        >
            <div class="eh-global-modal-header">
                <div>
                    <h2>Delete record?</h2>
                    <p>
                        This action cannot be undone.
                    </p>
                </div>

                <button
                    type="button"
                    class="eh-global-modal-close"
                    data-delete-cancel
                >
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="eh-global-modal-body">
                <p>
                    Are you sure you want to permanently
                    delete this record?
                </p>

                <div
                    style="
                        display:flex;
                        justify-content:flex-end;
                        gap:8px;
                        margin-top:18px
                    "
                >
                    <button
                        type="button"
                        class="btn btn-outline"
                        data-delete-cancel
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger"
                        data-delete-confirm
                    >
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(deleteModal);

    const hideDeleteModal = () => {
        deleteModal.classList.remove('is-open');
        deleteModal.setAttribute('aria-hidden', 'true');
        pendingDeleteForm = null;
        document.body.classList.remove('modal-open');
    };

    deleteModal
        .querySelectorAll('[data-delete-cancel]')
        .forEach(button => {
            button.addEventListener(
                'click',
                hideDeleteModal
            );
        });

    deleteModal
        .querySelector('[data-delete-confirm]')
        ?.addEventListener('click', () => {
            if (!pendingDeleteForm) return;

            const form = pendingDeleteForm;

            pendingDeleteForm = null;
            hideDeleteModal();

            form.dataset.deleteConfirmed = '1';
            form.requestSubmit();
        });

    document.addEventListener('submit', event => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const methodInput =
            form.querySelector(
                'input[name="_method"]'
            );

        if (
            methodInput?.value?.toUpperCase() !==
            'DELETE'
        ) {
            return;
        }

        if (form.dataset.deleteConfirmed === '1') {
            delete form.dataset.deleteConfirmed;
            return;
        }

        if (
            form.hasAttribute('data-bulk-form') ||
            form.classList.contains('no-delete-modal')
        ) {
            return;
        }

        event.preventDefault();

        pendingDeleteForm = form;

        deleteModal.classList.add('is-open');
        deleteModal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add('modal-open');
    });


    /* =========================================================
       Auto-wrap all admin tables for responsive scrolling
       ========================================================= */

    adminContent
        .querySelectorAll('table')
        .forEach(table => {
            if (
                table.closest('.admin-table-wrap') ||
                table.closest('.table-responsive')
            ) {
                return;
            }

            const wrapper =
                document.createElement('div');

            wrapper.className =
                'admin-table-wrap';

            table.parentNode.insertBefore(
                wrapper,
                table
            );

            wrapper.appendChild(table);
        });


    /* =========================================================
       Generic statistics for pages that do not yet have
       module-specific server-side statistic cards.
       These values describe the CURRENT rendered page only.
       ========================================================= */

    const existingStats =
        adminContent.querySelector(
            '.admin-stats-grid, .stats-grid, .admin-auto-stats'
        );

    const firstTable =
        adminContent.querySelector(
            'table tbody'
        );

    const pageHeader =
        adminContent.querySelector(
            '.admin-page-header, h1'
        );

    if (!existingStats && firstTable && pageHeader) {
        const dataRows =
            [...firstTable.querySelectorAll('tr')]
                .filter(row =>
                    row.querySelectorAll('td').length > 1
                );

        const params =
            new URLSearchParams(
                window.location.search
            );

        const activeFilters =
            [...params.entries()]
                .filter(([key, value]) =>
                    value &&
                    key !== 'page' &&
                    key !== 'per_page'
                )
                .length;

        const currentPage =
            Number(params.get('page') || 1);

        const stats =
            document.createElement('div');

        stats.className = 'admin-auto-stats';

        stats.innerHTML = `
            <div class="admin-auto-stat">
                <i class="fas fa-table-list"></i>
                <div>
                    <small>Records on page</small>
                    <strong>${dataRows.length}</strong>
                </div>
            </div>

            <div class="admin-auto-stat">
                <i class="fas fa-filter"></i>
                <div>
                    <small>Active filters</small>
                    <strong>${activeFilters}</strong>
                </div>
            </div>

            <div class="admin-auto-stat">
                <i class="fas fa-file"></i>
                <div>
                    <small>Current page</small>
                    <strong>${currentPage}</strong>
                </div>
            </div>

            <div class="admin-auto-stat">
                <i class="fas fa-square-check"></i>
                <div>
                    <small>Selected</small>
                    <strong data-auto-selected>0</strong>
                </div>
            </div>
        `;

        const header =
            adminContent.querySelector(
                '.admin-page-header'
            );

        if (header) {
            header.insertAdjacentElement(
                'afterend',
                stats
            );
        } else {
            pageHeader.insertAdjacentElement(
                'afterend',
                stats
            );
        }
    }


    /* =========================================================
       Auto multi-select + bulk delete for ordinary CRUD tables
       which already contain per-row DELETE forms.
       ========================================================= */

    adminContent
        .querySelectorAll('table')
        .forEach((table, tableIndex) => {
            if (
                table.querySelector(
                    '[data-row-select]'
                )
            ) {
                return;
            }

            const bodyRows =
                [...table.querySelectorAll('tbody tr')]
                    .filter(row =>
                        row.querySelectorAll('td').length > 1
                    );

            const deletableRows =
                bodyRows.filter(row => {
                    return [...row.querySelectorAll('form')]
                        .some(form =>
                            form.querySelector(
                                'input[name="_method"][value="DELETE"]'
                            )
                        );
                });

            if (!deletableRows.length) {
                return;
            }

            const headRow =
                table.querySelector('thead tr');

            if (!headRow) return;

            const masterHead =
                document.createElement('th');

            masterHead.className = 'select-col';
            masterHead.innerHTML =
                '<input type="checkbox" data-auto-select-all>';

            headRow.insertBefore(
                masterHead,
                headRow.firstChild
            );

            deletableRows.forEach(
                (row, index) => {
                    const cell =
                        document.createElement('td');

                    cell.className = 'select-col';

                    cell.innerHTML =
                        `<input
                            type="checkbox"
                            data-auto-row-select
                            value="${index}"
                        >`;

                    row.insertBefore(
                        cell,
                        row.firstChild
                    );
                }
            );

            const wrapper =
                table.closest('.admin-table-wrap') ||
                table;

            const bulkBar =
                document.createElement('div');

            bulkBar.className =
                'admin-auto-bulk';

            bulkBar.innerHTML = `
                <strong>
                    <span data-auto-bulk-count>0</span>
                    selected
                </strong>

                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    data-auto-bulk-delete
                >
                    <i class="fas fa-trash"></i>
                    Delete Selected
                </button>
            `;

            wrapper.parentNode.insertBefore(
                bulkBar,
                wrapper
            );

            const master =
                table.querySelector(
                    '[data-auto-select-all]'
                );

            const boxes =
                [...table.querySelectorAll(
                    '[data-auto-row-select]'
                )];

            const syncSelection = () => {
                const selected =
                    boxes.filter(box => box.checked);

                bulkBar.classList.toggle(
                    'is-visible',
                    selected.length > 0
                );

                bulkBar
                    .querySelector(
                        '[data-auto-bulk-count]'
                    )
                    .textContent =
                    selected.length;

                const selectedStat =
                    adminContent.querySelector(
                        '[data-auto-selected]'
                    );

                if (selectedStat) {
                    selectedStat.textContent =
                        selected.length;
                }

                master.indeterminate =
                    selected.length > 0 &&
                    selected.length < boxes.length;

                master.checked =
                    selected.length > 0 &&
                    selected.length === boxes.length;
            };

            master.addEventListener(
                'change',
                () => {
                    boxes.forEach(
                        box => box.checked =
                            master.checked
                    );

                    syncSelection();
                }
            );

            boxes.forEach(box =>
                box.addEventListener(
                    'change',
                    syncSelection
                )
            );

            bulkBar
                .querySelector(
                    '[data-auto-bulk-delete]'
                )
                .addEventListener(
                    'click',
                    async () => {
                        const selected =
                            boxes.filter(
                                box => box.checked
                            );

                        if (!selected.length) {
                            return;
                        }

                        if (!window.confirm(
                            `Delete ${selected.length} selected record(s)?`
                        )) {
                            return;
                        }

                        const forms =
                            selected
                                .map(box => {
                                    const row =
                                        box.closest('tr');

                                    return [...row.querySelectorAll('form')]
                                        .find(form =>
                                            form.querySelector(
                                                'input[name="_method"][value="DELETE"]'
                                            )
                                        );
                                })
                                .filter(Boolean);

                        for (const form of forms) {
                            const response =
                                await fetch(
                                    form.action,
                                    {
                                        method:'POST',
                                        body:new FormData(form),
                                        credentials:'same-origin',
                                        headers:{
                                            'X-Requested-With':
                                                'XMLHttpRequest',
                                        },
                                    }
                                );

                            if (!response.ok) {
                                alert(
                                    'One or more records could not be deleted.'
                                );
                                break;
                            }
                        }

                        window.location.reload();
                    }
                );

            syncSelection();
        });


    /* =========================================================
       Client search/pagination fallback for simple admin tables
       that do not yet provide server-side search/pagination.
       ========================================================= */

    adminContent
        .querySelectorAll('.admin-table-wrap')
        .forEach((wrapper, wrapperIndex) => {
            const table =
                wrapper.querySelector('table');

            if (!table) return;

            const panel =
                wrapper.closest(
                    '.admin-panel, .card'
                ) ||
                wrapper.parentElement;

            const existingSearch =
                panel?.querySelector(
                    'form[method="GET"] input[name="search"], input[data-table-search]'
                );

            const serverPagination =
                panel?.querySelector(
                    'nav[role="navigation"], .pagination, .admin-pagination'
                );

            const rows =
                [...table.querySelectorAll(
                    'tbody tr'
                )]
                    .filter(row =>
                        row.querySelectorAll('td').length > 1
                    );

            if (!rows.length) return;

            if (!existingSearch) {
                const toolbar =
                    document.createElement('div');

                toolbar.className =
                    'admin-toolbar admin-client-toolbar';

                toolbar.innerHTML = `
                    <div class="search-box">
                        <i class="fas fa-magnifying-glass"></i>

                        <input
                            type="search"
                            data-table-search
                            placeholder="Search records on this page..."
                        >
                    </div>
                `;

                panel.insertBefore(
                    toolbar,
                    wrapper
                );

                const searchInput =
                    toolbar.querySelector(
                        '[data-table-search]'
                    );

                searchInput.addEventListener(
                    'input',
                    () => {
                        const term =
                            searchInput.value
                                .trim()
                                .toLowerCase();

                        rows.forEach(row => {
                            row.hidden =
                                term &&
                                !row.textContent
                                    .toLowerCase()
                                    .includes(term);
                        });
                    }
                );
            }

            if (
                !serverPagination &&
                rows.length > 15
            ) {
                let page = 1;
                let pageSize = 15;

                const nav =
                    document.createElement('div');

                nav.className =
                    'admin-client-pagination';

                const render = () => {
                    const visibleRows =
                        rows.filter(row => !row.hidden);

                    const totalPages =
                        Math.max(
                            1,
                            Math.ceil(
                                visibleRows.length /
                                pageSize
                            )
                        );

                    page =
                        Math.min(page,totalPages);

                    visibleRows.forEach(
                        (row,index) => {
                            row.style.display =
                                index >=
                                    (page-1)*pageSize &&
                                index <
                                    page*pageSize
                                    ? ''
                                    : 'none';
                        }
                    );

                    nav.innerHTML = `
                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            ${page <= 1 ? 'disabled' : ''}
                            data-prev-page
                        >
                            Previous
                        </button>

                        <span>
                            Page ${page} of ${totalPages}
                        </span>

                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            ${page >= totalPages ? 'disabled' : ''}
                            data-next-page
                        >
                            Next
                        </button>
                    `;

                    nav
                        .querySelector(
                            '[data-prev-page]'
                        )
                        ?.addEventListener(
                            'click',
                            () => {
                                page--;
                                render();
                            }
                        );

                    nav
                        .querySelector(
                            '[data-next-page]'
                        )
                        ?.addEventListener(
                            'click',
                            () => {
                                page++;
                                render();
                            }
                        );
                };

                wrapper.insertAdjacentElement(
                    'afterend',
                    nav
                );

                render();
            }
        });
});
