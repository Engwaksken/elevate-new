import 'package:flutter/services.dart';

import '../core/logger.dart';

/// Android FLAG_SECURE while view-only course files are on screen: blocks
/// screenshots, screen recording and the recent-apps thumbnail. A no-op on
/// platforms without the channel (iOS cannot block screenshots).
class SecureScreen {
  SecureScreen._();

  static const MethodChannel _channel = MethodChannel('org.elevateher360/secure_screen');

  /// Nested viewers keep the flag until the last one closes.
  static int _holders = 0;

  static Future<void> acquire() async {
    _holders++;
    if (_holders == 1) await _set(true);
  }

  static Future<void> release() async {
    if (_holders == 0) return;
    _holders--;
    if (_holders == 0) await _set(false);
  }

  static Future<void> _set(bool secure) async {
    try {
      await _channel.invokeMethod<void>('setSecure', {'secure': secure});
    } on MissingPluginException {
      // iOS, tests and other platforms.
    } catch (error) {
      appLog('SecureScreen failed', error);
    }
  }
}
