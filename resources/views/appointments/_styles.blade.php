<style>
.appt-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 20px}
.appt-stat{display:flex;align-items:center;gap:12px;min-width:0;padding:14px 16px;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;background:#fff}
.appt-stat i{flex:0 0 38px;width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#fff7da;color:#800000}
.appt-stat small{display:block;color:#667085;font-weight:700;font-size:.74rem}
.appt-stat strong{font-size:1.25rem;color:#101828}
.appt-section{margin:0 0 22px}
.appt-section>h2{display:flex;align-items:center;gap:8px;margin:0 0 12px;font-size:1.05rem;color:#101828}
.appt-list{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;align-items:start}
.appt-card{display:flex;flex-direction:column;gap:10px;min-width:0;height:100%;padding:14px;border:1px solid #e4e7ec;border-left:4px solid #d0d5dd;border-radius:12px;background:#fff}
.appt-card--pending{border-left-color:#f79009}.appt-card--rescheduled_proposed{border-left-color:#7a5af8}.appt-card--approved{border-left-color:#12b76a}
.appt-card--declined,.appt-card--cancelled{border-left-color:#f04438}.appt-card--completed{border-left-color:#2e90fa}
.appt-date{align-self:flex-start;display:inline-flex;align-items:baseline;gap:6px;padding:5px 10px;border-radius:8px;background:#fff4e3;color:#800000;line-height:1.1}
.appt-date b{font-size:1.1rem}.appt-date span{font-size:.7rem;font-weight:800;text-transform:uppercase}
.appt-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.appt-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:6px 10px}
.appt-head h3{margin:0;font-size:.95rem;color:#101828;overflow-wrap:anywhere}
.appt-meta{display:flex;flex-wrap:wrap;gap:4px 14px;margin:0;padding:0;list-style:none;color:#475467;font-size:.78rem}
.appt-meta li{display:inline-flex;align-items:center;gap:5px;min-width:0;overflow-wrap:anywhere}
.appt-meta i{color:#98a2b3}
.appt-details{margin:0;color:#475467;font-size:.8rem;overflow-wrap:anywhere}
.appt-note{margin:0;padding:8px 10px;border-radius:8px;background:#f9fafb;color:#344054;font-size:.78rem;overflow-wrap:anywhere}
.appt-note--proposal{background:#f4f3ff;color:#3e1c96}.appt-note--reason{background:#fef3f2;color:#912018}.appt-note--link{background:#ecfdf3;color:#05603a}
.appt-note a{color:inherit;font-weight:700;text-decoration:underline}
.appt-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
.appt-actions form{margin:0}
.appt-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:.68rem;font-weight:800;white-space:nowrap;background:#f2f4f7;color:#344054}
.appt-chip--pending{background:#fffaeb;color:#b54708}.appt-chip--rescheduled_proposed{background:#f4f3ff;color:#5925dc}
.appt-chip--approved{background:#ecfdf3;color:#067647}.appt-chip--declined,.appt-chip--cancelled{background:#fef3f2;color:#b42318}
.appt-chip--completed{background:#eff8ff;color:#175cd3}
.appt-empty{padding:22px;border:1px dashed #d0d5dd;border-radius:12px;background:#fcfcfd;color:#667085;text-align:center}
.appt-empty i{display:block;margin-bottom:6px;font-size:1.4rem;color:#98a2b3}
.appt-hint{display:block;margin-top:5px;color:#667085;font-size:.72rem}
.appt-modes{display:flex;flex-wrap:wrap;gap:10px}
.appt-modes label{display:inline-flex!important;align-items:center;gap:6px;margin:0!important;padding:9px 12px;border:1px solid #d0d5dd;border-radius:8px;font-weight:600!important;cursor:pointer}
.appt-modes input{width:auto!important;min-height:0!important;margin:0}
.appt-rules{margin:0 0 14px;padding:10px 12px 10px 30px;border-radius:8px;background:#fffaeb;color:#7a2e0e;font-size:.76rem}
.appt-tabs{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px;padding:0;list-style:none;border-bottom:1px solid #e4e7ec}
.appt-tabs a{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-bottom:3px solid transparent;color:#475467;font-weight:700;font-size:.85rem;text-decoration:none}
.appt-tabs a[aria-current="page"]{border-bottom-color:#800000;color:#800000}
.appt-tabs .appt-count{min-width:20px;padding:0 6px;border-radius:999px;background:#f2f4f7;color:#344054;font-size:.68rem;line-height:18px;text-align:center}
.appt-tabs a[aria-current="page"] .appt-count{background:#800000;color:#fff}
.eh-modal .eh-modal-dialog{max-width:100%}
@media(max-width:1279px){.appt-list{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:1100px){.appt-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.appt-list{grid-template-columns:1fr}}
@media(max-width:900px){.appt-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){
    .appt-stats{grid-template-columns:1fr 1fr;gap:8px}
    .appt-stat{padding:10px;gap:8px}.appt-stat i{flex-basis:30px;width:30px;height:30px}
    .appt-card{flex-direction:column;gap:10px;padding:12px}
    .appt-date{flex:none;width:auto;height:auto;flex-direction:row;gap:6px;justify-content:flex-start;padding:6px 10px;align-self:flex-start}
    .appt-date b{font-size:1rem}
    .appt-actions .btn{flex:1 1 auto;justify-content:center}
    .eh-modal{padding:10px}
    .eh-modal-footer{flex-wrap:wrap}
}
</style>
