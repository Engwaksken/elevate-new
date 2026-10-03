document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('[data-cms-builder]');
    if (!builder) return;

    const blocksEl = builder.querySelector('[data-cms-blocks]');
    const emptyEl = builder.querySelector('[data-cms-empty]');

    const reindexNested = (block, i) => {
        block.querySelectorAll('[data-column]').forEach((column, c) => {
            const width = column.querySelector('[data-column-width]');
            if (width) width.name = `sections[${i}][columns][${c}][width]`;
            column.querySelectorAll('[data-widget]').forEach((widget, w) => {
                const typeField = widget.querySelector('[data-widget-type-field]');
                if (typeField) typeField.name = `sections[${i}][columns][${c}][widgets][${w}][type]`;
                widget.querySelectorAll('[data-widget-field]').forEach((el) => {
                    el.name = `sections[${i}][columns][${c}][widgets][${w}][${el.dataset.widgetField}]`;
                });
            });
        });
    };

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
            reindexNested(block, i);
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

    const addColumn = (block) => {
        const tpl = builder.querySelector('[data-column-template]');
        const columns = block.querySelector('[data-columns]');
        if (!tpl || !columns) return;
        columns.appendChild(tpl.content.firstElementChild.cloneNode(true));
        reindex();
    };

    const addWidget = (column, type) => {
        const tpl = builder.querySelector(`[data-widget-template="${type}"]`);
        const widgets = column.querySelector('[data-widgets]');
        if (!tpl || !widgets) return;
        widgets.appendChild(tpl.content.firstElementChild.cloneNode(true));
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
            return;
        }

        const addColumnBtn = event.target.closest('[data-add-column]');
        if (addColumnBtn) {
            addColumn(addColumnBtn.closest('[data-block]'));
            return;
        }

        const addWidgetBtn = event.target.closest('[data-add-widget]');
        if (addWidgetBtn) {
            addWidget(addWidgetBtn.closest('[data-column]'), addWidgetBtn.dataset.addWidget);
            return;
        }

        const removeColumn = event.target.closest('[data-column-remove]');
        if (removeColumn) {
            removeColumn.closest('[data-column]').remove();
            reindex();
            return;
        }

        const moveColumn = event.target.closest('[data-column-move]');
        if (moveColumn) {
            const column = moveColumn.closest('[data-column]');
            const columns = column.parentElement;
            const dir = moveColumn.dataset.columnMove;
            if (dir === 'up' && column.previousElementSibling) {
                columns.insertBefore(column, column.previousElementSibling);
            } else if (dir === 'down' && column.nextElementSibling) {
                columns.insertBefore(column.nextElementSibling, column);
            }
            reindex();
            return;
        }

        const removeWidget = event.target.closest('[data-widget-remove]');
        if (removeWidget) {
            removeWidget.closest('[data-widget]').remove();
            reindex();
            return;
        }

        const moveWidget = event.target.closest('[data-widget-move]');
        if (moveWidget) {
            const widget = moveWidget.closest('[data-widget]');
            const widgets = widget.parentElement;
            const dir = moveWidget.dataset.widgetMove;
            if (dir === 'up' && widget.previousElementSibling) {
                widgets.insertBefore(widget, widget.previousElementSibling);
            } else if (dir === 'down' && widget.nextElementSibling) {
                widgets.insertBefore(widget.nextElementSibling, widget);
            }
            reindex();
            return;
        }
    });

    reindex();
});
