/*
 * Frontend CMS editors: section builder, media uploads, colour pickers,
 * navigation link rows and the header live preview.
 */
const fieldName = (prefix, field) => `${prefix}[${String(field).split('.').join('][')}]`;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_token"]')?.value || '';

/* ---------- Colour fields ---------- */
const initColors = (root = document) => {
    root.querySelectorAll('[data-color]').forEach((wrap) => {
        if (wrap.dataset.ready) return;
        wrap.dataset.ready = '1';
        const picker = wrap.querySelector('[data-color-picker]');
        const text = wrap.querySelector('[data-color-text]');
        const paint = () => wrap.classList.toggle('is-empty', !text.value);
        picker.addEventListener('input', () => {
            text.value = picker.value;
            paint();
            text.dispatchEvent(new Event('input', { bubbles: true }));
        });
        text.addEventListener('input', () => {
            if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value;
            paint();
        });
        wrap.querySelector('[data-color-clear]')?.addEventListener('click', () => {
            text.value = '';
            paint();
            text.dispatchEvent(new Event('input', { bubbles: true }));
        });
        paint();
    });
};

/* ---------- Range outputs ---------- */
document.addEventListener('input', (event) => {
    const range = event.target.closest?.('[data-range]');
    if (!range) return;
    const out = range.closest('div, .form-group')?.querySelector('[data-range-out]');
    if (out) out.textContent = `${range.value}${range.dataset.rangeUnit || '%'}`;
});

/* ---------- Media upload ---------- */
const uploadUrl = () => document.querySelector('[data-upload-url]')?.dataset.uploadUrl;

const uploadLimit = () => Number(document.querySelector('[data-upload-limit]')?.dataset.uploadLimit) || 0;
const formatMb = (bytes) => `${(bytes / 1048576).toFixed(1)} MB`;

/*
 * Large photos are resized in the browser (max 2400px, WebP/JPEG) so they
 * fit shared-hosting upload limits and load quickly. GIFs keep their
 * animation and small files are sent unchanged.
 */
const shrinkImage = (file) => new Promise((resolve) => {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 600 * 1024) return resolve(file);
    const img = new Image();
    const src = URL.createObjectURL(file);
    img.onload = () => {
        const scale = Math.min(1, 2400 / Math.max(img.naturalWidth, img.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(img.naturalWidth * scale);
        canvas.height = Math.round(img.naturalHeight * scale);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(src);
        const done = (blob, type, ext) => {
            if (!blob || blob.size >= file.size) return resolve(file);
            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + ext, { type }));
        };
        canvas.toBlob((webp) => {
            if (webp && webp.type === 'image/webp') return done(webp, 'image/webp', '.webp');
            canvas.toBlob((jpg) => done(jpg, 'image/jpeg', '.jpg'), 'image/jpeg', 0.85);
        }, 'image/webp', 0.85);
    };
    img.onerror = () => { URL.revokeObjectURL(src); resolve(file); };
    img.src = src;
});

const uploadFile = async (original) => {
    const url = uploadUrl();
    if (!url) throw new Error('Uploads are not available right now. Ask an administrator to run "php artisan optimize:clear" on the server.');
    const file = await shrinkImage(original);
    const limit = uploadLimit();
    if (limit && file.size > limit) {
        throw new Error(`${original.name} is ${formatMb(file.size)}; this server accepts files up to ${formatMb(limit)}. Use a smaller file${file.type.startsWith('video/') ? ' or a YouTube/Vimeo link' : ''}.`);
    }
    const body = new FormData();
    body.append('file', file);
    const res = await fetch(url, {
        method: 'POST',
        body,
        headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = data?.errors?.file?.[0]
            || (res.status === 419 ? 'Your session expired. Save your work, reload the page and try again.' : null)
            || (res.status === 413 ? `The file is too large for the server${uploadLimit() ? ` (limit ${formatMb(uploadLimit())})` : ''}.` : null)
            || (res.status === 403 ? 'You do not have permission to upload website media.' : null)
            || data?.message
            || `The server returned an error (${res.status}).`;
        throw new Error(msg);
    }
    return data;
};

const renderMediaPreview = (wrap) => {
    const input = wrap.querySelector('[data-media-input]');
    const preview = wrap.querySelector('[data-media-preview]');
    if (!input || !preview) return;
    const value = input.value.trim();
    preview.innerHTML = '';
    if (!value) return;
    const path = value.split('?')[0].toLowerCase();
    if (/\.(mp4|webm)$/.test(path)) {
        const video = document.createElement('video');
        video.src = value;
        video.muted = true;
        video.preload = 'metadata';
        preview.appendChild(video);
    } else if (/youtube|youtu\.be|vimeo/.test(path)) {
        preview.innerHTML = '<span class="cms-media__chip"><i class="fas fa-circle-play"></i> Online video</span>';
    } else {
        const img = document.createElement('img');
        img.src = value;
        img.alt = '';
        img.onerror = () => { preview.innerHTML = '<span class="cms-media__chip is-error">Image could not be loaded</span>'; };
        preview.appendChild(img);
    }
};

const initMedia = (root = document) => {
    root.querySelectorAll('[data-media]').forEach(renderMediaPreview);
};

document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-media-upload]');
    if (!btn) return;
    btn.closest('[data-media]')?.querySelector('[data-media-file]')?.click();
});

