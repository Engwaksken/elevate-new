{{-- Behaviour and styles for <x-form-tabs>. Inline so it works without a frontend rebuild;
     the base look comes from the existing .eh-tab-* classes (participant-tabs.css). --}}
<style>
    .form-tabs { margin-bottom: 20px; }
    .form-tabs__nav { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scroll-behavior: smooth; }
    .form-tabs__panel[hidden] { display: none !important; }
    .form-tabs__panel.active { display: block; }
    .form-tabs__tab:focus-visible { outline: 2px solid #800000; outline-offset: -2px; border-radius: 6px; }
    .form-tabs__error-dot { display: none; width: 8px; height: 8px; border-radius: 50%; background: #b42318; flex: 0 0 auto; }
    .form-tabs__tab.has-error, .form-tabs__tab.has-error i { color: #b42318; }
    .form-tabs__tab.has-error .form-tabs__error-dot { display: inline-block; }
    .form-tabs__sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    .eh-modal-body .form-tabs { margin-bottom: 0; }
    .eh-modal-body .form-tabs .eh-tab-content { padding-top: 18px; }
    @media (max-width: 575px) { .form-tabs__tab { min-height: 44px; padding: 9px 12px; } }
</style>
<script>
(function () {
    var ERROR_SELECTOR = '[aria-invalid="true"], .is-invalid, .has-error, .field-error, .invalid-feedback, .error-text';

    function setup(root) {
        if (root.dataset.formTabsReady) return;
        root.dataset.formTabsReady = '1';

        var nav = root.querySelector('[role="tablist"]');
        var tabs = Array.prototype.slice.call(nav.querySelectorAll('[role="tab"]'));
        var panelFor = function (tab) { return document.getElementById(tab.getAttribute('aria-controls')); };
        var tabFor = function (panel) {
            for (var i = 0; i < tabs.length; i++) { if (panelFor(tabs[i]) === panel) return tabs[i]; }
            return null;
        };

        function activate(tab, opts) {
            opts = opts || {};
            tabs.forEach(function (t) {
                var on = t === tab;
                var panel = panelFor(t);
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                if (panel) { panel.hidden = !on; panel.classList.toggle('active', on); }
            });
            // Keep the active tab visible when the tab strip scrolls horizontally (mobile).
            var left = tab.offsetLeft - nav.offsetLeft;
            if (left < nav.scrollLeft || left + tab.offsetWidth > nav.scrollLeft + nav.clientWidth) {
                nav.scrollLeft = Math.max(0, left - 16);
            }
            if (opts.focus) tab.focus();
            if (opts.remember && window.history && history.replaceState) {
                history.replaceState(history.state, '', '#' + tab.getAttribute('aria-controls'));
            }
        }

        function markError(tab, on) {
            tab.classList.toggle('has-error', on);
            var sr = tab.querySelector('[data-form-tab-error-text]');
            if (sr) sr.textContent = on ? '(has errors)' : '';
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activate(tab, { remember: true }); });
            tab.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = tabs[(index + 1) % tabs.length];
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = tabs[(index - 1 + tabs.length) % tabs.length];
                else if (e.key === 'Home') next = tabs[0];
                else if (e.key === 'End') next = tabs[tabs.length - 1];
                if (next) { e.preventDefault(); activate(next, { focus: true, remember: true }); }
            });
        });

        // Initial tab: a server-side validation error wins, then error markup, then the URL hash.
        var initial = null;
        if (root.dataset.errorTab) {
            initial = tabs.filter(function (t) { return t.dataset.formTab === root.dataset.errorTab; })[0] || null;
        }
        if (!initial) {
            tabs.some(function (t) {
                var p = panelFor(t);
                if (p && p.querySelector(ERROR_SELECTOR)) { markError(t, true); initial = initial || t; }
                return false;
            });
        }
        if (!initial && location.hash) {
            initial = tabs.filter(function (t) { return '#' + t.getAttribute('aria-controls') === location.hash; })[0] || null;
        }
        if (initial) activate(initial);

        window.addEventListener('hashchange', function () {
            var t = tabs.filter(function (t) { return '#' + t.getAttribute('aria-controls') === location.hash; })[0];
            if (t) activate(t);
        });

        // Browser validation on submit: reveal the tab holding the first invalid field.
        var form = root.closest('form');
        if (!form) return;
        var revealed = false;
        form.addEventListener('invalid', function (e) {
            var field = e.target;
            var panel = field.closest && field.closest('[role="tabpanel"]');
            if (!panel || !root.contains(panel)) return;
            var tab = tabFor(panel);
            if (!tab) return;
            markError(tab, true);
            if (revealed) return;
            revealed = true;
            setTimeout(function () { revealed = false; }, 300);
            if (panel.hidden) {
                activate(tab);
                setTimeout(function () {
                    if (document.activeElement !== field) {
                        field.focus();
                        if (field.reportValidity) field.reportValidity();
                    }
                }, 0);
            }
        }, true);

        var recheck = function () {
            tabs.forEach(function (t) {
                if (!t.classList.contains('has-error')) return;
                var p = panelFor(t);
                if (p && !p.querySelector(':invalid') && !p.querySelector(ERROR_SELECTOR) && !root.dataset.errorTab) markError(t, false);
            });
        };
        form.addEventListener('input', recheck);
        form.addEventListener('change', recheck);
    }

    function init() {
        document.querySelectorAll('[data-form-tabs]').forEach(setup);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    window.EhFormTabs = { init: init };
})();
</script>
