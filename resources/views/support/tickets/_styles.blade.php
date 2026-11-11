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
/* Collapsible queue cards, four per row */
.itq-list-tools{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;color:#667085;font-size:.78rem}
.itq-list{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:start}
.itq-row{min-width:0;border:1px solid #e4e7ec;border-top:3px solid #d0d5dd;border-radius:12px;background:#fff;transition:box-shadow .15s ease}
.itq-row:hover,.itq-row[open]{box-shadow:0 8px 20px rgba(16,24,40,.08)}
.itq-row.itq-open{border-top-color:#2e90fa}.itq-row.itq-in_progress{border-top-color:#f79009}
.itq-row.itq-awaiting_requester{border-top-color:#7a5af8}.itq-row.itq-resolved{border-top-color:#12b76a}
.itq-row>summary{display:flex;flex-direction:column;gap:6px;padding:11px 12px;cursor:pointer;list-style:none;min-width:0;font-size:.8rem;color:#475467}
.itq-row>summary::-webkit-details-marker{display:none}
.itq-row>summary:focus-visible{outline:2px solid #800000;outline-offset:2px;border-radius:10px}
.itq-row-top{display:flex;align-items:center;gap:8px;min-width:0}
.itq-row-top .itq-chip{margin-left:auto}
.itq-row .itq-caret{color:#98a2b3;font-size:.7rem;transition:transform .15s ease;flex:none}
.itq-row[open] .itq-caret{transform:rotate(90deg)}
.itq-row .itq-subject{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;-webkit-line-clamp:unset}
.itq-row[open] .itq-subject{white-space:normal}
.itq-row-foot{display:flex;justify-content:space-between;gap:8px;min-width:0;font-size:.72rem;color:#667085}
.itq-row-who{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.itq-row-who i{margin-right:4px;color:#98a2b3}
.itq-row-when{flex:none;white-space:nowrap}
.itq-row .itq-pri{white-space:nowrap;font-size:.72rem}
.itq-row-body{display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:10px 12px 12px;border-top:1px dashed #eef0f3}
.itq-row-body .itq-desc{white-space:pre-line}
.itq-list .itq-empty{grid-column:1/-1}
@media(max-width:1279px){.itq-list{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:1000px){.itq-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.itq-list{grid-template-columns:1fr}}
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
