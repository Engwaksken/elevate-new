document.addEventListener('DOMContentLoaded', () => {
    const body=document.body;

    const sidebar=
        document.querySelector('.eh-admin-sidebar') ||
        document.querySelector('[data-admin-sidebar="true"]');

    if(!sidebar) return;

    body.classList.add('eh-admin-layout-fixed');

    const topbar=
        document.querySelector('[data-admin-topbar="true"]') ||
        document.querySelector('.eh-admin-topbar') ||
        document.querySelector('header[class*="admin"]');

    /*
     * Pick ONE top-level shell.
     * Never assign sidebar margin to nested main/admin-content elements.
     */
    let shell=null;

    const candidates=[
        document.querySelector('.admin-shell'),
        document.querySelector('.admin-layout'),
        document.querySelector('.admin-wrapper'),
        document.querySelector('.page-wrapper'),
        document.querySelector('.content-wrapper'),
        document.querySelector('.admin-main'),
        document.querySelector('main')
    ].filter(Boolean);

    shell=candidates.find(el => {
        if(sidebar.contains(el)) return false;
        const parent=el.parentElement;
        return parent===body || parent?.parentElement===body;
    }) || candidates.find(el=>!sidebar.contains(el)) || null;

    /*
     * If topbar and main are siblings under a shared wrapper, that wrapper is
     * the correct shell.
     */
    if(topbar && shell){
        const topParent=topbar.parentElement;
        if(
            topParent &&
            topParent!==body &&
            !sidebar.contains(topParent) &&
            topParent.contains(shell)
        ){
            shell=topParent;
        }
    }

    if(shell){
        shell.dataset.ehAdminMainShell='true';
    }

    /*
     * Find the actual page-content container. Prefer <main> inside the shell.
     */
    let content=null;

    if(shell){
        content=
            shell.querySelector(':scope > main') ||
            shell.querySelector('main') ||
            shell.querySelector('.admin-content') ||
            shell.querySelector('.content-wrapper') ||
            shell.querySelector('.page-content');
    }

    if(!content && shell){
        content=shell;
    }

    if(content){
        content.dataset.ehAdminContent='true';
    }

    /*
     * Remove old inline left offsets from nested elements. Those offsets were
     * stacking and creating the large blank column visible in the screenshot.
     */
    if(shell){
        [
            ...shell.querySelectorAll('main,.admin-main,.admin-content,.content-wrapper,.page-wrapper')
        ].forEach(el=>{
            if(el===shell) return;
            el.style.removeProperty('margin-left');
            el.style.removeProperty('width');
            el.style.removeProperty('max-width');
        });
    }

    /*
     * Ensure each sidebar navigation row has a visible icon box. Do not invent
     * icons; this only restores icons already present in the markup.
     */
    sidebar.querySelectorAll('.eh-admin-sidebar__links a').forEach(link=>{
        const icon=link.querySelector('i,svg,.fa,.fas,.far,.fab');
        if(icon){
            icon.setAttribute('aria-hidden','true');
        }
    });
});
