import 'formatters.dart';

/// Typed views over the participant appointments API
/// (`/api/v1/participant/appointments`). Parsing is defensive: fields may
/// arrive as strings or be missing.
///
/// Times: the API returns ISO 8601 strings carrying the backend offset
/// (Africa/Kampala, `+03:00`). They are parsed to absolute instants and
/// shown in the device's local time with [DateTime.toLocal]. Bookings are
/// sent as UTC ISO 8601 (see [appointmentStartPayload]); the server converts
/// them to its own timezone.

/// Appointment status values from the backend.
abstract final class AppointmentStatus {
  static const pending = 'pending';
  static const approved = 'approved';
  static const declined = 'declined';
  static const proposed = 'rescheduled_proposed';
  static const cancelled = 'cancelled';
  static const completed = 'completed';
}

DateTime? _parseDate(dynamic value) {
  final text = value?.toString().trim() ?? '';
  if (text.isEmpty) return null;
  return DateTime.tryParse(text);
}

String? _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? null : text;
}

bool _bool(dynamic value) => value == true || isTruthy(value);

class AppointmentPerson {
  const AppointmentPerson({required this.id, required this.name});

  final int? id;
  final String name;

  static AppointmentPerson? fromJson(dynamic json) {
    if (json is! Map) return null;
    return AppointmentPerson(
      id: asInt(json['id']),
      name: _text(json['name']) ?? 'Instructor',
    );
  }
}

class AppointmentCourse {
  const AppointmentCourse({required this.id, required this.title});

  final int id;
  final String title;

  static AppointmentCourse? fromJson(dynamic json) {
    if (json is! Map) return null;
    final id = asInt(json['id']);
    if (id == null) return null;
    return AppointmentCourse(
      id: id,
      title: tidyTitle(json['title']?.toString(), fallback: 'Course'),
    );
  }
}

class AppointmentInfo {
  AppointmentInfo(this.raw);

  final Map<String, dynamic> raw;

  static AppointmentInfo? fromJson(dynamic json) =>
      json is Map ? AppointmentInfo(Map<String, dynamic>.from(json)) : null;

  static List<AppointmentInfo> listFrom(dynamic json) {
    if (json is! List) return const [];
    return json.map(fromJson).whereType<AppointmentInfo>().toList();
  }

  int? get id => asInt(raw['id']);

  String get status => _text(raw['status']) ?? AppointmentStatus.pending;

  String get statusLabel =>
      _text(raw['status_label']) ??
      (status == AppointmentStatus.proposed ? 'New time proposed' : humanise(status));

  String get topic => _text(raw['topic']) ?? 'Appointment';
  String? get details => _text(raw['details']);

  /// Absolute instants (compare/convert with `.toLocal()` for display).
  DateTime? get startsAt => _parseDate(raw['starts_at']);
  DateTime? get endsAt => _parseDate(raw['ends_at']);
  DateTime? get proposedStartsAt => _parseDate(raw['proposed_starts_at']);
  DateTime? get proposedEndsAt =>
      _parseDate(raw['proposed_ends_at']) ??
      proposedStartsAt?.add(Duration(minutes: durationMinutes));

  /// Backend timezone name, e.g. `Africa/Kampala`.
  String? get timezone => _text(raw['timezone']);

  int get durationMinutes => asInt(raw['duration_minutes']) ?? 0;

  String get mode => _text(raw['mode']) ?? 'online';
  String get modeLabel =>
      _text(raw['mode_label']) ?? (mode == 'in_person' ? 'In person' : humanise(mode));
  bool get isOnline => mode == 'online';

  String? get location => _text(raw['location']);

  /// Only sent by the API once the appointment is approved.
  String? get meetingUrl => _text(raw['meeting_url']);

  AppointmentPerson? get instructor => AppointmentPerson.fromJson(raw['instructor']);
  AppointmentCourse? get course => AppointmentCourse.fromJson(raw['course']);

  String? get proposalNote => _text(raw['proposal_note']);
  String? get decisionReason => _text(raw['decision_reason']);
  bool get cancelledByMe => _bool(raw['cancelled_by_me']);

  bool get canCancel => _bool(raw['can_cancel']);
  bool get canRespondToProposal => _bool(raw['can_respond_to_proposal']);

  /// Older payloads may lack `can_accept_proposal`; fall back to "can respond
  /// and the proposed time is still in the future".
  bool canAcceptProposal({DateTime? now}) {
    if (raw.containsKey('can_accept_proposal')) return _bool(raw['can_accept_proposal']);
    final proposed = proposedStartsAt;
    return canRespondToProposal &&
        proposed != null &&
        proposed.isAfter(now ?? DateTime.now());
  }

