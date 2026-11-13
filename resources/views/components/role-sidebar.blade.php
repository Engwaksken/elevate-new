@props(['role' => 'participant'])
@php
    $rsUser = auth()->user();
    $rsMeta = \App\Support\RoleShell::meta($role);
    $rsHome = \App\Support\RoleShell::url($rsMeta['home']) ?? route('home');
    $rsNav = \App\Support\RoleShell::navigation($role);
    $rsLogoPath = app(\App\Services\SettingsService::class)->get('branding.logo_path');
    $rsLogoUrl = $rsLogoPath && Route::has('branding.asset')
        ? route('branding.asset', ['type' => 'logo', 'v' => md5((string) $rsLogoPath)])
        : null;
@endphp
<aside id="participantSidebar" class="participant-sidebar role-sidebar role-sidebar--{{ $role }}" aria-label="{{ $rsMeta['label'] }} navigation">
    <div class="ps-brand">
        <a href="{{ $rsHome }}" class="ps-brand-link">
            @if($rsLogoUrl)
                <img src="{{ $rsLogoUrl }}" alt="ElevateHer360" class="ps-mark-logo">
            @else
                <span class="ps-mark">E360</span>
            @endif
            <span class="ps-brand-copy">
                <strong>ElevateHer360</strong>
                <small>Women in Technology Uganda</small>
            </span>
        </a>
        <button type="button" class="ps-close" data-sidebar-close aria-label="Close navigation">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <div class="ps-user">
        <span class="ps-avatar" aria-hidden="true">{{ strtoupper(substr($rsUser->name ?? $rsUser->email ?? 'U', 0, 1)) }}</span>
        <span class="ps-user-copy">
            <strong>{{ $rsUser->name ?? $rsMeta['label'] }}</strong>
            <small>{{ $rsUser->email }}</small>
            <x-role-badge :role="$role" tone="dark" class="ps-user-role" />
        </span>
    </div>

    <nav class="ps-nav" aria-label="{{ $rsMeta['label'] }} menu">
        @php
            $rsOpenGroup = array_key_first($rsNav);
            foreach ($rsNav as $rsGroupName => $rsGroupItems) {
                if (collect($rsGroupItems)->contains(fn (array $item) => $item['is_active'])) {
                    $rsOpenGroup = $rsGroupName;
                    break;
                }
            }
        @endphp
        @foreach($rsNav as $group => $items)
            @php
                $rsGroupId = 'ps-group-'.\Illuminate\Support\Str::slug($group);
                $rsGroupOpen = $group === $rsOpenGroup;
            @endphp
            <div class="ps-group {{ $rsGroupOpen ? 'is-open' : '' }}" data-ps-group>
                <button type="button" class="ps-label ps-group-toggle" aria-expanded="{{ $rsGroupOpen ? 'true' : 'false' }}" aria-controls="{{ $rsGroupId }}" data-ps-group-toggle>
                    <span>{{ $group }}</span>
                    <i class="fas fa-chevron-down ps-group-chevron" aria-hidden="true"></i>
                </button>
                <div id="{{ $rsGroupId }}" class="ps-group-links" @if(! $rsGroupOpen) hidden @endif>
                    @foreach($items as $item)
                        <a href="{{ $item['url'] }}" class="ps-link {{ $item['is_active'] ? 'active' : '' }}" data-ps-tip="{{ $item['label'] }}" @if($item['is_active']) aria-current="page" @endif>
                            <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="ps-footer">
        <button type="button" class="ps-collapse" data-ps-collapse aria-controls="participantSidebar" aria-pressed="false" data-ps-tip="Expand menu">
            <i class="fas fa-angles-left" aria-hidden="true"></i>
            <span>Collapse menu</span>
        </button>
        <form method="POST" action="{{ route($role !== 'participant' && Route::has('partners.logout') ? 'partners.logout' : 'logout') }}" class="ps-logout-form">
            @csrf
            <button type="submit" class="ps-logout" data-ps-tip="Sign out">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
<div class="ps-tip" data-ps-tip-bubble role="tooltip" hidden></div>
<script>
/* Sidebar rail: desktop users can shrink the menu to icons (remembered per browser);
   tablets always show the icon rail and the hamburger opens the full menu over it. */
(function () {
    const KEY = 'eh-ps-sidebar-collapsed';
    const body = document.body;
    const button = document.querySelector('[data-ps-collapse]');
    const tip = document.querySelector('[data-ps-tip-bubble]');
    // In the compact rail every group's icons show; otherwise only the open group's links.
    const syncGroups = () => {
        const compact = body.classList.contains('ps-rail') && !body.classList.contains('sidebar-open');
        document.querySelectorAll('#participantSidebar [data-ps-group]').forEach(group => {
            const panel = group.querySelector('.ps-group-links');
            if (panel) panel.hidden = !compact && !group.classList.contains('is-open');
        });
    };
    new MutationObserver(syncGroups).observe(body, {attributes: true, attributeFilter: ['class']});
    const read = () => { try { return localStorage.getItem(KEY) === 'true'; } catch (e) { return false; } };
    const apply = () => {
        const w = window.innerWidth;
        const pref = read();
        body.classList.toggle('ps-rail', (w >= 992 && pref) || (w >= 768 && w < 992));
        if (w >= 992) body.classList.remove('sidebar-open');
        syncGroups();
        if (button) {
            button.setAttribute('aria-pressed', String(pref));
            button.setAttribute('aria-label', pref ? 'Expand menu' : 'Collapse menu');
            button.querySelector('i').className = 'fas ' + (pref ? 'fa-angles-right' : 'fa-angles-left');
            button.querySelector('span').textContent = pref ? 'Expand menu' : 'Collapse menu';
        }
    };
    button?.addEventListener('click', () => {
        try { localStorage.setItem(KEY, String(!read())); } catch (e) { body.classList.toggle('ps-rail'); return; }
        tip.hidden = true;
        apply();
    });
    window.addEventListener('resize', apply);
    apply();

    // Icon-only rail: show the item's name beside it on hover/focus.
    const show = (el) => {
        if (!body.classList.contains('ps-rail') || body.classList.contains('sidebar-open') || !tip) return;
        const r = el.getBoundingClientRect();
        tip.textContent = el.dataset.psTip;
        tip.hidden = false;
        tip.style.top = (r.top + r.height / 2) + 'px';
        tip.style.left = (r.right + 10) + 'px';
    };
    const hide = () => { if (tip) tip.hidden = true; };
    document.querySelectorAll('#participantSidebar [data-ps-tip]').forEach(el => {
        el.addEventListener('mouseenter', () => show(el));
        el.addEventListener('focus', () => show(el));
        el.addEventListener('mouseleave', hide);
        el.addEventListener('blur', hide);
    });
    document.querySelector('#participantSidebar .ps-nav')?.addEventListener('scroll', hide, {passive: true});
})();
</script>
