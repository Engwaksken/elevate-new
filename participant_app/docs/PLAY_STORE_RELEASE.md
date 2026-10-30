# Google Play release guide

Everything needed to publish the ElevateHer360 participant app on Google Play. The code side
(signing, shrinking, versioning, account deletion) is already set up; this guide covers the
build command and the Play Console steps.

App ID: `org.elevateher360.app` (cannot change after the first upload). The Kotlin `namespace`
stays `org.elevateher360.elevateher360_participant`; only `applicationId` is the Play package name.
`android/app/google-services.json` must contain a Firebase Android app for this package.

## 1. Before every release

1. Bump the version in `pubspec.yaml` (`version: 1.3.0+4` = name `1.3.0`, build number `4`).
   The build number must be higher than any build already uploaded to Play.
   Also update `appVersion` in `lib/core/app_config.dart` to the same name.
2. Run the tests: `flutter test`.
3. Make sure the server is deployed first (the app calls `/api/v1/participant/account/deletion-request`
   and links to `/account-deletion`), including `php artisan migrate --force`.

## 2. Build the upload file (Android App Bundle)

```
cd participant_app
flutter build appbundle --release --obfuscate --split-debug-info=build/symbols
```

Output: `build/app/outputs/bundle/release/app-release.aab` - this is the file you upload.

- Keep the `build/symbols` folder for this version (zip it with the version number). It is needed
  to read crash reports from the obfuscated build.
- Point the app at another server with `--dart-define=API_BASE_URL=https://.../api/v1/participant`
  (defaults are in `lib/core/app_config.dart`).
- The build needs about 4 GB of free memory. `android/gradle.properties` is sized for an 8 GB PC;
  close other programs if Gradle reports it ran out of memory.

## 3. Signing (do this carefully, once)

The release is signed with the upload key in `android/app/elevateher360-release.jks`, configured
through `android/key.properties`. Both files are git-ignored and must never be committed.

- **Back up the `.jks` file and its passwords** somewhere safe and separate (for example a password
  manager plus an offline copy). Without them you cannot publish updates.
- On the first upload, accept **Play App Signing**. Google then holds the app signing key and your
  `.jks` is only the upload key, which Google can reset if it is ever lost.

## 4. Play Console checklist

### App setup
- **Account type:** a *personal* developer account created after November 2023 must run a closed test
  with at least 12 testers for 14 days before it can publish to production. An *organisation*
  account (recommended for Women in Technology Uganda, needs a D-U-N-S number) does not.
- **App access:** reviewers need a test login. Create a participant account for Google
  (verified email, enrolled in a sample course) and enter its email and password under
  *App content > App access*.
- **Privacy policy URL:** `https://site.elevateher360.org/privacy-policy`
- **Account deletion URL:** `https://site.elevateher360.org/account-deletion`
- **Ads:** No ads.
- **Target audience:** 18 and over (matches `LEGAL_MINIMUM_AGE`). Choosing under-18 brings in the
  Families policy and extra review.
- **Content rating:** complete the questionnaire as an *Education* app; no violence, sexual content,
  gambling or user-to-user public chat. Expect a rating of Everyone / PEGI 3.
- **News app / COVID-19 / government app:** No.

### Update the live privacy policy (CMS)
The public privacy policy is edited in **Admin > Frontend CMS > Privacy Policy**, not in code.
Update its "Deleting your account and data" section to say people can delete their account from
the app (Profile > Settings > Delete my account) or at the account deletion page above. The draft
text in `resources/views/legal/privacy.blade.php` (section 12) already describes the process and
can be copied. Have the policy reviewed before launch; it is marked as a draft needing legal review.

### Data safety form
Answers based on what the app and platform actually do. Re-check them whenever features change.

| Question | Answer |
|---|---|
| Does the app collect or share user data? | Yes, collects |
| Is all data encrypted in transit? | Yes (HTTPS only) |
| Can users request deletion? | Yes, in the app and at the account deletion URL |
| Is data shared with third parties? | No. Firebase (push notifications) and the AI provider (career mentor) act as service providers processing data on our behalf, which Play does not count as sharing |

Data types collected (all: *collected*, *not shared*, *required for app functionality / account management*, *not optional* unless noted):

| Category | Data type | Notes |
|---|---|---|
| Personal info | Name, Email address, Phone number, User IDs | Account and sign-in |
| Personal info | Address | District and country only (profile) |
| Personal info | Other info | Gender, date of birth, education, employment, career interests (profile) |
| Health and fitness | Health info | Optional disability information, used for accessibility support |
| Photos and videos | Photos | Optional profile photo |
| Files and docs | Files and docs | Resumes, cover letters, assignment uploads |
| Messages | Other in-app messages | AI career mentor questions, support tickets |
| App activity | App interactions | Course progress, reading progress, bookmarks |
| App activity | Other user-generated content | Goals, feedback, survey answers |
| Device or other IDs | Device or other IDs | Firebase push notification token |

Not collected: precise or approximate location from the device, contacts, calendar, SMS, call logs,
financial info, audio, web browsing history, crash logs or analytics. Biometric sign-in uses the
phone's own fingerprint/face unlock; no biometric data leaves the device.

### Store listing (draft - edit to taste)
- **App name (30):** ElevateHer360
- **Short description (80):** Learn, find mentors and grow your career with Women in Technology Uganda.
- **Full description (4000):**

  ElevateHer360 is the participant app for Women in Technology Uganda's programmes. Keep learning
  even with patchy internet, stay close to your mentor and take the next step in your career.

  - Learn: follow your courses, lessons and assignments, and download materials to read offline.
  - Mentorship: see your sessions and goals, and get practical career advice from the AI career mentor.
  - Career: build your resume and cover letters with simple AI help.
  - Library: read books in the app and pick up where you left off.
  - Stay on track: reminders for classes, appointments and the steps you plan.
  - Certificates: view and share the certificates you earn.

  You need an ElevateHer360 participant account to sign in. Register at site.elevateher360.org.

- **Graphics:** app icon 512 x 512 (`assets/icon/play_store_icon_512.png`), feature graphic
  1024 x 500 (`assets/icon/play_feature_graphic_1024x500.png`), and at least 2 phone screenshots (1080 x 1920 or similar) of real screens:
  dashboard, a course, the AI career mentor, the library reader.
- **Category:** Education. **Contact email:** support@elevateher360.org.

## 5. Release
1. Upload `app-release.aab` to **Internal testing** first and install it from the Play link on a real
   phone. Check sign in, a lesson download, notifications and Settings > Delete my account.
2. Promote to Closed testing (required first for new personal accounts) and then Production.
3. Write release notes in plain language, for example "Delete your account from Settings,
   and faster, smaller app."