document.addEventListener('change', async (event) => {
    const fileInput = event.target.closest?.('[data-media-file]');
    if (!fileInput || !fileInput.files.length) return;
    const wrap = fileInput.closest('[data-media]');
    const status = wrap.querySelector('[data-media-status]');
    const btn = wrap.querySelector('[data-media-upload]');
    btn.disabled = true;
    status.className = 'cms-media__status';
    status.textContent = 'Uploading…';
    try {
        const data = await uploadFile(fileInput.files[0]);
        const input = wrap.querySelector('[data-media-input]');
        input.value = data.url;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        status.textContent = 'Uploaded.';
    } catch (err) {
        status.classList.add('is-error');
        status.textContent = `Upload failed: ${err.message}`;
        alert(`Upload failed: ${err.message}`);
    } finally {
        btn.disabled = false;
        fileInput.value = '';
    }
});

document.addEventListener('input', (event) => {
    const input = event.target.closest?.('[data-media-input]');
    if (input) renderMediaPreview(input.closest('[data-media]'));
});

/* ---------- Navigation link rows ---------- */
const reindexLinks = (list) => {
    list.querySelectorAll('[data-link-row]').forEach((row, i) => {
        row.querySelectorAll('[data-link-field]').forEach((el) => {
            el.name = `links[${i}][${el.dataset.linkField}]`;
        });
    });
    list.dispatchEvent(new CustomEvent('links:changed', { bubbles: true }));
};

