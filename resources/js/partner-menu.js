// Header "Partners" menu (<details data-partner-menu>): close on outside
// click or Escape, and pin the phone sheet just below the sticky header.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-partner-menu]').forEach((menu) => {
        const summary = menu.querySelector('summary');

        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            const header = menu.closest('header');
            const top = header ? header.getBoundingClientRect().bottom : summary.getBoundingClientRect().bottom;
            menu.style.setProperty('--partner-menu-top', `${Math.round(top + 8)}px`);
        });

        document.addEventListener('click', (event) => {
            if (menu.open && !menu.contains(event.target)) menu.open = false;
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menu.open) {
                menu.open = false;
                summary.focus();
            }
        });
    });
});
