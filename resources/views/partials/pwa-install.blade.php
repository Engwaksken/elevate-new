<div class="eh-pwa-status" data-pwa-shell>
    <div class="eh-pwa-status__connection">
        <span class="eh-pwa-dot"></span>
        <strong data-pwa-status>Checking connection…</strong>
        <small data-pwa-last-sync>Not synced yet</small>
    </div>

    <div class="eh-pwa-status__actions">
        <button type="button" class="btn btn-outline btn-sm" data-pwa-sync-retry>
            <i class="fas fa-arrows-rotate"></i>
            Retry Sync
        </button>

        <button type="button" class="btn btn-primary btn-sm" data-pwa-install hidden>
            <i class="fas fa-download"></i>
            Install ElevateHer360
        </button>
    </div>
</div>

<div class="eh-pwa-update" data-pwa-update hidden>
    <span>
        <i class="fas fa-circle-up"></i>
        A new ElevateHer360 update is available.
    </span>
    <button type="button" class="btn btn-primary btn-sm" data-pwa-update-now>
        Update now
    </button>
</div>

<style>
.eh-pwa-status{
    position:fixed;right:18px;bottom:18px;z-index:9000;
    display:flex;align-items:center;gap:14px;max-width:min(620px,calc(100vw - 36px));
    background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:10px 12px;
    box-shadow:0 12px 32px rgba(16,24,40,.15)
}
.eh-pwa-status__connection{display:grid;grid-template-columns:auto 1fr;column-gap:8px;align-items:center;min-width:180px}
.eh-pwa-status__connection small{grid-column:2;color:#667085}
.eh-pwa-dot{width:9px;height:9px;border-radius:50%;background:#98a2b3}
[data-pwa-status][data-state="online"]~small{}
.eh-pwa-status__actions{display:flex;gap:8px;flex-wrap:wrap}
.eh-pwa-update{
    position:fixed;left:50%;transform:translateX(-50%);bottom:18px;z-index:9100;
    background:#101828;color:#fff;border-radius:12px;padding:12px 14px;
    display:flex;align-items:center;gap:14px;box-shadow:0 14px 36px rgba(16,24,40,.25)
}
.eh-pwa-update[hidden],.eh-pwa-status [hidden]{display:none!important}
@media(max-width:720px){
    .eh-pwa-status{left:10px;right:10px;bottom:10px;max-width:none;align-items:flex-start;flex-direction:column}
    .eh-pwa-update{left:10px;right:10px;bottom:10px;transform:none}
}
</style>
