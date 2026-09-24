document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('modal-open');
        modal.querySelector('input,select,textarea,button')?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        body.classList.remove('modal-open');
    };

    document.querySelectorAll('[data-modal-open]').forEach(button => {
        button.addEventListener('click', () => openModal(button.dataset.modalOpen));
    });

    document.querySelectorAll('[data-modal-close]').forEach(button => {
        button.addEventListener('click', () => closeModal(button.closest('.eh-modal')));
    });

    document.querySelectorAll('.eh-modal').forEach(modal => {
        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal(document.querySelector('.eh-modal.is-open'));
    });

    document.querySelectorAll('[data-select-all]').forEach(master => {
        const table = master.closest('table');
        const boxes = table?.querySelectorAll('[data-row-select]') ?? [];
        const bulkBar = document.querySelector(master.dataset.bulkTarget || '');

        const sync = () => {
            const selected = [...boxes].filter(b => b.checked);
            if (bulkBar) {
                bulkBar.classList.toggle('is-visible', selected.length > 0);
                bulkBar.querySelector('[data-selected-count]')?.replaceChildren(String(selected.length));
            }
        };

        master.addEventListener('change', () => {
            boxes.forEach(box => box.checked = master.checked);
            sync();
        });

        boxes.forEach(box => box.addEventListener('change', sync));
        sync();
    });

    document.querySelectorAll('[data-bulk-form]').forEach(form => {
        form.addEventListener('submit', e => {
            const tableId = form.dataset.table;
            const table = document.getElementById(tableId);
            const ids = [...(table?.querySelectorAll('[data-row-select]:checked') ?? [])].map(i => i.value);

            form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            if (!ids.length || !confirm(`Delete ${ids.length} selected record(s)? This cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-delete-form]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm || 'Delete this record? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
