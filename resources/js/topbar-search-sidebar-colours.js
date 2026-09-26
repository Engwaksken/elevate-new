document.addEventListener('DOMContentLoaded', () => {
    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    const topbar=document.querySelector('[data-admin-topbar="true"]');

    if(topbar){
        document.body.classList.add('eh-has-admin-topbar');
    }

    if(!sidebar) return;

    const norm=value=>(value || '').trim().replace(/\s+/g,' ').toLowerCase();

    // 1) Remove sidebar Global Search item completely.
    sidebar.querySelectorAll('a[href]').forEach(link=>{
        const label=norm(link.textContent);
        const href=(link.getAttribute('href') || '').toLowerCase();

        if(
            label==='global search' ||
            label==='search' ||
            href.includes('/admin/search')
        ){
            link.classList.add('eh-sidebar-global-search-hidden');
        }
    });

    // 2) Ensure Executive Dashboard is inside the existing Overview group.
    const template=document.querySelector('[data-eh-overview-sidebar-template]');
    if(template){
        const existingExecutive=[...sidebar.querySelectorAll('a[href]')]
            .find(a=>norm(a.textContent)==='executive dashboard');

        if(!existingExecutive){
            const generated=template.content.cloneNode(true)
                .querySelector('[data-eh-sidebar-generated="executive-dashboard"]');

            if(generated){
                const dashboard=[...sidebar.querySelectorAll('a[href]')]
                    .find(a=>norm(a.textContent)==='dashboard');

                if(dashboard && dashboard.parentElement){
                    dashboard.insertAdjacentElement('afterend',generated);
                }
            }
        }
    }

    // 3) Colour sidebar links according to their nearest existing section heading.
    const sectionMap=[
        {keys:['overview'], cls:'eh-sidebar-colour-overview'},
        {keys:['people & access','people','access'], cls:'eh-sidebar-colour-people'},
        {keys:['programme management','program management'], cls:'eh-sidebar-colour-programme'},
        {keys:['programme delivery','program delivery'], cls:'eh-sidebar-colour-delivery'},
        {keys:['planning & meal','planning and meal','meal'], cls:'eh-sidebar-colour-meal'},
        {keys:['hr','human resources'], cls:'eh-sidebar-colour-hr'},
        {keys:['procurement'], cls:'eh-sidebar-colour-procurement'},
        {keys:['assets & operations','assets and operations','assets','operations'], cls:'eh-sidebar-colour-assets'},
        {keys:['settings','system'], cls:'eh-sidebar-colour-settings'},
    ];

    const elements=[...sidebar.querySelectorAll('*')];
    let currentClass='eh-sidebar-colour-overview';

    elements.forEach(el=>{
        const text=norm(el.textContent);

        // Detect compact heading-like elements only.
        const isHeading=
            el.children.length===0 &&
            text.length>0 &&
            text.length<40 &&
            (
                el.tagName==='H3' ||
                el.tagName==='H4' ||
                el.className.toString().toLowerCase().includes('section') ||
                el.className.toString().toLowerCase().includes('heading') ||
                el.className.toString().toLowerCase().includes('title')
            );

        if(isHeading){
            for(const group of sectionMap){
                if(group.keys.some(key=>text===key || text.includes(key))){
                    currentClass=group.cls;
                    break;
                }
            }
            return;
        }

        if(el.matches('a[href]') && sidebar.contains(el)){
            sectionMap.forEach(group=>el.classList.remove(group.cls));
            el.classList.add(currentClass);
        }
    });
});