document.addEventListener('click', (event) => {
    const list = event.target.closest('[data-links]');
    if (!list) return;
    const rows = list.querySelector('[data-link-rows]');
    if (event.target.closest('[data-link-add]')) {
        const tpl = list.querySelector('[data-link-template]');
        const row = tpl.content.firstElementChild.cloneNode(true);
        rows.appendChild(row);
        reindexLinks(list);
        row.querySelector('input')?.focus();
        return;
    }
    const row = event.target.closest('[data-link-row]');
    if (!row) return;
    if (event.target.closest('[data-link-remove]')) {
        row.remove();
        reindexLinks(list);
    } else if (event.target.closest('[data-link-move]')) {
        const dir = event.target.closest('[data-link-move]').dataset.linkMove;
        if (dir === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        if (dir === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
        reindexLinks(list);
    }
});

/* ---------- Section builder ---------- */
const initBuilder = (builder) => {
    const blocksEl = builder.querySelector('[data-cms-blocks]');
    const emptyEl = builder.querySelector('[data-cms-empty]');
    // Templates are rendered next to the builder, not inside it.
    const findTemplate = (selector) => builder.querySelector(selector)
        || builder.parentElement?.querySelector(selector)
        || document.querySelector(selector);

    const reindexNested = (block, i) => {
        block.querySelectorAll('[data-column]').forEach((column, c) => {
            const width = column.querySelector('[data-column-width]');
            if (width) width.name = `sections[${i}][columns][${c}][width]`;
            column.querySelectorAll('[data-widget]').forEach((widget, w) => {
                const prefix = `sections[${i}][columns][${c}][widgets][${w}]`;
                const typeField = widget.querySelector('[data-widget-type-field]');
                if (typeField) typeField.name = `${prefix}[type]`;
                widget.querySelectorAll('[data-widget-field]').forEach((el) => {
                    el.name = fieldName(prefix, el.dataset.widgetField);
                });
            });
        });
    };

    const reindex = () => {
        const blocks = [...blocksEl.querySelectorAll(':scope > [data-block]')];
        blocks.forEach((block, i) => {
            const typeField = block.querySelector('[data-block-type-field]');
            if (typeField) typeField.name = `sections[${i}][type]`;
            block.querySelectorAll('[data-block-field]').forEach((el) => {
                el.name = fieldName(`sections[${i}]`, el.dataset.blockField);
            });
            block.querySelectorAll('[data-item]').forEach((item, j) => {
                item.querySelectorAll('[data-item-field]').forEach((el) => {
                    el.name = fieldName(`sections[${i}][items][${j}]`, el.dataset.itemField);
                });
            });
            reindexNested(block, i);
        });
        if (emptyEl) emptyEl.hidden = blocks.length > 0;
    };

    const syncBackground = (block) => {
        const panel = block.querySelector('[data-style-panel]');
        if (!panel) return;
        const mode = panel.querySelector('[data-bg-mode]:checked')?.value || 'none';
        panel.querySelectorAll('[data-bg-show]').forEach((el) => {
            el.hidden = !el.dataset.bgShow.split(' ').includes(mode);
        });
        const badge = panel.querySelector('[data-style-badge]');
        const label = panel.querySelector('[data-bg-mode]:checked')?.closest('label')?.textContent.trim();
        if (badge && label) badge.textContent = label;
    };

    const syncSummary = (block) => {
        const summary = block.querySelector('[data-block-summary]');
        if (!summary) return;
        const label = block.querySelector('[data-block-label]')?.value.trim();
        const title = block.querySelector('[data-block-field="title"]')?.value.trim();
        summary.textContent = label || title || '';
        block.classList.toggle('is-hidden', !!block.querySelector('[data-block-hidden]')?.checked);
    };

    const prepare = (block) => {
        initColors(block);
        initMedia(block);
        syncBackground(block);
        syncSummary(block);
    };

    const appendBlock = (node, { open = true, after = null } = {}) => {
        if (after) after.after(node); else blocksEl.appendChild(node);
        if (open) node.removeAttribute('data-collapsed');
        reindex();
        prepare(node);
        return node;
    };

    const addBlock = (type) => {
        const tpl = findTemplate(`[data-block-template="${type}"]`);
        if (!tpl) return;
        const node = appendBlock(tpl.content.firstElementChild.cloneNode(true));
        node.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const addItem = (block, type, values = {}) => {
        const tpl = findTemplate(`[data-item-template="${type}"]`);
        const items = block.querySelector('[data-items]');
        if (!tpl || !items) return null;
        const item = tpl.content.firstElementChild.cloneNode(true);
        Object.entries(values).forEach(([field, value]) => {
            const el = item.querySelector(`[data-item-field="${field}"]`);
            if (el) el.value = value;
        });
        items.appendChild(item);
        reindex();
        initMedia(item);
        return item;
    };

    const addColumn = (block) => {
        const tpl = findTemplate('[data-column-template]');
        const columns = block.querySelector('[data-columns]');
        if (!tpl || !columns) return;
        columns.appendChild(tpl.content.firstElementChild.cloneNode(true));
        reindex();
    };

    const addWidget = (column, type) => {
        const tpl = findTemplate(`[data-widget-template="${type}"]`);
        const widgets = column.querySelector('[data-widgets]');
        if (!tpl || !widgets) return;
        const widget = tpl.content.firstElementChild.cloneNode(true);
        widgets.appendChild(widget);
        reindex();
        initMedia(widget);
    };

    const duplicate = (block) => {
        // Radios keep their checked state only within a uniquely named group,
        // so remember the selection before the clone shares the same names.
        const checked = [...block.querySelectorAll('input[type="radio"]')].map((r) => r.checked);
        const copy = block.cloneNode(true);
        block.querySelectorAll('textarea, select').forEach((el, idx) => {
            const target = copy.querySelectorAll('textarea, select')[idx];
            if (target) target.value = el.value;
        });
        appendBlock(copy, { after: block });
        [block, copy].forEach((b) => b.querySelectorAll('input[type="radio"]').forEach((r, idx) => { r.checked = checked[idx]; }));
        copy.querySelectorAll('[data-color]').forEach((c) => delete c.dataset.ready);
        prepare(copy);
    };

    const move = (el, dir) => {
        const parent = el.parentElement;
        if (dir === 'up' && el.previousElementSibling) parent.insertBefore(el, el.previousElementSibling);
        else if (dir === 'down' && el.nextElementSibling) parent.insertBefore(el.nextElementSibling, el);
        reindex();
    };

    builder.addEventListener('click', async (event) => {
        const t = event.target;
        const addBlockBtn = t.closest('[data-add-block]');
        if (addBlockBtn) return addBlock(addBlockBtn.dataset.addBlock);

        if (t.closest('[data-expand-all]')) return blocksEl.querySelectorAll('[data-block]').forEach((b) => b.removeAttribute('data-collapsed'));
        if (t.closest('[data-collapse-all]')) return blocksEl.querySelectorAll('[data-block]').forEach((b) => b.setAttribute('data-collapsed', ''));

        const presetBtn = t.closest('[data-load-preset]');
        if (presetBtn) {
            const preset = builder.parentElement.querySelector('[data-preset]') || document.querySelector('[data-preset]');
            if (!preset) return;
            const existing = blocksEl.querySelectorAll(':scope > [data-block]');
            if (existing.length) {
                if (!confirm('Replace the current sections with the original home page content and layout? Nothing changes on the live site until you save or publish.')) return;
                existing.forEach((b) => b.remove());
            }
            [...preset.content.children].forEach((node, i) => appendBlock(node.cloneNode(true), { open: i === 0 }));
            return;
        }

        const block = t.closest('[data-block]');
        if (!block) return;

        if (t.closest('[data-block-toggle]')) return block.toggleAttribute('data-collapsed');
        if (t.closest('[data-block-remove]')) {
            if (confirm('Remove this section?')) { block.remove(); reindex(); }
            return;
        }
        if (t.closest('[data-block-duplicate]')) return duplicate(block);
        const blockMove = t.closest('[data-block-move]');
        if (blockMove) return move(block, blockMove.dataset.blockMove);

        const addItemBtn = t.closest('[data-add-item]');
        if (addItemBtn) return addItem(block, addItemBtn.dataset.addItem);
        if (t.closest('[data-item-remove]')) { t.closest('[data-item]').remove(); reindex(); return; }
        const itemMove = t.closest('[data-item-move]');
        if (itemMove) return move(t.closest('[data-item]'), itemMove.dataset.itemMove);

        if (t.closest('[data-gallery-bulk]')) return block.querySelector('[data-gallery-files]')?.click();

        if (t.closest('[data-add-column]')) return addColumn(block);
        const addWidgetBtn = t.closest('[data-add-widget]');
        if (addWidgetBtn) return addWidget(addWidgetBtn.closest('[data-column]'), addWidgetBtn.dataset.addWidget);
        if (t.closest('[data-column-remove]')) { t.closest('[data-column]').remove(); reindex(); return; }
        const columnMove = t.closest('[data-column-move]');
        if (columnMove) return move(t.closest('[data-column]'), columnMove.dataset.columnMove);
        if (t.closest('[data-widget-remove]')) { t.closest('[data-widget]').remove(); reindex(); return; }
        const widgetMove = t.closest('[data-widget-move]');
        if (widgetMove) return move(t.closest('[data-widget]'), widgetMove.dataset.widgetMove);
        return undefined;
    });

    builder.addEventListener('change', async (event) => {
        const block = event.target.closest('[data-block]');
        if (!block) return;
        if (event.target.matches('[data-bg-mode]')) syncBackground(block);
        if (event.target.matches('[data-block-hidden]')) syncSummary(block);

        if (event.target.matches('[data-gallery-files]')) {
            const input = event.target;
            const files = [...input.files];
            const btn = block.querySelector('[data-gallery-bulk]');
            // Fill an empty first row before adding new ones.
            const emptyRows = [...block.querySelectorAll('[data-item]')].filter((row) => !row.querySelector('[data-item-field="image_url"]')?.value);
            for (const [n, file] of files.entries()) {
                btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Uploading ${n + 1} of ${files.length}…`;
                try {
                    const data = await uploadFile(file);
                    const row = emptyRows.shift();
                    if (row) {
                        const field = row.querySelector('[data-item-field="image_url"]');
                        field.value = data.url;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    } else {
                        addItem(block, 'gallery', { image_url: data.url });
                    }
                } catch (err) {
                    alert(`${file.name}: ${err.message}`);
                }
            }
            btn.innerHTML = '<i class="fas fa-upload"></i> Upload several images';
            input.value = '';
        }
    });

    builder.addEventListener('input', (event) => {
        const block = event.target.closest('[data-block]');
        if (block && event.target.matches('[data-block-label], [data-block-field="title"]')) syncSummary(block);
    });

    blocksEl.querySelectorAll(':scope > [data-block]').forEach(prepare);
    // Open the first section so the editor never looks empty.
    blocksEl.querySelector(':scope > [data-block]')?.removeAttribute('data-collapsed');
    reindex();
};

/* ---------- Header editor live preview ---------- */
const initHeaderPreview = (form) => {
    const pv = (sel) => form.querySelector(sel);
    const header = pv('[data-pv-header]');
    const announce = pv('[data-pv-announce]');
    if (!header) return;
    const set = (key, value) => {
        if (key === 'name') pv('[data-pv-name]').textContent = value;
        if (key === 'tagline') pv('[data-pv-tagline]').textContent = value;
        if (key === 'logo_height') pv('[data-pv-logo]').style.height = `${value}px`;
        if (key === 'cta') { pv('[data-pv-cta]').textContent = value; pv('[data-pv-cta]').hidden = !value; }
        if (key === 'announce_text') pv('[data-pv-announce-text]').textContent = value;
        if (key === 'announce_link') pv('[data-pv-announce-link]').textContent = value;
    };
    form.addEventListener('input', (event) => {
        const el = event.target;
        if (el.dataset.pvInput) set(el.dataset.pvInput, el.value);
        if (el.dataset.pvColor) {
            const v = el.value;
            if (el.dataset.pvColor === 'header-bg') header.style.background = v || '#ffffff';
            if (el.dataset.pvColor === 'header-fg') header.style.color = v || '#800000';
            if (el.dataset.pvColor === 'announce-bg') announce.style.background = v || '#d4af37';
            if (el.dataset.pvColor === 'announce-fg') announce.style.color = v || '#1b1b1b';
        }
    });
    form.addEventListener('change', (event) => {
        const el = event.target;
        if (el.dataset.pvToggle === 'name') pv('[data-pv-name]').hidden = !el.checked;
        if (el.dataset.pvToggle === 'tagline') pv('[data-pv-tagline]').hidden = !el.checked;
        if (el.dataset.pvToggle === 'announce') announce.hidden = !el.checked;
        if (el.matches('[data-pv-logo-file]') && el.files[0]) {
            const img = pv('[data-pv-logo]');
            img.src = URL.createObjectURL(el.files[0]);
            img.hidden = false;
        }
    });
    form.addEventListener('links:changed', () => {
        pv('[data-pv-links]').innerHTML = '';
        form.querySelectorAll('[data-link-field="label"]').forEach((input) => {
            if (!input.value.trim()) return;
            const span = document.createElement('span');
            span.textContent = input.value;
            pv('[data-pv-links]').appendChild(span);
        });
    });
    form.addEventListener('input', (event) => {
        if (event.target.matches('[data-link-field="label"]')) form.dispatchEvent(new CustomEvent('links:changed'));
    });
};

/* ---------- Unsaved-changes guard ---------- */
const guardUnsaved = (form) => {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initColors();
    initMedia();
    document.querySelectorAll('[data-cms-builder]').forEach(initBuilder);
    document.querySelectorAll('[data-chrome-editor="header"]').forEach(initHeaderPreview);
    document.querySelectorAll('form.cms-editor').forEach(guardUnsaved);
});
