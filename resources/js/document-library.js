// Department document libraries (staff/documents): SharePoint-style selection,
// command bar, row menus, drag-and-drop upload and the share people picker.
document.addEventListener('DOMContentLoaded', () => {
    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        modal.querySelector('input:not([type=hidden]),select,textarea')?.focus();
    };

    /* ---------- Dropdowns: one open at a time, close on outside click ---------- */
    const dropdowns = () => document.querySelectorAll('.dl-dropdown[open]');
    document.addEventListener('toggle', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLDetailsElement) || !el.classList.contains('dl-dropdown') || !el.open) return;
        dropdowns().forEach((d) => { if (d !== el) d.open = false; });
    }, true);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dl-dropdown')) dropdowns().forEach((d) => { d.open = false; });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dropdowns().forEach((d) => { d.open = false; });
    });

    /* ---------- Rename / new version / delete (from row menus, command bar, panel) ---------- */
    const renameForm = document.querySelector('[data-dl-rename-form]');
    const versionForm = document.querySelector('[data-dl-version-form]');
    const deleteForm = document.querySelector('[data-dl-delete-form]');

    const runAction = (kind, url, name, rename) => {
        dropdowns().forEach((d) => { d.open = false; });
        if (kind === 'rename' && renameForm) {
            renameForm.action = url;
            renameForm.querySelector('[data-dl-rename-label]').textContent = name;
            const input = renameForm.querySelector('[data-dl-rename-input]');
            input.value = rename || name;
            openModal('dl-rename');
            input.select();
        } else if (kind === 'version' && versionForm) {
            versionForm.action = url;
            versionForm.querySelector('[data-dl-version-label]').textContent = name;
            openModal('dl-new-version');
        } else if (kind === 'delete' && deleteForm) {
            if (confirm(`Delete "${name}"? Folders are deleted with everything inside them. This cannot be undone.`)) {
                deleteForm.action = url;
                deleteForm.submit();
            }
        }
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-dl-action]');
        if (!btn) return;
        e.preventDefault();
        runAction(btn.dataset.dlAction, btn.dataset.url, btn.dataset.name, btn.dataset.rename);
    });

    /* ---------- Selection drives the command bar ---------- */
    const items = document.querySelector('[data-dl-items]');
    const defaultCmds = document.querySelector('[data-dl-default-cmds]');
    const selectionCmds = document.querySelector('[data-dl-selection-cmds]');

    if (items && selectionCmds) {
        const rows = [...items.querySelectorAll('[data-dl-row]')];
        const boxOf = (row) => row.querySelector('[data-dl-select]');
        const selectAll = items.querySelector('[data-dl-select-all]');

        const cmdButton = (key, [label, icon, url, kind], name, rename) => {
            const el = (kind === 'link' || kind === 'blank') ? document.createElement('a') : document.createElement('button');
            el.className = 'dl-cmd' + (key === 'delete' ? ' danger' : '');
            if (el.tagName === 'A') {
                el.href = url;
                if (kind === 'blank') { el.target = '_blank'; el.rel = 'noopener'; }
            } else {
                el.type = 'button';
                Object.assign(el.dataset, { dlAction: kind, url, name, rename: rename || '' });
            }
            const i = document.createElement('i');
            i.className = `fas ${icon}`;
            const span = document.createElement('span');
            span.className = 'dl-cmd-label';
            span.textContent = label;
            el.append(i, span);
            return el;
        };

        const render = () => {
            const selected = rows.filter((r) => boxOf(r)?.checked);
            rows.forEach((r) => r.classList.toggle('is-selected', boxOf(r)?.checked));
            if (selectAll) {
                selectAll.checked = selected.length > 0 && selected.length === rows.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < rows.length;
            }

            selectionCmds.replaceChildren();
            if (!selected.length) {
                selectionCmds.hidden = true;
                if (defaultCmds) defaultCmds.hidden = false;
                return;
            }
            if (defaultCmds) defaultCmds.hidden = true;
            selectionCmds.hidden = false;

            if (selected.length === 1) {
                let data = {};
                try { data = JSON.parse(selected[0].dataset.dlItem || '{}'); } catch { /* ignore */ }
                // Command bar order, as SharePoint shows it for one selected item.
                ['open', 'share', 'download', 'access', 'rename', 'newversion', 'versions', 'details', 'delete'].forEach((key) => {
                    if (data.actions?.[key]) selectionCmds.append(cmdButton(key, data.actions[key], data.name, data.rename));
                });
            }

            const info = document.createElement('span');
            info.className = 'dl-selection-info';
            info.textContent = `${selected.length} selected`;
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'dl-cmd';
            clear.innerHTML = '<i class="fas fa-xmark"></i><span class="dl-cmd-label">Clear</span>';
            clear.addEventListener('click', () => { rows.forEach((r) => { const b = boxOf(r); if (b) b.checked = false; }); render(); });
            if (selectionCmds.children.length) {
                const sep = document.createElement('span');
                sep.className = 'dl-cmd-sep';
                selectionCmds.append(sep);
            }
            selectionCmds.append(info, clear);
        };

        rows.forEach((row) => {
            boxOf(row)?.addEventListener('change', render);
            // Clicking empty space in a row selects just that row, like SharePoint.
            row.addEventListener('click', (e) => {
                if (e.target.closest('a,button,input,summary,details,label')) return;
                const box = boxOf(row);
                if (!box) return;
                const only = !box.checked || rows.some((r) => r !== row && boxOf(r)?.checked);
                rows.forEach((r) => { const b = boxOf(r); if (b) b.checked = false; });
                box.checked = only;
                render();
            });
        });
        selectAll?.addEventListener('change', () => {
            rows.forEach((r) => { const b = boxOf(r); if (b) b.checked = selectAll.checked; });
            render();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.querySelector('.eh-modal.is-open')) {
                rows.forEach((r) => { const b = boxOf(r); if (b) b.checked = false; });
                render();
            }
        });
        render();
    }

    /* ---------- Upload: buttons, file picker and drag-and-drop ---------- */
    const uploadForm = document.querySelector('[data-dl-upload-form]');
    const uploadInput = uploadForm?.querySelector('[data-dl-upload-input]');
    const dropzone = document.querySelector('[data-dl-dropzone]');

    const submitUpload = () => {
        if (!uploadInput?.files?.length) return;
        dropzone?.classList.add('is-uploading');
        uploadForm.submit();
    };

    document.querySelectorAll('[data-dl-upload-trigger]').forEach((btn) => btn.addEventListener('click', () => {
        dropdowns().forEach((d) => { d.open = false; });
        uploadInput?.click();
    }));
    uploadInput?.addEventListener('change', submitUpload);

    if (dropzone && uploadInput) {
        let depth = 0;
        const hasFiles = (e) => [...(e.dataTransfer?.types || [])].includes('Files');
        dropzone.addEventListener('dragenter', (e) => { if (!hasFiles(e)) return; e.preventDefault(); depth++; dropzone.classList.add('is-dragover'); });
        dropzone.addEventListener('dragover', (e) => { if (hasFiles(e)) e.preventDefault(); });
        dropzone.addEventListener('dragleave', () => { depth = Math.max(0, depth - 1); if (!depth) dropzone.classList.remove('is-dragover'); });
        dropzone.addEventListener('drop', (e) => {
            if (!hasFiles(e)) return;
            e.preventDefault();
            depth = 0;
            dropzone.classList.remove('is-dragover');
            const dt = new DataTransfer();
            [...e.dataTransfer.files].forEach((f) => dt.items.add(f));
            uploadInput.files = dt.files;
            submitUpload();
        });
    }

    /* ---------- Share: people filter and selection count ---------- */
    document.querySelectorAll('[data-dl-grant]').forEach((form) => {
        const filter = form.querySelector('[data-dl-people-filter]');
        const labels = [...form.querySelectorAll('[data-dl-people] label')];
        const chosen = form.querySelector('[data-dl-chosen]');
        const update = () => {
            const names = labels.filter((l) => l.querySelector('input').checked).map((l) => l.querySelector('span').firstChild.textContent.trim());
            chosen.textContent = names.length ? `Selected: ${names.join(', ')}` : 'No one selected';
        };
        filter?.addEventListener('input', () => {
            const q = filter.value.trim().toLowerCase();
            labels.forEach((l) => { l.hidden = q !== '' && !l.dataset.search.includes(q); });
        });
        filter?.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        labels.forEach((l) => l.querySelector('input').addEventListener('change', update));
        update();
        if (location.hash === '#grant') filter?.focus();
    });
});
