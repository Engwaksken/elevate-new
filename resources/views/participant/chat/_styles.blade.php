<style>
/* Participant chat: two-pane messenger layout (conversation list + thread). */
.ehc{--ehc-brand:#800000;--ehc-brand-soft:#f8eaea;--ehc-line:#e6e8ec;--ehc-muted:#667085;--ehc-ink:#172033;--ehc-bg:#f4f5f7;--ehc-radius:18px;
    display:grid;grid-template-columns:340px minmax(0,1fr);height:calc(100vh - 150px);min-height:540px;background:#fff;border:1px solid var(--ehc-line);border-radius:var(--ehc-radius);overflow:hidden;box-shadow:0 10px 30px #1018280f}
.ehc *{box-sizing:border-box}
.ehc button{font:inherit}

/* Sidebar */
.ehc-side{display:flex;flex-direction:column;min-height:0;border-right:1px solid var(--ehc-line);background:#fff}
.ehc-side-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:18px 18px 12px}
.ehc-side-head h1{margin:0;font-size:1.25rem;color:var(--ehc-ink)}
.ehc-icon-btn{display:inline-grid;place-items:center;width:38px;height:38px;border:0;border-radius:50%;background:transparent;color:var(--ehc-ink);cursor:pointer;flex:none}
.ehc-icon-btn:hover,.ehc-icon-btn:focus-visible{background:#f2f4f7}
.ehc-icon-btn.is-brand{background:var(--ehc-brand);color:#fff}
.ehc-icon-btn.is-brand:hover{background:#660000}
.ehc-search{position:relative;margin:0 18px 10px}
.ehc-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#98a2b3;font-size:.85rem}
.ehc-search input{width:100%;padding:10px 12px 10px 36px;border:1px solid transparent;border-radius:999px;background:#f2f4f7;font:inherit;font-size:.9rem}
.ehc-search input:focus{outline:none;border-color:var(--ehc-brand);background:#fff}
.ehc-list{flex:1;overflow-y:auto;padding:4px 8px 12px;margin:0;list-style:none}
.ehc-item{position:relative;display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;color:inherit;text-decoration:none}
.ehc-item:hover{background:#f9fafb}
.ehc-item.is-active{background:var(--ehc-brand-soft)}
.ehc-item.is-active::before{content:"";position:absolute;left:0;top:14px;bottom:14px;width:3px;border-radius:3px;background:var(--ehc-brand)}
.ehc-item-copy{min-width:0;flex:1}
.ehc-item-top{display:flex;align-items:baseline;justify-content:space-between;gap:8px}
.ehc-item-top strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ehc-ink);font-size:.94rem}
.ehc-item-top time{flex:none;font-size:.72rem;color:var(--ehc-muted)}
.ehc-item-copy p{margin:2px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.84rem;color:var(--ehc-muted)}
.ehc-item-tools{display:flex;gap:2px;opacity:0;transition:opacity .15s}
.ehc-item:hover .ehc-item-tools,.ehc-item:focus-within .ehc-item-tools{opacity:1}
.ehc-item-tools .ehc-icon-btn{width:30px;height:30px;font-size:.8rem;color:var(--ehc-muted);background:#fff}
.ehc-side-empty{margin:24px 18px;padding:22px;text-align:center;color:var(--ehc-muted);border:1px dashed #d0d5dd;border-radius:14px;font-size:.9rem}
.ehc-side-foot{padding:8px 16px;border-top:1px solid var(--ehc-line)}

/* Avatars */
.ehc-avatar{--ehc-av:#800000;display:inline-grid;place-items:center;width:44px;height:44px;flex:none;border-radius:50%;background:color-mix(in srgb,var(--ehc-av) 14%,#fff);color:var(--ehc-av);font-weight:800;font-size:.9rem;letter-spacing:.02em}
.ehc-avatar.is-sm{width:30px;height:30px;font-size:.7rem}
.ehc-avatar.is-group{background:var(--ehc-av);color:#fff}

/* Main pane */
.ehc-main{display:flex;flex-direction:column;min-width:0;min-height:0;background:var(--ehc-bg)}
.ehc-head{display:flex;align-items:center;gap:12px;padding:12px 18px;background:#fff;border-bottom:1px solid var(--ehc-line)}
.ehc-head-copy{min-width:0;flex:1}
.ehc-head-copy h2{margin:0;font-size:1.05rem;color:var(--ehc-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ehc-head-copy p{margin:2px 0 0;font-size:.8rem;color:var(--ehc-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ehc-head-actions{display:flex;gap:4px}
.ehc-back{display:none}
.ehc-thread{flex:1;overflow-y:auto;padding:18px 22px 8px;display:flex;flex-direction:column;gap:2px;scroll-behavior:smooth}
.ehc-day{align-self:center;margin:14px 0 10px;padding:4px 12px;border-radius:999px;background:#fff;border:1px solid var(--ehc-line);font-size:.72rem;font-weight:700;color:var(--ehc-muted);text-transform:uppercase;letter-spacing:.04em}
.ehc-row{display:flex;align-items:flex-end;gap:8px;max-width:78%}
.ehc-row.is-mine{align-self:flex-end;flex-direction:row-reverse}
.ehc-row.is-first{margin-top:10px}
.ehc-row .ehc-avatar{visibility:hidden}
.ehc-row.is-last .ehc-avatar{visibility:visible}
.ehc-row.is-mine .ehc-avatar{display:none}
.ehc-bubble{position:relative;min-width:0;padding:8px 12px 6px;background:#fff;border-radius:16px;box-shadow:0 1px 2px #1018280f;overflow-wrap:anywhere}
.ehc-row:not(.is-mine) .ehc-bubble{border-bottom-left-radius:6px}
.ehc-row:not(.is-mine):not(.is-first) .ehc-bubble{border-top-left-radius:6px}
.ehc-row.is-mine .ehc-bubble{background:var(--ehc-brand);color:#fff;border-bottom-right-radius:6px}
.ehc-row.is-mine:not(.is-first) .ehc-bubble{border-top-right-radius:6px}
.ehc-sender{display:block;margin-bottom:2px;font-size:.75rem;font-weight:800;color:var(--ehc-brand)}
.ehc-row:not(.is-first) .ehc-sender,.ehc-row.is-mine .ehc-sender,.ehc[data-type="direct"] .ehc-sender{display:none}
.ehc-text{margin:0;white-space:pre-wrap;line-height:1.45;font-size:.94rem}
.ehc-meta{display:block;margin-top:2px;text-align:right;font-size:.68rem;opacity:.65}
.ehc-msg-tools{display:flex;gap:2px;align-self:center;visibility:hidden;opacity:0;transition:opacity .15s}
.ehc-row:hover .ehc-msg-tools,.ehc-row:focus-within .ehc-msg-tools,.ehc-row.is-open .ehc-msg-tools{visibility:visible;opacity:1}
.ehc-msg-tools .ehc-icon-btn{width:28px;height:28px;font-size:.75rem;color:var(--ehc-muted)}
.ehc-msg-tools .ehc-icon-btn:hover{background:#fff}
.ehc-row.is-pending .ehc-bubble{opacity:.6}
.ehc-row.is-failed .ehc-bubble{outline:2px solid #d92d20}
.ehc-thread-empty{margin:auto;text-align:center;color:var(--ehc-muted);max-width:320px}
.ehc-thread-empty i{display:block;margin:0 auto 12px;font-size:2.2rem;color:#d0d5dd}
.ehc-jump{position:absolute;right:26px;bottom:96px;display:none}
.ehc-jump.is-visible{display:inline-grid;background:#fff;box-shadow:0 4px 12px #10182826}

/* Composer */
.ehc-compose{display:flex;align-items:flex-end;gap:10px;padding:12px 72px 12px 16px;background:#fff;border-top:1px solid var(--ehc-line)}
.ehc-compose-field{flex:1;display:flex;align-items:flex-end;border:1px solid #d0d5dd;border-radius:22px;background:#fff;padding:4px 6px 4px 16px}
.ehc-compose-field:focus-within{border-color:var(--ehc-brand);box-shadow:0 0 0 3px #80000014}
.ehc .ehc-compose textarea{flex:1;width:100%;min-height:0 !important;height:38px;border:0 !important;box-shadow:none !important;outline:0;resize:none;max-height:160px;padding:8px 0 !important;font:inherit;font-size:.95rem;line-height:1.4;background:transparent}
.ehc-compose small{display:block;padding:0 4px;color:#98a2b3;font-size:.7rem}
.ehc-send{width:44px;height:44px}
.ehc-send:disabled{opacity:.45;cursor:not-allowed}

/* Welcome (no conversation selected) */
.ehc-welcome{margin:auto;padding:30px;text-align:center;max-width:420px;color:var(--ehc-muted)}
.ehc-welcome-icon{display:grid;place-items:center;width:84px;height:84px;margin:0 auto 18px;border-radius:50%;background:var(--ehc-brand-soft);color:var(--ehc-brand);font-size:2rem}
.ehc-welcome h2{margin:0 0 8px;color:var(--ehc-ink)}
.ehc-welcome p{margin:0 0 20px}

/* Members modal list + form controls */
.ehc-members{list-style:none;margin:0 0 16px;padding:0;display:grid;gap:8px}
.ehc-members li{display:flex;align-items:center;gap:10px}
.ehc-members small{color:var(--ehc-muted)}
.ehc-field{width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:10px;font:inherit}
.ehc-field[multiple]{min-height:150px}
.ehc-label{display:block;margin:12px 0 6px;font-weight:700}
.ehc-label:first-child{margin-top:0}
.ehc-hint{display:block;margin-top:6px;color:var(--ehc-muted);font-size:.8rem}
.ehc-seg{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:4px;border-radius:12px;background:#f2f4f7}
.ehc-seg label{display:flex;align-items:center;justify-content:center;gap:8px;padding:9px;border-radius:9px;cursor:pointer;font-weight:700;color:var(--ehc-muted)}
.ehc-seg input{position:absolute;opacity:0;pointer-events:none}
.ehc-seg label:has(input:checked){background:#fff;color:var(--ehc-brand);box-shadow:0 1px 3px #1018281a}
.ehc-seg label:has(input:focus-visible){outline:2px solid var(--ehc-brand)}

/* Responsive: one pane at a time */
@media (max-width:900px){
    .ehc{grid-template-columns:1fr;height:calc(100vh - 120px);border-radius:14px}
    .ehc.is-thread .ehc-side{display:none}
    .ehc.is-list .ehc-main{display:none}
    .ehc-back{display:inline-grid}
    .ehc-row{max-width:88%}
    .ehc-thread{padding:14px 12px 8px}
    .ehc-item-tools{opacity:1}
}
@media (hover:none){.ehc-item-tools{opacity:1}}
</style>