  bool get isApproved => status == AppointmentStatus.approved;
  bool get isProposal => status == AppointmentStatus.proposed;

  /// "Join meeting" is offered only for approved appointments with a link.
  bool get canJoin => isApproved && meetingUrl != null && Uri.tryParse(meetingUrl!) != null;
}

class BookableInstructor {
  const BookableInstructor({required this.id, required this.name, required this.courses});

  final int id;
  final String name;
  final List<AppointmentCourse> courses;

  static BookableInstructor? fromJson(dynamic json) {
    if (json is! Map) return null;
    final id = asInt(json['id']);
    if (id == null) return null;
    final courses = json['courses'] is List
        ? (json['courses'] as List)
            .map(AppointmentCourse.fromJson)
            .whereType<AppointmentCourse>()
            .toList()
        : <AppointmentCourse>[];
    return BookableInstructor(
      id: id,
      name: _text(json['name']) ?? 'Instructor',
      courses: courses,
    );
  }
}

class AppointmentMode {
  const AppointmentMode(this.value, this.label);

  final String value;
  final String label;
}

/// GET /appointments/options.
class AppointmentOptions {
  const AppointmentOptions({
    required this.instructors,
    required this.durations,
    required this.modes,
    this.timezone,
    this.maxOpenRequests = 3,
    this.cancelCutoffHours = 2,
    this.maxDaysAhead = 180,
    this.topicMax = 150,
    this.detailsMax = 2000,
  });

  final List<BookableInstructor> instructors;
  final List<int> durations;
  final List<AppointmentMode> modes;
  final String? timezone;
  final int maxOpenRequests;
  final int cancelCutoffHours;
  final int maxDaysAhead;
  final int topicMax;
  final int detailsMax;

  static const defaultDurations = [15, 30, 45, 60];
  static const defaultModes = [
    AppointmentMode('online', 'Online'),
    AppointmentMode('in_person', 'In person'),
  ];

  factory AppointmentOptions.fromJson(Map<String, dynamic> json) {
    final instructors = json['instructors'] is List
        ? (json['instructors'] as List)
            .map(BookableInstructor.fromJson)
            .whereType<BookableInstructor>()
            .toList()
        : <BookableInstructor>[];

    final durations = json['durations'] is List
        ? (json['durations'] as List).map(asInt).whereType<int>().where((d) => d > 0).toList()
        : <int>[];

    final modes = json['modes'] is List
        ? (json['modes'] as List)
            .whereType<Map>()
            .map((m) {
              final value = _text(m['value']);
              if (value == null) return null;
              return AppointmentMode(value, _text(m['label']) ?? humanise(value));
            })
            .whereType<AppointmentMode>()
            .toList()
        : <AppointmentMode>[];

    final rules = json['rules'] is Map ? json['rules'] as Map : const {};

    return AppointmentOptions(
      instructors: instructors,
      durations: durations.isEmpty ? defaultDurations : durations,
      modes: modes.isEmpty ? defaultModes : modes,
      timezone: _text(json['timezone']),
      maxOpenRequests: asInt(rules['max_open_requests']) ?? 3,
      cancelCutoffHours: asInt(rules['cancel_cutoff_hours']) ?? 2,
      maxDaysAhead: asInt(rules['max_days_ahead']) ?? 180,
      topicMax: asInt(rules['topic_max']) ?? 150,
      detailsMax: asInt(rules['details_max']) ?? 2000,
    );
  }

  BookableInstructor? instructor(int? id) {
    for (final i in instructors) {
      if (i.id == id) return i;
    }
    return null;
  }
}

/// GET /appointments: `{upcoming: [...], past: [...], summary: {...}}`.
class AppointmentList {
  const AppointmentList({required this.upcoming, required this.past, this.summary = const {}});

  final List<AppointmentInfo> upcoming;
  final List<AppointmentInfo> past;
  final Map<String, int> summary;

  factory AppointmentList.fromJson(Map<String, dynamic> json) {
    final summary = <String, int>{};
    if (json['summary'] is Map) {
      (json['summary'] as Map).forEach((key, value) {
        final n = asInt(value);
        if (n != null) summary[key.toString()] = n;
      });
    }
    return AppointmentList(
      upcoming: AppointmentInfo.listFrom(json['upcoming']),
      past: AppointmentInfo.listFrom(json['past']),
      summary: summary,
    );
  }

  int get proposals => summary['proposals'] ?? upcoming.where((a) => a.isProposal).length;
}

// ---------------------------------------------------------------------------
// Pure helpers (unit tested)
// ---------------------------------------------------------------------------

