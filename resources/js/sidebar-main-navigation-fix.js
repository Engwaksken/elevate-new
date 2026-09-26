document.addEventListener('DOMContentLoaded', () => {
    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    const mainLinks=document.querySelector('[data-main-sidebar-links="true"]');

    if(!sidebar || !mainLinks) return;

    // Ensure these links are physically part of the main sidebar.
    if(!sidebar.contains(mainLinks)){
        const logoutForm=[...sidebar.querySelectorAll('form')].find(form=>{
            const text=(form.textContent || '').toLowerCase();
            const action=(form.getAttribute('action') || '').toLowerCase();
            return text.includes('sign out') || text.includes('logout') || action.endsWith('/logout');
        });

        if(logoutForm){
            logoutForm.before(mainLinks);
        }else{
            sidebar.appendChild(mainLinks);
        }
    }

    // Remove old generated sidebar extension containers.
    sidebar.querySelectorAll('[data-sidebar-extension="true"]').forEach(node=>node.remove());

    // Deduplicate by href, preserving the first native/main-sidebar link.
    const seen=new Set();

    sidebar.querySelectorAll('a[href]').forEach(link=>{
        const href=(link.getAttribute('href') || '').replace(/\/+$/,'');
        if(!href || href==='#' || href.startsWith('javascript:')) return;

        if(seen.has(href) && link.closest('[data-main-sidebar-links="true"]')){
            link.remove();
            return;
        }

        seen.add(href);
    });

    // Remove empty generated group.
    if(!mainLinks.querySelector('a[href]')){
        mainLinks.remove();
    }
});
