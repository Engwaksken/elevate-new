document.addEventListener('DOMContentLoaded', () => {
    const topbar =
        document.querySelector('[data-admin-topbar="true"]') ||
        document.querySelector('.eh-admin-topbar');

    if (!topbar) return;

    // Remove the exact helper text reported by the user.
    const badTexts = [
        'Optional. Enter the q for this record.',
        'Optional. Enter the q for this record',
    ];

    [...topbar.querySelectorAll('*')].forEach(el => {
        if (el.children.length > 0) return;

        const text = (el.textContent || '').trim();

        if (badTexts.includes(text)) {
            el.remove();
        }
    });

    // Also remove helper text attached by aria-describedby to the search input.
    const searchInput =
        topbar.querySelector('input[type="search"]') ||
        topbar.querySelector('input[name="q"]');

    if (searchInput) {
        const describedBy = searchInput.getAttribute('aria-describedby');

        if (describedBy) {
            describedBy.split(/\s+/).forEach(id => {
                const helper = document.getElementById(id);

                if (
                    helper &&
                    (helper.textContent || '').trim().toLowerCase().includes('enter the q for this record')
                ) {
                    helper.remove();
                }
            });
        }

        searchInput.setAttribute(
            'placeholder',
            searchInput.getAttribute('placeholder') || 'Search users, courses, events...'
        );
    }
});
