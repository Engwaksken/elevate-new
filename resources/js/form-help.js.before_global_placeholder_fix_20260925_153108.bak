document.addEventListener('DOMContentLoaded', () => {
    const optionalPatterns = [
        /^optional\./i,
        /select the appropriate .* from the available options/i,
        /enter the search for this record/i,
        /choose the relevant from/i,
        /choose the relevant to/i,
        /select the appropriate per page/i,
        /select the appropriate status/i,
        /select the appropriate employment type/i,
        /select the appropriate user type/i,
    ];

    const shouldRemove = (text) => {
        const value=(text || '').trim().replace(/\s+/g,' ');
        return optionalPatterns.some(pattern=>pattern.test(value));
    };

    const clean = (root=document) => {
        root.querySelectorAll(
            '.form-hint,.field-hint,.form-text,.help-text,[data-form-help-generated],small'
        ).forEach((node) => {
            if (shouldRemove(node.textContent)) {
                const id=node.id;
                node.remove();

                if(id){
                    document.querySelectorAll(`[aria-describedby~="${CSS.escape(id)}"]`)
                        .forEach((field)=>{
                            const current=(field.getAttribute('aria-describedby') || '')
                                .split(/\s+/)
                                .filter(Boolean)
                                .filter(token=>token!==id);

                            if(current.length){
                                field.setAttribute('aria-describedby',current.join(' '));
                            }else{
                                field.removeAttribute('aria-describedby');
                            }
                        });
                }
            }
        });
    };

    clean();

    // Clean helpers added later by modals/dynamic forms.
    const observer=new MutationObserver((mutations)=>{
        for(const mutation of mutations){
            for(const node of mutation.addedNodes){
                if(node.nodeType!==Node.ELEMENT_NODE) continue;

                if(shouldRemove(node.textContent) && node.matches?.(
                    '.form-hint,.field-hint,.form-text,.help-text,[data-form-help-generated],small'
                )){
                    node.remove();
                    continue;
                }

                clean(node);
            }
        }
    });

    observer.observe(document.body,{
        childList:true,
        subtree:true
    });
});
