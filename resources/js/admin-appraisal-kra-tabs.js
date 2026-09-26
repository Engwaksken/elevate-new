document.addEventListener('DOMContentLoaded', () => {
    const workflow=document.querySelector('.appraisal-admin-kra-workflow');

    if(workflow){
        const tabs=[...workflow.querySelectorAll('[data-admin-kra-tab]')];
        const panels=[...workflow.querySelectorAll('[data-admin-kra-panel]')];

        const activate=(key)=>{
            tabs.forEach((tab)=>{
                const active=tab.dataset.adminKraTab===key;
                tab.classList.toggle('active',active);
                tab.setAttribute('aria-selected',active?'true':'false');
            });

            panels.forEach((panel)=>{
                panel.classList.toggle(
                    'active',
                    panel.dataset.adminKraPanel===key
                );
            });

            try{
                sessionStorage.setItem(
                    'eh360_admin_appraisal_kra',
                    key
                );
            }catch(e){}
        };

        tabs.forEach((tab)=>{
            tab.addEventListener('click',()=>{
                activate(tab.dataset.adminKraTab);
            });
        });

        const activeIndex=()=>panels.findIndex(
            panel=>panel.classList.contains('active')
        );

        workflow.querySelectorAll('[data-admin-kra-next]').forEach((button)=>{
            button.addEventListener('click',()=>{
                const index=activeIndex();

                if(index>=0 && index<panels.length-1){
                    activate(panels[index+1].dataset.adminKraPanel);

                    window.scrollTo({
                        top:Math.max(0,workflow.offsetTop-80),
                        behavior:'smooth'
                    });
                }
            });
        });

        workflow.querySelectorAll('[data-admin-kra-prev]').forEach((button)=>{
            button.addEventListener('click',()=>{
                const index=activeIndex();

                if(index>0){
                    activate(panels[index-1].dataset.adminKraPanel);

                    window.scrollTo({
                        top:Math.max(0,workflow.offsetTop-80),
                        behavior:'smooth'
                    });
                }
            });
        });

        try{
            const stored=sessionStorage.getItem(
                'eh360_admin_appraisal_kra'
            );

            if(
                stored &&
                tabs.some(tab=>tab.dataset.adminKraTab===stored)
            ){
                activate(stored);
            }
        }catch(e){}
    }

    /*
     * Remove only automatically generated Optional help messages.
     * Placeholders remain in the fields.
     */
    const unwanted=[
        'optional. select the appropriate user type from the available options.',
        'optional. enter the search for this record.'
    ];

    document.querySelectorAll(
        '[data-form-help-generated],.form-hint,.field-hint,small'
    ).forEach((node)=>{
        const text=(node.textContent || '')
            .trim()
            .replace(/\s+/g,' ')
            .toLowerCase();

        if(
            unwanted.includes(text) ||
            (
                node.hasAttribute('data-form-help-generated') &&
                text.startsWith('optional.')
            )
        ){
            node.remove();
        }
    });
});
