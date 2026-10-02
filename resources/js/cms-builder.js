document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('[data-cms-builder]');
    if (!builder) return;

    const blocksEl = builder.querySelector('[data-cms-blocks]');
    const emptyEl = builder.querySelector('[data-cms-empty]');

    const reindex = () => {
        const blocks = [...blocksEl.querySelectorAll('[data-block]')];
        blocks.forEach((block, i) => {
            const typeField = block.querySelector('[data-block-type-field]');
            if (typeField) typeField.name = `sections[${i}][type]`;
            block.querySelectorAll('[data-block-field]').forEach((el) => {
                el.name = `sections[${i}][${el.dataset.blockField}]`;
            });
            block.querySelectorAll('[data-item]').forEach((item, j) => {
                item.querySelectorAll('[data-item-field]').forEach((el) => {
                    el.name = `sections[${i}][items][${j}][${el.dataset.itemField}]`;
                });
            });
        });
        if (emptyEl) emptyEl.hidden = blocks.length > 0;
    };

    const addBlock = (type) => {
        const tpl = builder.querySelector(`[data-block-template="${type}"]`);
        if (!tpl) return;
        blocksEl.appendChild(tpl.content.firstElementChild.cloneNode(true));
        reindex();
    };

    const addItem = (block, type) => {
        const tpl = builder.querySelector(`[data-item-template="${type}"]`);
        const items = block.querySelector('[data-items]');
        if (!tpl || !items) return;
        items.appendChild(tpl.content.firstElementChild.cloneNode(true));
        reindex();
    };

    builder.querySelectorAll('[data-add-block]').forEach((btn) => {
        btn.addEventListener('click', () => addBlock(btn.dataset.addBlock));
    });

    blocksEl.addEventListener('click', (event) => {
        const removeBlock = event.target.closest('[data-block-remove]');
        if (removeBlock) {
            removeBlock.closest('[data-block]').remove();
            reindex();
            return;
        }

        const move = event.target.closest('[data-block-move]');
        if (move) {
            const block = move.closest('[data-block]');
            const dir = move.dataset.blockMove;
            if (dir === 'up' && block.previousElementSibling) {
                blocksEl.insertBefore(block, block.previousElementSibling);
            } else if (dir === 'down' && block.nextElementSibling) {
                blocksEl.insertBefore(block.nextElementSibling, block);
            }
            reindex();
            return;
        }

        const addItemBtn = event.target.closest('[data-add-item]');
        if (addItemBtn) {
            addItem(addItemBtn.closest('[data-block]'), addItemBtn.dataset.addItem);
            return;
        }

        const removeItem = event.target.closest('[data-item-remove]');
        if (removeItem) {
            removeItem.closest('[data-item]').remove();
            reindex();
        }
    });

    reindex();
});
