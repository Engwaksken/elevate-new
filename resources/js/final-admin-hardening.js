document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('aside') || document.querySelector('[class*="sidebar"]');

    if (sidebar) {
        const seen = new Set();

        sidebar.querySelectorAll('a[href]').forEach(link => {
            const href = (link.getAttribute('href') || '').replace(/\/+$/, '');

            if (!href || href === '#' || href.startsWith('javascript:')) return;

            if (seen.has(href) && link.closest('[data-sidebar-extension="true"]')) {
                link.remove();
                return;
            }

            seen.add(href);
        });

        sidebar.querySelectorAll('[data-sidebar-extension="true"]').forEach((group,index) => {
            if (index > 0) group.remove();
            else if (!group.querySelector('a[href]')) group.remove();
        });
    }

    document.querySelectorAll('.alert-success,.alert-error,.alert-danger,.alert-warning,.alert-info').forEach(alert => {
        if (alert.dataset.noAutoDismiss === 'true') return;

        window.setTimeout(() => {
            if (!alert.isConnected) return;
            alert.classList.add('eh-flash-hiding');
            window.setTimeout(() => alert.remove(),250);
        },5000);
    });
});
