import 'package:flutter/foundation.dart';

/// Debug-only logging. Nothing is written in release builds, so raw
/// exception details never leak into device logs of production installs.
void appLog(String message, [Object? error, StackTrace? stackTrace]) {
  if (!kDebugMode) return;

  debugPrint('[ElevateHer360] $message${error == null ? '' : ': $error'}');

  if (stackTrace != null) {
    debugPrint(stackTrace.toString());
  }
}
