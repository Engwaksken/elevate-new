ElevateHer360 Phase 2 update

This update continues the appraisal/workspace restructuring.

Changes:
- Clean appraisal workspace routes.
- Adds Return for Revision route.
- Uses saveMeeting() consistently.
- Employee KRA/KPI saving updates existing rows instead of deleting/recreating all rows.
- Preserves supervisor and agreed scores when an employee edits existing KRA/KPI rows.
- Supervisor review now records supervisor scores only.
- Agreed scores are recorded at the appraisal meeting.
- Employee confirmation and supervisor confirmation are separated in workflow history.
- Dynamic Add KRA / Remove KRA / Add KPI / Remove KPI interface.
- Compact admin sidebar grouped into Workspaces, People & Performance, Programme and Administration.

Install:
1. Extract the ZIP anywhere, including inside the Laravel project.
2. From PowerShell run:
   .\apply_update.ps1 -ProjectPath "D:\projects\elevate_her"

Then run:
   cd D:\projects\elevate_her
   php -l routes\workspaces.php
   php -l app\Http\Controllers\HR\AppraisalWorkspaceController.php
   php artisan optimize:clear
   php artisan route:list --path=staff/performance

Expected appraisal route count: 10.
