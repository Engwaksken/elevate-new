{{-- Minimal, scoped stand-in for the Bootstrap utilities used by older pages.
     Bootstrap itself is not loaded; wrap content in .eh-bs to use these. --}}
@once
<style>
.eh-bs { --bs-gap:16px; color:var(--ad-text,#172033); }
.eh-bs .row { display:flex; flex-wrap:wrap; margin:calc(var(--g,0px) / -2); }
.eh-bs .row > * { box-sizing:border-box; flex:0 0 100%; max-width:100%; padding:calc(var(--g,0px) / 2); }
.eh-bs .g-2 { --g:8px; } .eh-bs .g-3 { --g:16px; }
@media (min-width:768px) {
    .eh-bs .row > .col-md-2 { flex-basis:16.6667%; max-width:16.6667%; }
    .eh-bs .row > .col-md-3 { flex-basis:25%; max-width:25%; }
    .eh-bs .row > .col-md-4 { flex-basis:33.3333%; max-width:33.3333%; }
    .eh-bs .row > .col-md-5 { flex-basis:41.6667%; max-width:41.6667%; }
    .eh-bs .row > .col-md-6 { flex-basis:50%; max-width:50%; }
    .eh-bs .row > .col-md-7 { flex-basis:58.3333%; max-width:58.3333%; }
}
@media (min-width:992px) { .eh-bs .row > .col-lg-6 { flex-basis:50%; max-width:50%; } }
.eh-bs .col-12 { flex-basis:100%; max-width:100%; }

.eh-bs .card { background:#fff; border:1px solid var(--ad-border,#e4e7ec); border-radius:12px; overflow:hidden; }
.eh-bs .card-header { padding:12px 16px; border-bottom:1px solid var(--ad-border,#e4e7ec); background:#fff; }
.eh-bs .card-body { padding:16px; }
.eh-bs .h3 { font-size:1.35rem; } .eh-bs .h5 { font-size:1rem; } .eh-bs .h6 { font-size:.92rem; }
.eh-bs .h3, .eh-bs .h5, .eh-bs .h6 { margin:0 0 6px; font-weight:700; line-height:1.3; }

.eh-bs .form-label { display:block; margin-bottom:4px; font-size:.8rem; font-weight:600; color:var(--ad-text,#172033); }
.eh-bs .form-control { box-sizing:border-box; display:block; width:100%; min-height:38px; padding:8px 10px; border:1px solid #d0d5dd; border-radius:8px; background:#fff; color:inherit; font:inherit; font-size:.88rem; }
.eh-bs textarea.form-control { min-height:auto; resize:vertical; }
.eh-bs .form-control:focus { outline:2px solid rgba(128,0,0,.18); border-color:var(--ad-primary,#800000); }
.eh-bs .form-control:disabled { background:#f2f4f7; color:var(--ad-muted,#667085); }

.eh-bs .btn-sm { padding:5px 10px; font-size:.8rem; }
.eh-bs .btn-success { background:#067647; border-color:#067647; color:#fff; }
.eh-bs .btn-success:hover { background:#05603a; }
.eh-bs .btn-outline-primary, .eh-bs .btn-outline-secondary, .eh-bs .btn-outline-danger { background:#fff; border:1px solid currentColor; }
.eh-bs .btn-outline-primary { color:var(--ad-primary,#800000); }
.eh-bs .btn-outline-secondary { color:#475467; }
.eh-bs .btn-outline-danger { color:#b42318; }

.eh-bs .table-responsive { overflow-x:auto; }
.eh-bs .table { width:100%; border-collapse:collapse; font-size:.86rem; }
.eh-bs .table th, .eh-bs .table td { padding:8px; border-bottom:1px solid var(--ad-border,#e4e7ec); text-align:left; vertical-align:top; }
.eh-bs .table-sm th, .eh-bs .table-sm td { padding:5px 8px; }
.eh-bs .align-middle td, .eh-bs .align-middle th { vertical-align:middle; }

.eh-bs .border { border:1px solid var(--ad-border,#e4e7ec); }
.eh-bs .border-top { border-top:1px solid var(--ad-border,#e4e7ec); }
.eh-bs .rounded { border-radius:10px; }
.eh-bs .bg-white { background:#fff; }

.eh-bs .d-flex { display:flex; } .eh-bs .d-inline-block { display:inline-block; }
.eh-bs .flex-wrap { flex-wrap:wrap; }
.eh-bs .justify-content-between { justify-content:space-between; }
.eh-bs .align-items-center { align-items:center; } .eh-bs .align-items-end { align-items:flex-end; } .eh-bs .align-items-start { align-items:flex-start; }
.eh-bs .gap-3 { gap:16px; }
.eh-bs .text-muted { color:var(--ad-muted,#667085); } .eh-bs .text-danger { color:#b42318; } .eh-bs .text-end { text-align:right; }
.eh-bs .small { font-size:.8rem; } .eh-bs .fw-bold { font-weight:700; }

.eh-bs .mb-0 { margin-bottom:0; } .eh-bs .mb-1 { margin-bottom:4px; } .eh-bs .mb-2 { margin-bottom:8px; } .eh-bs .mb-3 { margin-bottom:16px; } .eh-bs .mb-4 { margin-bottom:24px; }
.eh-bs .mt-1 { margin-top:4px; } .eh-bs .mt-2 { margin-top:8px; } .eh-bs .mt-3 { margin-top:16px; } .eh-bs .mt-4 { margin-top:24px; }
.eh-bs .me-1 { margin-right:4px; }
.eh-bs .p-3 { padding:16px; } .eh-bs .py-2 { padding-top:8px; padding-bottom:8px; } .eh-bs .pt-2 { padding-top:8px; }
.eh-bs .alert { margin-bottom:16px; }
</style>
@endonce
