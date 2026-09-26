const EH_A11Y_KEY='eh360_accessibility_preferences';

const defaultPrefs={
    textScale:1,
    contrast:false,
    greyscale:false,
    dyslexia:false,
    underline:false,
    reducedMotion:false,
    largeCursor:false,
    focusHighlight:false,
    readingGuide:false,
};

function loadPrefs(){
    try{
        return {...defaultPrefs,...JSON.parse(localStorage.getItem(EH_A11Y_KEY)||'{}')};
    }catch(e){
        return {...defaultPrefs};
    }
}

function savePrefs(prefs){
    localStorage.setItem(EH_A11Y_KEY,JSON.stringify(prefs));
}

function applyPrefs(prefs){
    const root=document.documentElement;
    root.style.setProperty('--eh-user-text-scale',String(prefs.textScale));
    root.classList.toggle('eh-a11y-contrast',Boolean(prefs.contrast));
    root.classList.toggle('eh-a11y-greyscale',Boolean(prefs.greyscale));
    root.classList.toggle('eh-a11y-dyslexia',Boolean(prefs.dyslexia));
    root.classList.toggle('eh-a11y-underline',Boolean(prefs.underline));
    root.classList.toggle('eh-a11y-reduced-motion',Boolean(prefs.reducedMotion));
    root.classList.toggle('eh-a11y-large-cursor',Boolean(prefs.largeCursor));
    root.classList.toggle('eh-a11y-focus',Boolean(prefs.focusHighlight));
    root.classList.toggle('eh-a11y-reading-guide',Boolean(prefs.readingGuide));

    const guide=document.querySelector('[data-reading-guide]');
    if(guide){
        guide.hidden=!prefs.readingGuide;
    }

    document.querySelectorAll('[data-a11y-action]').forEach(button=>{
        const action=button.dataset.a11yAction;
        const active=
            (action==='contrast' && prefs.contrast)
            || (action==='greyscale' && prefs.greyscale)
            || (action==='dyslexia' && prefs.dyslexia)
            || (action==='underline' && prefs.underline)
            || (action==='motion' && prefs.reducedMotion)
            || (action==='cursor' && prefs.largeCursor)
            || (action==='focus' && prefs.focusHighlight)
            || (action==='guide' && prefs.readingGuide);

        button.classList.toggle('is-active',Boolean(active));
        if(['contrast','greyscale','dyslexia','underline','motion','cursor','focus','guide'].includes(action)){
            button.setAttribute('aria-pressed',active?'true':'false');
        }
    });
}

function setPanel(name,open){
    const map={
        accessibility:document.getElementById('ehAccessibilityPanel'),
        chatbot:document.getElementById('ehChatbotPanel'),
    };

    const panel=map[name];
    if(!panel) return;

    Object.entries(map).forEach(([key,item])=>{
        if(!item) return;
        const shouldOpen=key===name ? open : false;
        item.hidden=!shouldOpen;
        document.querySelector(`[data-eh-toggle="${key}"]`)?.setAttribute('aria-expanded',shouldOpen?'true':'false');
    });

    if(open){
        window.setTimeout(()=>{
            panel.querySelector('button,textarea,input,select,a')?.focus();
        },20);
    }
}

function readMainContent(){
    if(!('speechSynthesis' in window)){
        alert('Read-aloud is not supported by this browser.');
        return;
    }

    window.speechSynthesis.cancel();

    const selection=window.getSelection()?.toString().trim();
    const source=selection || document.querySelector('#main-content, main')?.innerText || document.body.innerText;
    const text=source.replace(/\s+/g,' ').trim().slice(0,12000);

    if(!text) return;

    const utterance=new SpeechSynthesisUtterance(text);
    utterance.lang=document.documentElement.lang || 'en';
    utterance.rate=.95;
    window.speechSynthesis.speak(utterance);
}

function addChatMessage(role,text,links=[]){
    const container=document.querySelector('[data-chat-messages]');
    if(!container) return;

    const wrapper=document.createElement('div');
    wrapper.className=`eh-chat-message ${role}`;

    const bubble=document.createElement('div');
    bubble.className='eh-chat-bubble';
    bubble.textContent=text;
    wrapper.appendChild(bubble);

    if(Array.isArray(links) && links.length){
        const linksWrap=document.createElement('div');
        linksWrap.className='eh-chat-links';

        links.forEach(link=>{
            const a=document.createElement('a');
            a.href=link.url;
            a.textContent=link.label;
            linksWrap.appendChild(a);
        });

        bubble.appendChild(linksWrap);
    }

    container.appendChild(wrapper);
    container.scrollTop=container.scrollHeight;
}

