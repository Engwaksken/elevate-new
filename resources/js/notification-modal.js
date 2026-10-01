// Notification list: keyboard access for clickable rows and mark-as-read when a detail modal opens.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.notification-item[data-modal-open]').forEach(item => {
        item.addEventListener('keydown', e => {
            if (e.target !== item || (e.key !== 'Enter' && e.key !== ' ')) return;
            e.preventDefault();
            item.click();
        });
    });

    document.querySelectorAll('.eh-modal[data-notification-read-url]').forEach(modal => {
        const markRead = () => {
            const url = modal.dataset.notificationReadUrl;
            if (!url) return;
            delete modal.dataset.notificationReadUrl;

            const body = new FormData();
            body.append('_token', modal.dataset.notificationToken || '');
            body.append('_method', 'PATCH');

            fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(response => {
                if (!response.ok) return;
                const item = document.getElementById(modal.dataset.notificationItem || '');
                item?.classList.replace('unread', 'read');
                const chip = item?.querySelector('[data-notification-status]');
                if (chip) {
                    chip.classList.replace('pending', 'active');
                    chip.textContent = 'Read';
                }
            }).catch(() => {});
        };

        new MutationObserver(() => {
            if (modal.classList.contains('is-open')) markRead();
        }).observe(modal, { attributes: true, attributeFilter: ['class'] });
    });
});
