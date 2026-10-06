<style>
.itq-stats .itq-stat{color:inherit;text-decoration:none;transition:border-color .15s ease,box-shadow .15s ease}
.itq-stats .itq-stat:hover,.itq-stats .itq-stat:focus-visible{border-color:#800000;box-shadow:0 4px 14px rgba(128,0,0,.08)}
.itq-filters{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:16px}
.itq-filters .search-box{flex:1 1 240px;min-width:0}
.itq-filters select{flex:0 1 170px;width:auto!important;min-width:0;min-height:38px!important;font-size:.82rem}
.itq-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.itq-card{display:flex;flex-direction:column;gap:8px;min-width:0;padding:14px;border:1px solid #e4e7ec;border-top:3px solid #d0d5dd;border-radius:12px;background:#fff;color:inherit;text-decoration:none;transition:transform .15s ease,box-shadow .15s ease}
.itq-card:hover,.itq-card:focus-visible{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.1)}
.itq-card.itq-open{border-top-color:#2e90fa}.itq-card.itq-in_progress{border-top-color:#f79009}
.itq-card.itq-awaiting_requester{border-top-color:#7a5af8}.itq-card.itq-resolved{border-top-color:#12b76a}
.itq-card-top{display:flex;justify-content:space-between;align-items:center;gap:8px}
.itq-id{color:#98a2b3;font-size:.74rem;font-weight:700}
.itq-subject{color:#101828;font-size:.92rem;line-height:1.3;overflow-wrap:anywhere;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.itq-desc{margin:0;color:#667085;font-size:.78rem;line-height:1.4;overflow-wrap:anywhere;flex:1}
.itq-meta,.itq-foot{display:flex;flex-wrap:wrap;gap:4px 12px;color:#475467;font-size:.72rem}
.itq-meta i,.itq-foot i{margin-right:4px;color:#98a2b3}
.itq-foot{padding-top:8px;border-top:1px dashed #eef0f3}
.itq-chip{display:inline-flex;padding:3px 9px;border-radius:999px;background:#f2f4f7;color:#344054;font-size:.66rem;font-weight:800;white-space:nowrap}
.itq-chip--open{background:#eff8ff;color:#175cd3}.itq-chip--in_progress{background:#fffaeb;color:#b54708}
.itq-chip--awaiting_requester{background:#f4f3ff;color:#5925dc}.itq-chip--resolved{background:#ecfdf3;color:#067647}
.itq-pri--urgent,.itq-pri--urgent i{color:#b42318!important;font-weight:800}.itq-pri--high,.itq-pri--high i{color:#b54708!important;font-weight:700}
.itq-empty{grid-column:1/-1}
@media(max-width:1279px){.itq-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:1000px){.itq-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.itq-grid{grid-template-columns:1fr}.itq-filters select{flex:1 1 45%}}
/* Detail page */
.itq-detail{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}
.itq-dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px;margin:0 0 16px}
.itq-dl dt{margin-bottom:3px;color:#667085;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.itq-dl dd{margin:0;color:#101828}
.itq-body{padding:14px;border-radius:10px;background:#f9fafb;color:#344054;line-height:1.55;overflow-wrap:anywhere}
.itq-controls form{margin:0 0 16px}
.itq-controls .form-group{margin-bottom:8px}
.itq-controls .btn{width:100%;justify-content:center}
@media(max-width:1000px){.itq-detail{grid-template-columns:1fr}}
@media(max-width:600px){.itq-dl{grid-template-columns:1fr}}
</style>
