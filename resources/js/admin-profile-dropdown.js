document.addEventListener('DOMContentLoaded', () => {
    const menus=[...document.querySelectorAll('[data-profile-menu]')];

    if(!menus.length){
        return;
    }

    const closeMenu=(menu)=>{
        const trigger=menu.querySelector('[data-profile-menu-trigger]');
        const panel=menu.querySelector('[data-profile-menu-panel]');

        if(!trigger || !panel){
            return;
        }

        panel.hidden=true;
        trigger.setAttribute('aria-expanded','false');
        menu.classList.remove('is-open');
    };

    const openMenu=(menu)=>{
        menus.forEach((other)=>{
            if(other!==menu){
                closeMenu(other);
            }
        });

        const trigger=menu.querySelector('[data-profile-menu-trigger]');
        const panel=menu.querySelector('[data-profile-menu-panel]');

        if(!trigger || !panel){
            return;
        }

        panel.hidden=false;
        trigger.setAttribute('aria-expanded','true');
        menu.classList.add('is-open');
    };

    menus.forEach((menu)=>{
        const trigger=menu.querySelector('[data-profile-menu-trigger]');
        const panel=menu.querySelector('[data-profile-menu-panel]');

        if(!trigger || !panel){
            return;
        }

        trigger.addEventListener('click',(event)=>{
            event.preventDefault();
            event.stopPropagation();

            if(panel.hidden){
                openMenu(menu);
            }else{
                closeMenu(menu);
            }
        });

        panel.addEventListener('click',(event)=>{
            event.stopPropagation();
        });
    });

    document.addEventListener('click',()=>{
        menus.forEach(closeMenu);
    });

    document.addEventListener('keydown',(event)=>{
        if(event.key==='Escape'){
            menus.forEach(closeMenu);
        }
    });
});
