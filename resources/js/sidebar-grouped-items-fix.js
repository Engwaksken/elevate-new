document.addEventListener('DOMContentLoaded', () => {
    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    const template=document.querySelector('[data-sidebar-grouped-links-template]');

    if(!sidebar || !template) return;

    // Remove every old generated/floating management section.
    document.querySelectorAll(
        '[data-sidebar-extension="true"],[data-main-sidebar-links="true"],.eh-sidebar-extension,.eh-main-sidebar-links,.eh-native-sidebar-heading'
    ).forEach(node=>node.remove());

    // Remove prior generated copies.
    sidebar.querySelectorAll(
        '[data-eh-generated-sidebar-link="true"],[data-eh-generated-grouped-link="true"]'
    ).forEach(node=>node.remove());

    // Remove legacy visible heading "Management" / "More Management" only when
    // it belongs to an old generated block or sits immediately above generated links.
    [...sidebar.querySelectorAll('*')].forEach(node=>{
        if(node.children.length>0) return;
        const text=(node.textContent||'').trim().toLowerCase();
        if(text==='more management' || text==='management'){
            const next=node.nextElementSibling;
            if(
                node.classList.contains('eh-sidebar-section-title') ||
                node.classList.contains('eh-sidebar-extension-title') ||
                node.classList.contains('eh-native-sidebar-heading') ||
                (next && (
                    next.matches('[data-eh-generated-sidebar-link="true"]') ||
                    next.matches('[data-eh-generated-grouped-link="true"]')
                ))
            ){
                node.remove();
            }
        }
    });

    const normalize=s=>(s||'').trim().replace(/\s+/g,' ').toLowerCase();

    const existingLinks=[...sidebar.querySelectorAll('a[href]')];

    const findLinkByLabel=(labels)=>{
        for(const label of labels){
            const found=existingLinks.find(a=>normalize(a.textContent)===normalize(label));
            if(found) return found;
        }
        return null;
    };

    const groupAnchors={
        programme_delivery: findLinkByLabel(['Library','Jobs','Mentorship','Courses']),
        planning_meal: findLinkByLabel(['MEAL Dashboard','Results Framework','Indicators','Deliverables','Workplans']),
        hr: findLinkByLabel(['Appraisals','Leave','Employees']),
        overview: findLinkByLabel(['Dashboard']),
        operations: findLinkByLabel(['Settings','Assets','Data Migrations'])
    };

    const existingHrefs=new Set(
        [...sidebar.querySelectorAll('a[href]')]
            .map(a=>(a.getAttribute('href')||'').replace(/\/+$/,''))
            .filter(Boolean)
    );

    const fragment=template.content.cloneNode(true);
    const generated=[...fragment.querySelectorAll('[data-eh-generated-grouped-link="true"]')];

    const byGroup={};

    generated.forEach(link=>{
        const href=(link.getAttribute('href')||'').replace(/\/+$/,'');
        if(!href || existingHrefs.has(href)) return;

        const group=link.dataset.ehSidebarGroup;
        (byGroup[group] ||= []).push(link);
        existingHrefs.add(href);
    });

    Object.entries(byGroup).forEach(([group,links])=>{
        let anchor=groupAnchors[group];

        if(!anchor || !sidebar.contains(anchor)){
            return;
        }

        // Insert into the SAME parent/container as the native link.
        const parent=anchor.parentElement;

        links.forEach(link=>{
            if(anchor.parentElement===parent){
                anchor.insertAdjacentElement('afterend',link);
                anchor=link;
            }
        });
    });

    // Final de-duplication across the one real sidebar.
    const seen=new Set();

    sidebar.querySelectorAll('a[href]').forEach(link=>{
        const href=(link.getAttribute('href')||'').replace(/\/+$/,'');
        if(!href || href==='#' || href.startsWith('javascript:')) return;

        if(seen.has(href) && link.dataset.ehGeneratedGroupedLink==='true'){
            link.remove();
            return;
        }

        seen.add(href);
    });
});
