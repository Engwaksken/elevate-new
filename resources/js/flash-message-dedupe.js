function normaliseFlashText(element) {
    return (element.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function flashType(element) {
    const classes = Array.from(element.classList || []);

    if (
        classes.includes('alert-success')
        || classes.includes('success')
        || classes.includes('toast-success')
        || element.dataset.flashType === 'success'
    ) return 'success';

    if (
        classes.includes('alert-error')
        || classes.includes('alert-danger')
        || classes.includes('error')
        || classes.includes('toast-error')
        || element.dataset.flashType === 'error'
    ) return 'error';

    if (
        classes.includes('alert-warning')
        || classes.includes('warning')
        || classes.includes('toast-warning')
        || element.dataset.flashType === 'warning'
    ) return 'warning';

    if (
        classes.includes('alert-info')
        || classes.includes('info')
        || classes.includes('toast-info')
        || element.dataset.flashType === 'info'
    ) return 'info';

    return null;
}

function deduplicateFlashMessages(root = document) {
    const selectors = [
        '.alert-success',
        '.alert-error',
        '.alert-danger',
        '.alert-warning',
        '.alert-info',
        '.toast-success',
        '.toast-error',
        '.toast-warning',
        '.toast-info',
        '[data-flash-type]'
    ].join(',');

    const seen = new Map();

    root.querySelectorAll(selectors).forEach(element => {
        if (element.dataset.flashDeduped === 'keep') return;

        const type = flashType(element);
        const text = normaliseFlashText(element);

        if (!type || !text) return;

        const key = `${type}::${text}`;

        if (seen.has(key)) {
            element.remove();
            return;
        }

        seen.set(key, element);
        element.dataset.flashDeduped = 'keep';

        if (!element.dataset.noAutoDismiss) {
            window.setTimeout(() => {
                if (!element.isConnected) return;

                element.classList.add('eh-flash-hiding');

                window.setTimeout(() => {
                    element.remove();
                }, 260);
            }, 5000);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    deduplicateFlashMessages(document);

    const observer = new MutationObserver(mutations => {
        let shouldCheck = false;

        for (const mutation of mutations) {
            if (mutation.addedNodes.length) {
                shouldCheck = true;
                break;
            }
        }

        if (shouldCheck) {
            deduplicateFlashMessages(document);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
});
