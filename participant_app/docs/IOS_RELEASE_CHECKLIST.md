# iOS release checklist (needs a Mac)

The `ios/` platform folder was generated and configured on Windows. Nothing in
it has been built yet. You need a Mac with Xcode (current stable), CocoaPods
and the same Flutter version (`flutter --version`, currently 3.44.x) for the
steps below.

What is already set up:

| Item | Value |
| --- | --- |
| Bundle identifier | `org.elevateher360.elevateher360Participant` (Android uses `org.elevateher360.elevateher360_participant`, but iOS bundle IDs can't contain `_`) |
| Display name | ElevateHer360 |
| Deployment target | iOS 13.0 (`ios/Podfile` and `IPHONEOS_DEPLOYMENT_TARGET`); this is the highest minimum among the plugins (firebase_core/messaging, shared_preferences, url_launcher) |
| Info.plist | Photo library, camera and Apple Music usage strings (file_picker), `UIBackgroundModes` = `remote-notification`, `LSApplicationQueriesSchemes` https/http/mailto, `ITSAppUsesNonExemptEncryption` = false, no `NSAllowsArbitraryLoads` |
| Privacy manifest | `ios/Runner/PrivacyInfo.xcprivacy`, added to the Runner target's Copy Bundle Resources |
| Firebase | If `GoogleService-Info.plist` is missing, the app still starts. It logs the error and runs without push notifications. |
| Notification permission | Requested after sign-in, not at launch |

## 1. First open and build

1. `cd participant_app && flutter pub get`
2. `cd ios && pod install`. The Podfile exists because `flutter_secure_storage`
   and `open_filex` have no Swift Package Manager support, so CocoaPods and
   SwiftPM are both used. Commit the `Podfile.lock` this creates.
3. Open **`ios/Runner.xcworkspace`**, not the `.xcodeproj`.
4. Check that `PrivacyInfo.xcprivacy` shows up under Runner and is listed in
   Runner > Build Phases > Copy Bundle Resources. It was added to
   `project.pbxproj` by hand. If it's missing, drag the file into the Runner
   group and tick "Runner" under Target Membership.
5. `flutter build ios --debug --no-codesign` must succeed before you continue.

## 2. Firebase / push notifications

1. In the Firebase console (the project that holds the Android app), add an
   **iOS app** with bundle ID `org.elevateher360.elevateher360Participant`.
2. Download `GoogleService-Info.plist` and add it **through Xcode**: drag it
   into the Runner group, tick "Copy items if needed" and the Runner target.
   If you only copy it into the folder, it isn't bundled.
   The file is project-specific config. Treat it like `google-services.json`
   (that file is git-ignored on Android).
3. Apple Developer > Certificates, IDs & Profiles > Keys: create an **APNs
   Auth Key (.p8)**. Note the Key ID and your Team ID.
4. Firebase console > Project settings > Cloud Messaging > Apple app
   configuration: upload the .p8 with the Key ID and Team ID.
5. Test on a **physical device**. Push notifications don't work on the
   simulator for FCM. Sign in, accept the notification prompt, and check that
   `POST /device-token` is sent with `platform: ios`.

## 3. Signing and capabilities

1. Runner target > Signing & Capabilities: choose the Team and keep
   "Automatically manage signing" on (or pick a distribution provisioning
   profile).
2. **+ Capability > Push Notifications.** No `Runner.entitlements` file exists
   yet. Adding this capability makes Xcode create
   `ios/Runner/Runner.entitlements` with `aps-environment` and set
   `CODE_SIGN_ENTITLEMENTS`. Commit both changes.
3. **+ Capability > Background Modes**, tick **Remote notifications**. This
   matches the `UIBackgroundModes` entry already in Info.plist.
4. Register the App ID with the Push Notifications capability in the developer
   portal. Automatic signing does this for you.

## 4. App icon and launch screen

- `ios/Runner/Assets.xcassets/AppIcon.appiconset` still has the **Flutter
  default icon**. The Android launcher icon (`mipmap-*/ic_launcher.png`) is
  also the Flutter default, so the repo has no branded source image. Get a
  1024x1024 PNG with no transparency, then either fill the AppIcon set in
  Xcode or use a generator such as `flutter_launcher_icons` for both
  platforms. App Store review rejects the default icon.
- Optionally brand `LaunchImage` / `LaunchScreen.storyboard`.

## 5. Archive and TestFlight

1. Set the version in `pubspec.yaml` (`version: x.y.z+build`). The build
   number must go up for every upload.
2. `flutter build ipa --release`, or in Xcode choose Product > Archive with
   "Any iOS Device" selected.
3. Upload with Xcode Organizer or Transporter. Fix any ITMS warnings. The
   usual one is ITMS-90683 (missing purpose string). file_picker links the
   photo library, camera (through DKImagePickerController/DKCamera under
   SwiftPM) and media library APIs, so all three strings are already present.
   If Apple names another key, add it with an honest description.
4. Test with internal testers in TestFlight: sign-in, sync, lesson downloads
   and opening files, assignment submission with an attachment (Files, Photos),
   reminders, push notifications, and links (https/mailto).

## 6. App Store Connect: App Privacy ("nutrition label")

These answers must match `ios/Runner/PrivacyInfo.xcprivacy`. Every type is
**linked to the user**, is **not used for tracking**, and the only purpose is
**App Functionality**.

| App Store Connect category | Data type | Why |
| --- | --- | --- |
| Contact Info | Name | Participant account profile |
| Contact Info | Email Address | Sign-in and account |
| Contact Info | Phone Number | Account profile (shown on the More screen) |
| User Content | Other User Content | Assignment submission text and files |
| User Content | Photos or Videos | Images/videos attached to submissions |
| Identifiers | Device ID | Per-install ID + push token for notifications |
| Usage Data | Product Interaction | Course/lesson progress, completions |

- "Do you or your third-party partners use data for tracking?" **No**.
- No advertising data, no location, no health, no financial info, no
  diagnostics. Firebase Messaging is used only for push delivery, not
  Analytics. If you add Firebase Analytics or Crashlytics later, update both
  the manifest and these answers.
- Privacy policy URL: use the same one as on Google Play (the app's About &
  privacy policy screen).
- Export compliance: already answered in Info.plist
  (`ITSAppUsesNonExemptEncryption` = NO, standard HTTPS only).

## 7. Review notes

- Give App Review a working **demo participant account**, because the app is
  login-only.
- Explain that accounts are issued by the programme, and point to where users
  can request account deletion. Apple requires in-app account deletion or a
  clear path to it for apps with account creation.
