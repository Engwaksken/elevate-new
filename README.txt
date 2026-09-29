Phase 7 V3 Android release-build fix

The build log showed two independent Android issues:

1. flutter_local_notifications requires Android core-library desugaring.
   This patch enables coreLibraryDesugaring and adds desugar_jdk_libs 2.1.5.

2. Kotlin incremental compilation was trying to relativise plugin source files
   from the C: Pub cache against the D: project and failed because Windows drive
   roots differ. This patch disables Kotlin incremental compilation and
   classpath snapshots for this Android project.

The continuation installer also runs flutter clean and removes only the
project-local android/.gradle cache before rebuilding. It does not delete the
global Pub cache or global Gradle cache.

Run:
powershell -ExecutionPolicy Bypass -File .\APPLY_AND_PUSH_PHASE7_PRODUCTION_READINESS_V3.ps1 -ProjectPath "D:\projects\elevate_her"

Validate without commit/push:
powershell -ExecutionPolicy Bypass -File .\APPLY_AND_PUSH_PHASE7_PRODUCTION_READINESS_V3.ps1 -ProjectPath "D:\projects\elevate_her" -NoPush
