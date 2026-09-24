ELEVATEHER360 ADMIN MODAL CRUD CORE

This batch is the first repository-safe conversion to the requested administration standard.

Updated modules:
- Programmes
- Projects
- Branches
- Cohorts

Each updated page now has:
- fixed admin sidebar layout
- statistic cards
- search
- status filters
- period filter: today/week/month/quarter/year
- custom from/to date filters
- page-size selector
- pagination
- multiple row selection
- bulk delete
- create modal
- edit modal
- delete confirmation modal
- field placeholders and hints
- success/error feedback through the shared admin layout
- Font Awesome icons

Important:
The remaining platform modules cannot be safely mass-converted by a blind search/replace because their route contracts differ:
- Workplans use submit/approve actions.
- Indicators use target/calculate/verify actions.
- HR uses employee/contract/leave/appraisal/exit workflows.
- Procurement uses requests/quotations/orders/receipts.
- Assets use assign/return/maintenance/disposal workflows.
These need module-specific modal actions rather than generic CRUD.

INSTALL
1. Make a checkpoint:
   git add .
   git commit -m "Checkpoint before admin modal CRUD core"

2. Extract ZIP contents directly to:
   D:\projects\elevate_her
   Choose Replace files in destination.

3. Run:
   cd D:\projects\elevate_her
   Unblock-File .\APPLY_ADMIN_MODAL_CORE.ps1
   powershell -ExecutionPolicy Bypass -File .\APPLY_ADMIN_MODAL_CORE.ps1

4. Ctrl+F5.

Next module batches should apply this same UX standard to:
- Users + Roles
- Learning
- Mentorship
- Jobs
- Library
- Workplans + Tasks + Deliverables
- MEAL / Indicators / Results Framework
- HR
- Procurement
- Assets
- System / migrations / search / settings
