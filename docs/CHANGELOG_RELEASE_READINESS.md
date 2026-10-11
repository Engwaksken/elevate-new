# Release-readiness changelog

## 2026-10-11 — security and quality gate pass

- Branding asset storage now derives the saved extension from decoded image bytes instead of client-controlled filename metadata.
- ICO is no longer advertised/accepted for favicon upload until safe ICO delivery is implemented; accepted formats now match the current raster detector.
- Added a regression test proving a PNG payload submitted with a `.jpg` suffix is saved with a `.png` suffix.
- Cleared the five existing Flutter analyzer informational findings.
- Added `docs/RELEASE_READINESS.md` with the prioritized issue register, verified local evidence, and outstanding release gates.

## Release evidence boundary

No production deployment, physical-device test, Android signing/build, iOS/Xcode build, backup restore, notification provider delivery, or stakeholder UAT is claimed by this changelog.
