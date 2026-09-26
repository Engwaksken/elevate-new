document.addEventListener('DOMContentLoaded', () => {
    const topbar = document.querySelector('[data-admin-topbar="true"]');

    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    if (!sidebar) return;

    // Remove duplicate route extensions left by earlier sidebar packages.
    const extensions = [...sidebar.querySelectorAll('[data-sidebar-extension="true"]')];

    extensions.forEach((extension,index) => {
        if(index>0) extension.remove();
    });

    // Hide duplicate Search/Profile/Sign Out controls in the sidebar.
    sidebar.querySelectorAll('a[href]').forEach(link => {
        const label=(link.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();
        const href=(link.getAttribute('href') || '').toLowerCase();

        if(
            label==='search' ||
            label==='global search' ||
            label==='profile' ||
            label==='my profile' ||
            href.includes('/admin/search') ||
            href.endsWith('/admin/profile')
        ){
            link.classList.add('eh-sidebar-account-hidden');
        }
    });

    sidebar.querySelectorAll('form').forEach(form => {
        const text=(form.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();
        const action=(form.getAttribute('action') || '').toLowerCase();

        if(
            text.includes('sign out') ||
            text.includes('logout') ||
            action.endsWith('/logout') ||
            action.includes('/admin/logout')
        ){
            form.classList.add('eh-sidebar-account-hidden');
        }
    });

    // Hide the old sidebar user/profile card by matching the signed-in user's
    // exact email. This leaves the ElevateHer360 brand block untouched.
    if(topbar){
        const email=(topbar.dataset.authEmail || '').trim().toLowerCase();
        const name=(topbar.dataset.authName || '').trim().toLowerCase();

        const candidates=[...sidebar.querySelectorAll('div,section,article')];

        for(const element of candidates){
            const text=(element.textContent || '').trim().replace(/\s+/g,' ').toLowerCase();

            if(!text) continue;

            const hasEmail=email && text.includes(email);
            const hasName=name && text.includes(name);

            if(!(hasEmail || hasName)) continue;

            const rect=element.getBoundingClientRect();

            if(
                rect.height >= 55 &&
                rect.height <= 180 &&
                element.querySelectorAll('a').length <= 2
            ){
                element.classList.add('eh-sidebar-profile-hidden');
                break;
            }
        }
    }

    // Ensure only one copy of each sidebar href remains.
    const seen=new Set();

    sidebar.querySelectorAll('a[href]').forEach(link => {
        const href=(link.getAttribute('href') || '').replace(/\/+$/,'');
        if(!href || href==='#' || href.startsWith('javascript:')) return;

        if(seen.has(href) && link.closest('[data-sidebar-extension="true"]')){
            link.remove();
            return;
        }

        seen.add(href);
    });
});
