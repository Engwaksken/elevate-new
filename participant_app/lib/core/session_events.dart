import 'dart:async';

/// Broadcasts app-wide session events (e.g. the server rejected the
/// token with a 401). The root widget listens and returns to sign-in.
class SessionEvents {
  SessionEvents._();

  static final SessionEvents instance = SessionEvents._();

  final StreamController<String> _expired =
      StreamController<String>.broadcast();

  bool _handling = false;

  Stream<String> get sessionExpired => _expired.stream;

  void notifyExpired(String message) {
    if (_handling) return;
    _handling = true;
    _expired.add(message);
  }

  /// Called once the user is back on the login screen.
  void reset() => _handling = false;
}
