ElevateHer360 Participant Tabs Update

1. Extract this package.
2. Run from PowerShell:
   powershell -ExecutionPolicy Bypass -File .\install_tabs_full.ps1

Default project path: D:\projects\elevate_her
Optional custom path:
   powershell -ExecutionPolicy Bypass -File .\install_tabs_full.ps1 -ProjectPath "D:\projects\elevate_her"

The installer:
- backs up replaced participant Blade views
- adds participant-tabs.css
- imports it into app.css if needed
- appends shared tab behaviour to app.js without removing sidebar JS
- rewrites dashboard, learning, mentorship, jobs, library, calendar, notifications and profile to tab/list layouts
- writes files as UTF-8 without BOM
- clears Laravel caches, rebuilds Vite, and checks key routes
