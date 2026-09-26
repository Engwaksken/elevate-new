document.addEventListener('DOMContentLoaded', () => {
    const topbar = document.querySelector('[data-admin-topbar="true"]');

    if (!topbar) return;

    document.body.classList.add('eh-has-admin-topbar');

    const profile = topbar.querySelector('[data-profile-menu]');
    const trigger = topbar.querySelector('[data-profile-menu-trigger]');
    const panel = topbar.querySelector('[data-profile-menu-panel]');

    const closeProfile = () => {
        if (!panel || !trigger) return;
        panel.hidden = true;
        trigger.setAttribute('aria-expanded','false');
    };

    if (trigger && panel) {
        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const opening = panel.hidden;
            panel.hidden = !opening;
            trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
        });

        document.addEventListener('click', event => {
            if (profile && !profile.contains(event.target)) closeProfile();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeProfile();
        });
    }

    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    if (sidebar) {
        // Hide Search/Profile links in the sidebar once the same controls exist in the topbar.
        sidebar.querySelectorAll('a[href]').forEach(link => {
            const label = (link.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();
            const href = (link.getAttribute('href') || '').toLowerCase();

            const isSearch =
                label === 'search' ||
                label === 'global search' ||
                href.includes('/admin/search');

            const isProfile =
                label === 'profile' ||
                label === 'my profile' ||
                href.endsWith('/profile') ||
                href.includes('/profile/edit');

            if (isSearch || isProfile) {
                link.classList.add('eh-sidebar-account-hidden');
            }
        });

        // Hide POST logout form in the sidebar. Do not alter its route/method.
        sidebar.querySelectorAll('form[method="POST"],form[method="post"]').forEach(form => {
            const text = (form.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();
            const action = (form.getAttribute('action') || '').toLowerCase();

            if (
                text.includes('sign out') ||
                text.includes('logout') ||
                action.endsWith('/logout') ||
                action.includes('/admin/logout')
            ) {
                form.classList.add('eh-sidebar-account-hidden');
            }
        });
    }

    const mobileToggle = topbar.querySelector('[data-admin-sidebar-toggle]');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => {
            document.body.classList.toggle('eh-admin-sidebar-open');
            sidebar.classList.toggle('is-open');
            sidebar.classList.toggle('open');
        });
    }
});