/// The `starts_at` value sent to the API: the chosen local wall-clock time
/// converted to an unambiguous UTC ISO 8601 string, seconds zeroed, e.g.
/// `2026-10-08T07:00:00.000Z` for 10:00 in Kampala.
String appointmentStartPayload(DateTime localStart) {
  final local = localStart.isUtc ? localStart.toLocal() : localStart;
  final trimmed = DateTime(local.year, local.month, local.day, local.hour, local.minute);
  return trimmed.toUtc().toIso8601String();
}

/// Client-side mirror of the server's start-time rules (future, not more
/// than [maxDaysAhead] days ahead). Returns an error message or null. The
/// server remains the authority (it also checks overlaps and open requests).
String? validateAppointmentStart(
  DateTime? start, {
  DateTime? now,
  int maxDaysAhead = 180,
}) {
  if (start == null) return 'Choose a date and start time.';
  final current = now ?? DateTime.now();
  if (!start.isAfter(current)) return 'Choose a date and time in the future.';
  if (start.isAfter(current.add(Duration(days: maxDaysAhead)))) {
    return 'Appointments can be booked up to $maxDaysAhead days ahead.';
  }
  return null;
}

/// Client-side mirror of the open-request limit (pending + proposals).
String? validateOpenRequests(Iterable<AppointmentInfo> appointments, {int max = 3}) {
  final open = appointments
      .where((a) => a.status == AppointmentStatus.pending || a.status == AppointmentStatus.proposed)
      .length;
  if (open >= max) {
    return 'You already have $max requests awaiting a response. '
        'Wait for a reply or cancel one first.';
  }
  return null;
}

/// Client-side check that [start]/[duration] doesn't overlap the
/// participant's own open appointments. Returns a message or null.
String? validateNoOverlap(
  DateTime start,
  int durationMinutes,
  Iterable<AppointmentInfo> appointments,
) {
  final end = start.add(Duration(minutes: durationMinutes));
  const open = {
    AppointmentStatus.pending,
    AppointmentStatus.proposed,
    AppointmentStatus.approved,
  };
  for (final a in appointments) {
    if (!open.contains(a.status)) continue;
    final s = a.startsAt;
    final e = a.endsAt ?? s?.add(Duration(minutes: a.durationMinutes));
    if (s == null || e == null) continue;
    if (s.isBefore(end) && e.isAfter(start)) {
      return 'You already have an appointment request that overlaps this time.';
    }
  }
  return null;
}

const _weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const _monthNames = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
];

String _hm(DateTime d) =>
    '${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';

/// "Thu 8 Oct 2026" in device local time.
String formatAppointmentDay(DateTime instant) {
  final d = instant.toLocal();
  return '${_weekdays[d.weekday - 1]} ${d.day} ${_monthNames[d.month - 1]} ${d.year}';
}

/// "Thu 8 Oct 2026, 10:00 – 10:30" in device local time.
String formatAppointmentRange(DateTime? start, DateTime? end) {
  if (start == null) return 'Time to be confirmed';
  final s = start.toLocal();
  final base = '${formatAppointmentDay(s)}, ${_hm(s)}';
  if (end == null) return base;
  final e = end.toLocal();
  final sameDay = e.year == s.year && e.month == s.month && e.day == s.day;
  return sameDay ? '$base – ${_hm(e)}' : '$base – ${formatAppointmentDay(e)}, ${_hm(e)}';
}

/// Parses the `±HH:MM` (or `Z`) offset of an ISO 8601 string.
Duration? isoOffset(String? iso) {
  if (iso == null) return null;
  final text = iso.trim();
  if (text.endsWith('Z') || text.endsWith('z')) return Duration.zero;
  final match = RegExp(r'([+-])(\d{2}):?(\d{2})$').firstMatch(text);
  if (match == null) return null;
  final minutes = int.parse(match[2]!) * 60 + int.parse(match[3]!);
  return Duration(minutes: match[1] == '-' ? -minutes : minutes);
}

/// When the device is in a different timezone from the backend, a hint like
/// "10:00 in Africa/Kampala" so both sides agree on the meeting time. Null
/// when the device offset matches the backend's.
String? backendTimeHint(String? iso, {String? timezone, Duration? deviceOffset}) {
  final offset = isoOffset(iso);
  final instant = _parseDate(iso);
  if (offset == null || instant == null) return null;
  final device = deviceOffset ?? instant.toLocal().timeZoneOffset;
  if (device == offset) return null;
  final there = instant.toUtc().add(offset);
  return '${_hm(there)} in ${timezone ?? 'the programme timezone'}';
}
