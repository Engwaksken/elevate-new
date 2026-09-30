import 'formatters.dart';

enum SessionStatus { scheduled, completed, missed, cancelled }

/// Typed view over a mentorship session from GET /mentorship (`sessions`):
/// `id, title, scheduled_at, duration_minutes, status, mentee_attended,
/// mentor_attended, meeting_link, venue, mentor_name`.
class SessionInfo {
  SessionInfo(this.raw, {DateTime? now}) : _now = now;

  /// Attendance can be self-reported for sessions from the past 14 days.
  static const attendanceWindow = Duration(days: 14);

  final Map<String, dynamic> raw;
  final DateTime? _now;

  DateTime get now => _now ?? DateTime.now();

  int? get id => asInt(raw['id']);

  String get title =>
      tidyTitle(raw['title']?.toString(), fallback: 'Mentorship session');

  DateTime? get scheduledAt =>
      DateTime.tryParse(raw['scheduled_at']?.toString() ?? '');

  int get durationMinutes {
    final value = asInt(raw['duration_minutes']);
    return value == null || value <= 0 ? 60 : value;
  }

  DateTime? get endsAt => scheduledAt?.add(Duration(minutes: durationMinutes));

  String? get mentorName {
    for (final key in ['mentor_name', 'mentor']) {
      final value = raw[key];
      if (value is Map && value['name'] != null) return value['name'].toString();
      if (value is String && value.trim().isNotEmpty) return value.trim();
    }
    return null;
  }

  String get meetingLink => raw['meeting_link']?.toString().trim() ?? '';

  String get venue => raw['venue']?.toString().trim() ?? '';

  /// `true`, `false` or `null` (not recorded yet).
  bool? get menteeAttended => _bool(raw['mentee_attended']);

  bool? get mentorAttended => _bool(raw['mentor_attended']);

  static bool? _bool(dynamic value) {
    if (value == null) return null;
    if (value is bool) return value;
    final text = value.toString().toLowerCase();
    if (text == '1' || text == 'true') return true;
    if (text == '0' || text == 'false') return false;
    return null;
  }

  String get _rawStatus => raw['status']?.toString().toLowerCase() ?? '';

  bool get isCancelled =>
      _rawStatus == 'cancelled' || _rawStatus == 'canceled';

  /// Still to come (or in progress right now).
  bool get isUpcoming {
    final end = endsAt;
    return end != null && end.isAfter(now);
  }

  SessionStatus get status {
    if (isCancelled) return SessionStatus.cancelled;
    if (_rawStatus == 'completed' || _rawStatus == 'held') {
      return SessionStatus.completed;
    }
    if (_rawStatus == 'missed' || _rawStatus == 'no_show') {
      return SessionStatus.missed;
    }
    if (!isUpcoming) {
      if (menteeAttended == true) return SessionStatus.completed;
      if (menteeAttended == false) return SessionStatus.missed;
    }
    return SessionStatus.scheduled;
  }

  String get statusLabel => switch (status) {
        SessionStatus.scheduled => 'Scheduled',
        SessionStatus.completed => 'Completed',
        SessionStatus.missed => 'Missed',
        SessionStatus.cancelled => 'Cancelled',
      };

  /// Whether she may record or change attendance now. Uses the server's
  /// `can_confirm_attendance` (GET /mentorship); /sync payloads lack it, so
  /// fall back to: started, not cancelled, within the last 14 days.
  bool get canConfirmAttendance {
    if (raw.containsKey('can_confirm_attendance') && raw['can_confirm_attendance'] != null) {
      return isTruthy(raw['can_confirm_attendance']);
    }
    final start = scheduledAt;
    if (start == null || isCancelled || start.isAfter(now)) return false;
    return now.difference(start) <= attendanceWindow;
  }

  /// Show "Did you attend?" for recent past sessions without an answer.
  bool get needsAttendanceAnswer => menteeAttended == null && canConfirmAttendance;

  /// She answered already and may still change it (14-day window).
  bool get canChangeAttendance => menteeAttended != null && canConfirmAttendance;
}

/// Attendance summary for past, non-cancelled sessions (attended means
/// `mentee_attended == true`, as in GET /progress).
class AttendanceSummary {
  const AttendanceSummary({
    required this.attended,
    required this.total,
    required this.missed,
    required this.upcoming,
  });

  factory AttendanceSummary.of(Iterable<SessionInfo> sessions) {
    var attended = 0, total = 0, missed = 0, upcoming = 0;
    for (final s in sessions) {
      if (s.isCancelled) continue;
      if (s.isUpcoming) {
        upcoming++;
        continue;
      }
      total++;
      // Same definitions as GET /progress.
      if (s.menteeAttended == true) {
        attended++;
      } else if (s.menteeAttended == false || s._rawStatus == 'missed') {
        missed++;
      }
    }
    return AttendanceSummary(
      attended: attended,
      total: total,
      missed: missed,
      upcoming: upcoming,
    );
  }

  final int attended;
  final int total;
  final int missed;
  final int upcoming;

  /// 0-100, or null when there are no past sessions.
  int? get ratePercent => total == 0 ? null : (attended / total * 100).round();
}
