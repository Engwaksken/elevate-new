document.addEventListener('DOMContentLoaded',()=>{
    const root=document.getElementById('survey-sortable');
    if(!root) return;

    let dragged=null;

    root.querySelectorAll('.eh-sortable-question').forEach(item=>{
        item.addEventListener('dragstart',()=>{
            dragged=item;
            item.classList.add('is-dragging');
        });

        item.addEventListener('dragend',()=>{
            item.classList.remove('is-dragging');
            dragged=null;
            saveOrder();
        });

        item.addEventListener('dragover',(event)=>{
            event.preventDefault();
            if(!dragged || dragged===item) return;

            const box=item.getBoundingClientRect();
            const after=event.clientY > box.top + box.height/2;

            if(after){
                item.after(dragged);
            }else{
                item.before(dragged);
            }
        });
    });

    async function saveOrder(){
        const url=root.dataset.reorderUrl;
        const ids=[...root.querySelectorAll('.eh-sortable-question')]
            .map(item=>Number(item.dataset.questionId));
        const status=document.getElementById('survey-order-status');
        if(status) status.textContent='Saving order…';

        try{
            const token=document.querySelector('meta[name="csrf-token"]')?.content;
            const response=await fetch(url,{
                method:'PUT',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN':token
                },
                body:JSON.stringify({question_ids:ids})
            });

            if(!response.ok) throw new Error('Unable to save question order.');

            if(status) status.textContent='Order saved';
        }catch(error){
            if(status) status.textContent='Order could not be saved';
        }
    }
});
