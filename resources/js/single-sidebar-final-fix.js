document.addEventListener('DOMContentLoaded', () => {
    const template = document.querySelector('[data-sidebar-links-template]');

    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    if (!sidebar) return;

    // Remove every old generated/floating sidebar block from previous patches.
    document.querySelectorAll(
        '[data-sidebar-extension="true"],[data-main-sidebar-links="true"],.eh-sidebar-extension,.eh-main-sidebar-links'
    ).forEach(node => node.remove());

    // Remove any generated links from previous runs before rebuilding.
    sidebar.querySelectorAll('[data-eh-generated-sidebar-link="true"]').forEach(node => node.remove());
    sidebar.querySelectorAll('.eh-native-sidebar-heading').forEach(node => node.remove());

    if (!template) return;

    // Find the same navigation container used by existing links.
    const existingLinks = [...sidebar.querySelectorAll('a[href]')];

    let navContainer = null;

    if (existingLinks.length) {
        // Prefer the closest common navigation wrapper of an existing sidebar link.
        const firstLink = existingLinks[0];
        navContainer =
            firstLink.closest('nav') ||
            firstLink.closest('ul') ||
            firstLink.parentElement;
    }

    if (!navContainer || !sidebar.contains(navContainer)) {
        navContainer = sidebar;
    }

    // Find Sign Out / Logout so new links can appear before it in the SAME sidebar.
    let logoutNode = null;

    sidebar.querySelectorAll('form').forEach(form => {
        if (logoutNode) return;

        const text = (form.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();
        const action = (form.getAttribute('action') || '').toLowerCase();

        if (
            text.includes('sign out') ||
            text.includes('logout') ||
            action.endsWith('/logout') ||
            action.includes('/admin/logout')
        ) {
            logoutNode = form;
        }
    });

    const fragment = template.content.cloneNode(true);
    const links = [...fragment.querySelectorAll('[data-eh-generated-sidebar-link="true"]')];

    // Do not duplicate pages already on the native sidebar.
    const existingHrefs = new Set(
        [...sidebar.querySelectorAll('a[href]')]
            .map(a => (a.getAttribute('href') || '').replace(/\/+$/,''))
            .filter(Boolean)
    );

    const newLinks = links.filter(link => {
        const href = (link.getAttribute('href') || '').replace(/\/+$/,'');
        return href && !existingHrefs.has(href);
    });

    if (!newLinks.length) return;

    const heading = document.createElement('div');
    heading.className = 'eh-native-sidebar-heading';
    heading.textContent = 'Management';

    const insert = node => {
        if (logoutNode && logoutNode.parentElement === navContainer) {
            navContainer.insertBefore(node, logoutNode);
        } else {
            navContainer.appendChild(node);
        }
    };

    insert(heading);

    newLinks.forEach(link => {
        insert(link);
    });

    // Final duplicate sweep across the entire single sidebar.
    const seen = new Set();

    sidebar.querySelectorAll('a[href]').forEach(link => {
        const href = (link.getAttribute('href') || '').replace(/\/+$/,'');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        if (seen.has(href)) {
            if (link.dataset.ehGeneratedSidebarLink === 'true') {
                link.remove();
            }
            return;
        }

        seen.add(href);
    });

    if (!sidebar.querySelector('[data-eh-generated-sidebar-link="true"]')) {
        heading.remove();
    }
});
