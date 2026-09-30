/// Counts "active reading time" for one lesson.
///
/// Time only accumulates while all of these hold:
/// * the lesson screen is visible ([setVisible]),
/// * the app is in the foreground ([setForeground]),
/// * the participant interacted (scroll/tap) within [idleCutoff].
///
/// After [idleCutoff] with no interaction, counting stops at the cutoff
/// point; the next [recordInteraction] starts a new counting segment. Time
/// is measured lazily from a clock, so no timer is needed for correctness.
/// [takeSeconds] drains whole seconds (the sub-second remainder is kept)
/// so callers can persist them without losing or double-counting time.
class ReadingTimeAccumulator {
  ReadingTimeAccumulator({
    DateTime Function()? clock,
    this.idleCutoff = const Duration(minutes: 5),
  }) : _clock = clock ?? DateTime.now {
    _lastInteraction = _clock();
  }

  final DateTime Function() _clock;
  final Duration idleCutoff;

  bool _visible = false;
  bool _foreground = true;
  late DateTime _lastInteraction;

  /// Start of the current counting segment, or null when paused.
  DateTime? _segmentStart;

  int _accumulatedMs = 0;

  bool get isVisible => _visible;
  bool get isForeground => _foreground;

  bool get _eligible => _visible && _foreground;

  /// True if time is being counted right now (not paused, not idle).
  bool get isCounting {
    if (!_eligible || _segmentStart == null) return false;
    return _clock().difference(_lastInteraction) < idleCutoff;
  }

  /// True when counting paused because of inactivity.
  bool get isIdle =>
      _eligible && _clock().difference(_lastInteraction) >= idleCutoff;

  /// Milliseconds counted so far and not yet taken.
  int get pendingMilliseconds => _accumulatedMs + _openSegmentMs(_clock());

  int get pendingSeconds => pendingMilliseconds ~/ 1000;

  int _openSegmentMs(DateTime now) {
    final start = _segmentStart;
    if (start == null || !_eligible) return 0;
    final idleAt = _lastInteraction.add(idleCutoff);
    final end = now.isBefore(idleAt) ? now : idleAt;
    final ms = end.difference(start).inMilliseconds;
    return ms > 0 ? ms : 0;
  }

  /// Closes the open segment up to [now] and reopens it if still eligible.
  void _settle(DateTime now) {
    _accumulatedMs += _openSegmentMs(now);
    _segmentStart = _eligible ? now : null;
  }

  void setVisible(bool visible) {
    if (visible == _visible) return;
    final now = _clock();
    _settle(now);
    _visible = visible;
    if (visible) _lastInteraction = now;
    _segmentStart = _eligible ? now : null;
  }

  void setForeground(bool foreground) {
    if (foreground == _foreground) return;
    final now = _clock();
    _settle(now);
    _foreground = foreground;
    // Returning to the app counts as activity.
    if (foreground) _lastInteraction = now;
    _segmentStart = _eligible ? now : null;
  }

  /// A scroll, tap or key press on the lesson.
  void recordInteraction() {
    final now = _clock();
    _settle(now);
    _lastInteraction = now;
    _segmentStart = _eligible ? now : null;
  }

  /// Removes and returns the whole seconds counted so far.
  int takeSeconds() {
    final now = _clock();
    _settle(now);
    final seconds = _accumulatedMs ~/ 1000;
    _accumulatedMs -= seconds * 1000;
    return seconds;
  }
}
