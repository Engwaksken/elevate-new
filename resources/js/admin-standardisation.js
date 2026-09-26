document.addEventListener('DOMContentLoaded', () => {
    const adminContent = document.querySelector('.admin-content');
    if (!adminContent) return;

    const modal = document.createElement('div');
    modal.className = 'eh-global-modal';
    modal.id = 'ehAdminUniversalModal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="eh-global-modal-dialog">
            <div class="eh-global-modal-header">
                <div>
                    <h2 data-admin-modal-title>Manage Record</h2>
                    <p data-admin-modal-subtitle>Complete the form below.</p>
                </div>
                <button type="button" class="eh-global-modal-close" data-admin-modal-close aria-label="Close">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="eh-global-modal-body" data-admin-modal-body>
                <div class="admin-empty">Loading...</div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const body = modal.querySelector('[data-admin-modal-body]');
    const title = modal.querySelector('[data-admin-modal-title]');

    const openModal = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        body.innerHTML = '<div class="admin-empty">Loading...</div>';
    };

    modal.querySelector('[data-admin-modal-close]')?.addEventListener('click', closeModal);
    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });

    const isAdminCreateEditLink = link => {
        if (!link || link.classList.contains('no-modal')) return false;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return false;

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch {
            return false;
        }

        if (url.origin !== window.location.origin) return false;
        if (!url.pathname.startsWith('/admin/')) return false;

        return (
            /\/create\/?$/.test(url.pathname) ||
            /\/edit\/?$/.test(url.pathname) ||
            link.hasAttribute('data-admin-form-modal')
        );
    };

    const normaliseFormAction = (form, sourceUrl) => {
        const action = form.getAttribute('action');
        if (action) {
            form.setAttribute('action', new URL(action, sourceUrl).href);
        }
    };

    const decorateImportedForm = form => {
        form.classList.add('admin-modal-loaded-form');

        const directChildren = [...form.children];
        const hasGrid = directChildren.some(el =>
            el.classList?.contains('grid') ||
            el.classList?.contains('form-grid') ||
            el.classList?.contains('modal-grid')
        );

        if (!hasGrid) {
            const fields = directChildren.filter(el => {
                const tag = el.tagName?.toLowerCase();
                return !['input'].includes(tag) || el.type !== 'hidden';
            });

            if (fields.length >= 4) {
                // Preserve existing structure, but allow CSS to size fields consistently.
                form.classList.add('admin-standard-form');
            }
        }
    };

    adminContent.addEventListener('click', async event => {
        const link = event.target.closest('a');
        if (!isAdminCreateEditLink(link)) return;

        event.preventDefault();

        title.textContent = link.textContent.trim() || 'Manage Record';
        body.innerHTML = `
            <div class="admin-empty">
                <i class="fas fa-spinner fa-spin"></i>
                <strong>Loading form...</strong>
            </div>
        `;
        openModal();

        try {
            const response = await fetch(link.href, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            const heading =
                doc.querySelector('.admin-page-header h1') ||
                doc.querySelector('main h1') ||
                doc.querySelector('h1');

            if (heading?.textContent.trim()) {
                title.textContent = heading.textContent.trim();
            }

            const sourceForm =
                doc.querySelector('.admin-content form:not([method="GET"])') ||
                doc.querySelector('main form:not([method="GET"])') ||
                doc.querySelector('form:not([method="GET"])');

            if (!sourceForm) {
                window.location.href = link.href;
                return;
            }

            normaliseFormAction(sourceForm, link.href);

            const imported = document.importNode(sourceForm, true);
            decorateImportedForm(imported);

            body.replaceChildren(imported);

            body.querySelector(
                'input:not([type="hidden"]):not([type="checkbox"]), select, textarea'
            )?.focus();
        } catch (error) {
            console.error('Admin modal load failed', error);
            window.location.href = link.href;
        }
    });

    // Convert explicit inline create/edit links loaded later into modal triggers too.
    const observer = new MutationObserver(() => {
        adminContent.querySelectorAll('a[href]').forEach(link => {
            if (isAdminCreateEditLink(link)) {
                link.dataset.adminModalReady = '1';
            }
        });
    });
    observer.observe(adminContent, { childList: true, subtree: true });


    /* DELETE CONFIRMATION MODAL */
    const deleteModal = document.createElement('div');
    deleteModal.className = 'eh-global-modal';
    deleteModal.setAttribute('aria-hidden', 'true');
    deleteModal.innerHTML = `
        <div class="eh-global-modal-dialog eh-modal-sm">
            <div class="eh-global-modal-header">
                <div>
                    <h2>Delete record?</h2>
                    <p>This action cannot be undone.</p>
                </div>
                <button type="button" class="eh-global-modal-close" data-delete-cancel>
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="eh-global-modal-body">
                <p style="margin:0;color:#475467;font-size:.8rem">
                    Are you sure you want to permanently delete this record?
                </p>
            </div>

            <div class="eh-modal-footer">
                <button type="button" class="btn btn-outline" data-delete-cancel>
                    Cancel
                </button>

                <button type="button" class="btn btn-danger" data-delete-confirm>
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(deleteModal);

    let pendingDeleteForm = null;

    const closeDeleteModal = () => {
        deleteModal.classList.remove('is-open');
        deleteModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        pendingDeleteForm = null;
    };

    deleteModal.querySelectorAll('[data-delete-cancel]').forEach(btn =>
        btn.addEventListener('click', closeDeleteModal)
    );

    deleteModal.querySelector('[data-delete-confirm]')?.addEventListener('click', () => {
        if (!pendingDeleteForm) return;
        const form = pendingDeleteForm;
        pendingDeleteForm = null;
        deleteModal.classList.remove('is-open');
        deleteModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        form.dataset.modalDeleteConfirmed = '1';
        form.requestSubmit();
    });

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const spoof = form.querySelector('input[name="_method"]');
        if (spoof?.value?.toUpperCase() !== 'DELETE') return;

        if (form.dataset.modalDeleteConfirmed === '1') {
            delete form.dataset.modalDeleteConfirmed;
            return;
        }

        if (form.hasAttribute('data-bulk-form') || form.classList.contains('no-delete-modal')) {
            return;
        }

        event.preventDefault();
        pendingDeleteForm = form;
        deleteModal.classList.add('is-open');
        deleteModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    });


    /* RESIZE EXISTING MODALS AND ENSURE FOOTERS STAY VISIBLE */
    document.querySelectorAll('.eh-modal-dialog').forEach(dialog => {
        if (!dialog.classList.contains('eh-modal-sm')) {
            dialog.classList.add('eh-modal-standard');
        }
    });


    /* FORCE STATISTICS GRIDS TO STANDARD COMPACT FORMAT */
    document.querySelectorAll(
        '.admin-stats-grid, .stats-grid, .dashboard-stats, .admin-auto-stats'
    ).forEach(grid => {
        grid.classList.add('admin-stats-standard');
    });
});
