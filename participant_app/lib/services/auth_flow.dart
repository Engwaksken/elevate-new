import 'dart:async';

import '../core/logger.dart';
import 'api_service.dart';
import 'download_service.dart';
import 'local_database.dart';
import 'notification_service.dart';
import 'sync_service.dart';

/// Sign-in / sign-out side effects shared by the login screen, the
/// sign-out action and the global 401 handler.
abstract final class AuthFlow {
  static Future<void> afterSignIn() async {
    // Not awaited: the notification permission prompt must not hold the
    // user on the login screen until they answer it.
    unawaited(
      NotificationService.instance.registerCurrentDevice().catchError(
        (Object error) => appLog('Device registration failed', error),
      ),
    );
    try {
      await SyncService.instance.syncNow();
    } catch (error) {
      // The app works from whatever is cached; sync retries later.
      appLog('Initial sync failed', error);
    }
  }

  /// Clears the server token (best effort) and all participant data held
  /// on this device, including offline files.
  static Future<void> signOut({bool remote = true}) async {
    await SyncService.instance.stopAutoSync();
    await NotificationService.instance.unregisterDeviceToken();

    if (remote) {
      await ApiService.instance.logout();
    } else {
      await ApiService.instance.clearSession();
    }

    await LocalDatabase.instance.clearAll();
    await DownloadService.instance.removeAll();
  }
}
