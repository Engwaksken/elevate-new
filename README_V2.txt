V2 avoids the unrelated trailing whitespace in routes/web.php by checking only the five intended patch files.

Run this after the original patch has already been applied:

powershell -ExecutionPolicy Bypass `
  -File .\APPLY_AND_PUSH_UI_BRANDING_FIX_V2.ps1 `
  -ProjectPath "D:\projects\elevate_her"

This script stages, commits and pushes only the five intended files.
