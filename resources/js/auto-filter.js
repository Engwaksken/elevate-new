// Filter bars that apply themselves: add data-auto-filter to a GET form.
// - dropdowns and checkboxes submit as soon as they change
// - text/search fields submit after the user pauses typing (Enter still submits at once)
// - date/month fields submit after a short pause, once the date is complete
// - submit buttons without a name (Apply / Search / Filter / Go) are hidden; they stay for browsers without JS
// - the Reset link (text "Reset", or [data-auto-filter-reset]) only shows while a filter is applied
// - after the reload, focus returns to the field being typed in so typing can continue

const TYPING_DELAY_MS = 600;
const DATE_DELAY_MS = 800;
const FOCUS_KEY = 'auto-filter:focus';
const IGNORED_PARAMS = /(^|_)(per_page|page)$/;

function submit(form) {
    if (form.dataset.autoFilterSubmitting) return;
    form.dataset.autoFilterSubmitting = '1';
    form.classList.add('is-filtering');
    form.requestSubmit ? form.requestSubmit() : form.submit();
}

function isTextField(field) {
    return field.matches('input:not([type]), input[type="text"], input[type="search"], input[type="email"], input[type="tel"], input[type="number"]');
}

function isDateField(field) {
    return field.matches('input[type="date"], input[type="month"], input[type="week"], input[type="datetime-local"], input[type="time"]');
}

// A typed date is only worth searching for once it is empty or a real, plausible date.
function dateReady(field) {
    if (field.value === '') return true;
    if (!field.checkValidity()) return false;
    const year = parseInt(field.value.slice(0, 4), 10);
    return Number.isNaN(year) || year >= 1900;
}

function hasActiveFilter(form) {
    const query = new URLSearchParams(window.location.search);
    return [...form.elements].some((field) =>
        field.name
        && field.type !== 'hidden'
        && !IGNORED_PARAMS.test(field.name)
        && query.getAll(field.name).some((value) => value.trim() !== ''));
}

function resetLinks(form) {
    return [...form.querySelectorAll('a')].filter((link) =>
        link.hasAttribute('data-auto-filter-reset') || link.textContent.trim() === 'Reset');
}

function restoreFocus(form) {
    let name = null;
    try {
        name = sessionStorage.getItem(FOCUS_KEY);
        sessionStorage.removeItem(FOCUS_KEY);
    } catch (e) { /* storage unavailable */ }
    if (!name) return;

    const field = form.elements.namedItem(name);
    if (!(field instanceof HTMLInputElement)) return;
    field.focus();
    const end = field.value.length;
    try { field.setSelectionRange(end, end); } catch (e) { /* not a text input */ }
}

function rememberFocus(field) {
    try { sessionStorage.setItem(FOCUS_KEY, field.name); } catch (e) { /* storage unavailable */ }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
        form.querySelectorAll('button:not([type="button"]):not([type="reset"]):not([name]), input[type="submit"]:not([name]), [data-auto-filter-submit]')
            .forEach((button) => { button.hidden = true; });

        if (!hasActiveFilter(form)) {
            resetLinks(form).forEach((link) => { link.hidden = true; });
        }

        let timer = null;
        const later = (delay, callback) => {
            clearTimeout(timer);
            timer = setTimeout(callback, delay);
        };

        form.addEventListener('change', (event) => {
            const field = event.target;
            if (!field.name || isTextField(field)) return;

            if (isDateField(field)) {
                later(DATE_DELAY_MS, () => {
                    if (!dateReady(field)) return;
                    if (document.activeElement === field) rememberFocus(field);
                    submit(form);
                });
                return;
            }

            submit(form);
        });

        form.addEventListener('input', (event) => {
            const field = event.target;
            if (!field.name || !isTextField(field)) return;
            later(TYPING_DELAY_MS, () => {
                if (field.value.trim() === (field.defaultValue || '').trim()) return;
                rememberFocus(field);
                submit(form);
            });
        });

        form.addEventListener('submit', () => clearTimeout(timer));

        restoreFocus(form);
    });
});

// Coming back with the browser's Back button can restore the dimmed, submitting state.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
        delete form.dataset.autoFilterSubmitting;
        form.classList.remove('is-filtering');
    });
});
