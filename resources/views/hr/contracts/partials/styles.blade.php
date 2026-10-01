{{-- Shared styles for the employment contract e-signature pages (HR and staff). --}}
<style>
    .ct-card { margin-bottom:16px; }
    .ct-grid { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:20px; align-items:start; }
    .ct-meta { display:grid; grid-template-columns:170px minmax(0,1fr); gap:8px 14px; margin:0; font-size:.86rem; }
    .ct-meta dt { color:#667085; font-weight:600; }
    .ct-meta dd { margin:0; color:#172033; overflow-wrap:anywhere; }
    .ct-muted { color:#667085; font-size:.82rem; }
    .ct-actions { display:inline-flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .ct-actions form { margin:0; }
    .ct-manage { margin-top:16px; }
    .ct-meta .ct-actions { margin-top:6px; display:flex; }
    .ct-signature-box { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; min-height:120px; padding:12px; border:1px dashed #d0d5dd; border-radius:10px; background:#fcfcfd; text-align:center; }
    .ct-signature-box img { max-width:100%; max-height:140px; object-fit:contain; }
    .ct-signature-box small { color:#667085; font-size:.75rem; }
    .ct-viewer { width:100%; height:70vh; min-height:420px; border:1px solid #e4e7ec; border-radius:10px; background:#f5f6fa; }
    .ct-methods { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
    .ct-method { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid #d0d5dd; border-radius:10px; cursor:pointer; font-weight:600; font-size:.86rem; }
    .ct-method:has(input:checked) { border-color:#800000; background:#fdf5f5; color:#800000; }
    .ct-pad-wrap { position:relative; }
    .ct-pad { display:block; width:100%; height:220px; border:2px dashed #d0d5dd; border-radius:10px; background:#fff; touch-action:none; cursor:crosshair; -webkit-user-select:none; user-select:none; }
    .ct-pad.has-ink { border-style:solid; border-color:#98a2b3; }
    .ct-pad-hint { position:absolute; left:0; right:0; bottom:44%; text-align:center; color:#98a2b3; font-size:.9rem; pointer-events:none; }
    .ct-pad-line { position:absolute; left:8%; right:8%; bottom:28%; border-bottom:1px solid #e4e7ec; pointer-events:none; }
    .ct-pad-tools { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:8px; }
    .ct-sign-form .form-group { margin-bottom:14px; }
    .ct-sign-form textarea, .ct-sign-form input[type=file] { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #d0d5dd; border-radius:8px; font:inherit; font-size:.86rem; }
    .ct-sign-form label.ct-label { display:block; margin-bottom:6px; font-size:.78rem; font-weight:700; color:#172033; }
    .ct-error { margin:6px 0 0; color:#b42318; font-size:.8rem; }
    .ct-upload-preview { display:none; max-width:100%; max-height:140px; margin-top:10px; border:1px solid #e4e7ec; border-radius:8px; }
    [data-sign-panel][hidden] { display:none !important; }
    @media (max-width: 760px) {
        .ct-grid { grid-template-columns:1fr; }
        .ct-meta { grid-template-columns:1fr; gap:2px; }
        .ct-meta dd { margin-bottom:8px; }
        .ct-viewer { height:60vh; min-height:320px; }
        .ct-pad { height:200px; }
    }
</style>