function setChatBusy(busy){
    const form=document.querySelector('[data-chat-form]');
    if(!form) return;

    form.querySelector('textarea')?.toggleAttribute('disabled',busy);
    form.querySelector('button[type="submit"]')?.toggleAttribute('disabled',busy);
    form.classList.toggle('is-busy',busy);
}

document.addEventListener('DOMContentLoaded',()=>{
    const widget=document.querySelector('[data-eh-assistive-tools]');
    if(!widget) return;

    let prefs=loadPrefs();
    applyPrefs(prefs);

    document.querySelectorAll('[data-eh-toggle]').forEach(button=>{
        button.addEventListener('click',()=>{
            const name=button.dataset.ehToggle;
            const panel=document.getElementById(name==='accessibility'?'ehAccessibilityPanel':'ehChatbotPanel');
            setPanel(name,panel?.hidden ?? true);
        });
    });

    document.querySelectorAll('[data-eh-close]').forEach(button=>{
        button.addEventListener('click',()=>setPanel(button.dataset.ehClose,false));
    });

    document.querySelectorAll('[data-a11y-action]').forEach(button=>{
        button.addEventListener('click',()=>{
            const action=button.dataset.a11yAction;

            if(action==='text-increase'){
                prefs.textScale=Math.min(1.5,Math.round((prefs.textScale+.1)*10)/10);
            }else if(action==='text-decrease'){
                prefs.textScale=Math.max(.8,Math.round((prefs.textScale-.1)*10)/10);
            }else if(action==='contrast'){
                prefs.contrast=!prefs.contrast;
            }else if(action==='greyscale'){
                prefs.greyscale=!prefs.greyscale;
            }else if(action==='dyslexia'){
                prefs.dyslexia=!prefs.dyslexia;
            }else if(action==='underline'){
                prefs.underline=!prefs.underline;
            }else if(action==='motion'){
                prefs.reducedMotion=!prefs.reducedMotion;
            }else if(action==='cursor'){
                prefs.largeCursor=!prefs.largeCursor;
            }else if(action==='focus'){
                prefs.focusHighlight=!prefs.focusHighlight;
            }else if(action==='guide'){
                prefs.readingGuide=!prefs.readingGuide;
            }else if(action==='read'){
                readMainContent();
                return;
            }else if(action==='stop-read'){
                window.speechSynthesis?.cancel();
                return;
            }else if(action==='reset'){
                prefs={...defaultPrefs};
                window.speechSynthesis?.cancel();
            }

            savePrefs(prefs);
            applyPrefs(prefs);
        });
    });

    document.addEventListener('mousemove',event=>{
        const guide=document.querySelector('[data-reading-guide]');
        if(!guide || guide.hidden || !prefs.readingGuide) return;
        guide.style.top=`${event.clientY-18}px`;
    });

    document.querySelectorAll('[data-chat-prompt]').forEach(button=>{
        button.addEventListener('click',()=>{
            const textarea=document.getElementById('ehChatMessage');
            if(!textarea) return;
            textarea.value=button.dataset.chatPrompt || '';
            textarea.focus();
        });
    });

    document.querySelector('[data-chat-form]')?.addEventListener('submit',async event=>{
        event.preventDefault();

        const form=event.currentTarget;
        const textarea=form.querySelector('textarea[name="message"]');
        const message=textarea?.value.trim();

        if(!message) return;

        addChatMessage('user',message);
        textarea.value='';
        setChatBusy(true);

        try{
            const response=await fetch(form.action,{
                method:'POST',
                headers:{
                    'Accept':'application/json',
                    'X-Requested-With':'XMLHttpRequest',
                },
                body:new FormData(form),
            });

            const data=await response.json();

            if(!response.ok){
                throw new Error(data?.message || 'Unable to send message.');
            }

            addChatMessage('bot',data.message || 'I could not find an answer for that.',data.links || []);
        }catch(error){
            addChatMessage('bot','The assistant is temporarily unavailable. You can still use the Accessibility button and the main navigation.');
        }finally{
            setChatBusy(false);
            textarea?.focus();
        }
    });

    document.addEventListener('keydown',event=>{
        if(event.key!=='Escape') return;
        setPanel('accessibility',false);
        setPanel('chatbot',false);
    });
});
