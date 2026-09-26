document.addEventListener('DOMContentLoaded', () => {
    const workflow=document.querySelector('.appraisal-kra-workflow');
    if(!workflow) return;

    const tabs=[...workflow.querySelectorAll('[data-kra-tab]')];
    const panels=[...workflow.querySelectorAll('[data-kra-panel]')];

    const activate=(key)=>{
        tabs.forEach(tab=>{
            const active=tab.dataset.kraTab===key;
            tab.classList.toggle('active',active);
            tab.setAttribute('aria-selected',active?'true':'false');
        });

        panels.forEach(panel=>{
            panel.classList.toggle('active',panel.dataset.kraPanel===key);
        });

        const tab=tabs.find(item=>item.dataset.kraTab===key);
        if(tab){
            try{
                sessionStorage.setItem('eh360_staff_appraisal_kra',key);
            }catch(e){}
            tab.scrollIntoView({behavior:'smooth',block:'nearest',inline:'nearest'});
        }
    };

    tabs.forEach(tab=>{
        tab.addEventListener('click',()=>activate(tab.dataset.kraTab));
    });

    const currentIndex=()=>{
        return panels.findIndex(panel=>panel.classList.contains('active'));
    };

    workflow.querySelectorAll('[data-kra-next]').forEach(button=>{
        button.addEventListener('click',()=>{
            const index=currentIndex();
            if(index>=0 && index<panels.length-1){
                activate(panels[index+1].dataset.kraPanel);
                window.scrollTo({top:Math.max(0,workflow.offsetTop-90),behavior:'smooth'});
            }
        });
    });

    workflow.querySelectorAll('[data-kra-prev]').forEach(button=>{
        button.addEventListener('click',()=>{
            const index=currentIndex();
            if(index>0){
                activate(panels[index-1].dataset.kraPanel);
                window.scrollTo({top:Math.max(0,workflow.offsetTop-90),behavior:'smooth'});
            }
        });
    });

    try{
        const saved=sessionStorage.getItem('eh360_staff_appraisal_kra');
        if(saved && tabs.some(tab=>tab.dataset.kraTab===saved)){
            activate(saved);
        }
    }catch(e){}
});
