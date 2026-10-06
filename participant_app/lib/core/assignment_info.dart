import 'formatters.dart';
import 'learning_file.dart';

/// Where an assignment stands for the participant.
enum AssignmentStatus { notSubmitted, submitted, graded, overdue }

/// State of the participant's extension request, if any.
enum ExtensionStatus { none, pending, approved, rejected }

/// Typed view over an Assessment object from GET /assignments (v2 fields:
/// `effective_due_at`, `is_overdue`, `can_submit`, `submissions_count`,
/// `extension_request`). Server flags always win; local fallbacks are only
/// used for older cached payloads that don't carry them.
class AssignmentInfo {
  AssignmentInfo(this.raw, {DateTime? now}) : _now = now;

  final Map<String, dynamic> raw;
  final DateTime? _now;

  DateTime get now => _now ?? DateTime.now();

  int? get id => asInt(raw['id']);

  String get title => tidyTitle(raw['title']?.toString(), fallback: 'Assignment');

  String get typeLabel {
    final value = humanise(raw['type']);
    return value.isEmpty ? 'Assignment' : value;
  }

  String? get courseTitle {
    final course = raw['course'];
    final title = course is Map ? course['title']?.toString().trim() : null;
    return title == null || title.isEmpty ? null : title;
  }

  Map<String, dynamic>? get extensionRequest {
    final value = raw['extension_request'];
    return value is Map ? Map<String, dynamic>.from(value) : null;
  }

  ExtensionStatus get extensionStatus {
    final status = extensionRequest?['status']?.toString().toLowerCase();
    switch (status) {
      case 'pending':
        return ExtensionStatus.pending;
      case 'approved':
        return ExtensionStatus.approved;
      case 'rejected':
      case 'declined':
        return ExtensionStatus.rejected;
      default:
        return ExtensionStatus.none;
    }
  }

  DateTime? get originalDue => DateTime.tryParse(raw['due_at']?.toString() ?? '');

  /// The due date that applies now: the server's `effective_due_at`, else an
  /// approved extension's `approved_due_at`, else `due_at`.
  DateTime? get effectiveDue {
    final effective = DateTime.tryParse(raw['effective_due_at']?.toString() ?? '');
    if (effective != null) return effective;
    if (extensionStatus == ExtensionStatus.approved) {
      final approved = DateTime.tryParse(
        extensionRequest?['approved_due_at']?.toString() ?? '',
      );
      if (approved != null) return approved;
    }
    return originalDue;
  }

  /// True when the due date was moved by an approved extension.
  bool get dueWasExtended {
    final original = originalDue;
    final effective = effectiveDue;
    return original != null && effective != null && effective.isAfter(original);
  }

  int get submissionsCount => asInt(raw['submissions_count']) ?? 0;

  int? get maxAttempts {
    final value = asInt(raw['max_attempts']);
    return value == null || value <= 0 ? null : value;
  }

  /// Server `attempts_remaining`; otherwise derived from `max_attempts`.
  int? get attemptsRemaining {
    final server = asInt(raw['attempts_remaining']);
    if (server != null) return server < 0 ? 0 : server;
    final max = maxAttempts;
    if (max == null) return null;
    final left = max - submissionsCount;
    return left < 0 ? 0 : left;
  }

  bool get attemptsUsedUp => attemptsRemaining == 0;

  bool get hasSubmitted => submissionsCount > 0 || isTruthy(raw['submitted']);

  bool get isGraded {
    if (isTruthy(raw['is_graded']) || raw['graded_at'] != null) return true;
    final status = raw['submission_status']?.toString().toLowerCase();
    if (status == 'graded' || status == 'marked') return true;
    final latest = raw['latest_submission'] ?? raw['latest_attempt'];
    if (latest is Map) {
      final s = latest['status']?.toString().toLowerCase();
      return s == 'graded' || s == 'marked' || latest['graded_at'] != null;
    }
    return false;
  }

  /// Server `is_overdue`; otherwise past the effective due date and not
  /// yet submitted.
  bool get isOverdue {
    if (raw.containsKey('is_overdue') && raw['is_overdue'] != null) {
      return isTruthy(raw['is_overdue']);
    }
    final due = effectiveDue;
    return due != null && due.isBefore(now) && !hasSubmitted;
  }

  /// Server `can_submit`; otherwise not past due and attempts remain.
  bool get canSubmit {
    if (raw.containsKey('can_submit') && raw['can_submit'] != null) {
      return isTruthy(raw['can_submit']);
    }
    final due = effectiveDue;
    if (due != null && due.isBefore(now)) return false;
    return !attemptsUsedUp;
  }

  /// Submission is blocked because the due date passed (show the
  /// "ask for more time" card rather than the submit button).
  bool get blockedByDueDate => isOverdue && !canSubmit;

  /// A queued offline submission was rejected because the due date passed.
  bool get queuedSubmissionRejected =>
      raw['queued_submission_rejected']?.toString() == 'overdue';

  /// Server `can_request_extension` (no pending request, attempts remain,
  /// and overdue or due within 48 hours). Fallback for older payloads.
  bool get canRequestExtension {
    if (raw.containsKey('can_request_extension') && raw['can_request_extension'] != null) {
      return isTruthy(raw['can_request_extension']);
    }
    if (extensionStatus == ExtensionStatus.pending || attemptsUsedUp) return false;
    final due = effectiveDue;
    if (due == null) return false;
    return isOverdue || due.difference(now) <= const Duration(hours: 48);
  }

  AssignmentStatus get status {
    if (isGraded) return AssignmentStatus.graded;
    if (hasSubmitted) return AssignmentStatus.submitted;
    if (isOverdue) return AssignmentStatus.overdue;
    return AssignmentStatus.notSubmitted;
  }

  String get statusLabel => switch (status) {
        AssignmentStatus.notSubmitted => 'Not submitted',
        AssignmentStatus.submitted => 'Submitted',
        AssignmentStatus.graded => 'Graded',
        AssignmentStatus.overdue => 'Overdue',
      };

  String? get extensionLabel => switch (extensionStatus) {
        ExtensionStatus.none => null,
        ExtensionStatus.pending => 'Extension pending',
        ExtensionStatus.approved => 'Extension approved',
        ExtensionStatus.rejected => 'Extension declined',
      };

  /// "Due in 2 days" / "Overdue by 3 days"; null without a due date. Once
  /// work is submitted the date is shown without urgency.
  String? get relativeDue {
    final due = effectiveDue;
    if (due == null) return null;
    if (hasSubmitted && due.isBefore(now)) {
      return 'Was due ${formatDateTime(due.toIso8601String(), withTime: false)}';
    }
    return relativeDueText(due, now: now);
  }

  /// Authenticated path of the first attachment (legacy single-file
  /// fields). Public /storage URLs are ignored.
  String? get legacyAttachmentPath {
    final path = raw['attachment_download_path']?.toString().trim() ?? '';
    if (path.isNotEmpty) return path;
    final url = raw['attachment_url']?.toString().trim() ?? '';
    if (url.isEmpty || url.contains('/storage/')) return null;
    return url;
  }

  /// Instructor files for the brief (`attachments[]`). Older servers only
  /// send `attachment_url`/`attachment_name`, used as a single file.
  List<LearningFileInfo> get attachments {
    final list = raw['attachments'];
    if (list is List) {
      return list
          .whereType<Map>()
          .map((f) => LearningFileInfo(Map<String, dynamic>.from(f)))
          .where((f) => f.downloadPath != null)
          .toList();
    }
    final path = legacyAttachmentPath;
    if (path == null) return const [];
    final name = raw['attachment_name']?.toString().trim() ?? '';
    return [
      LearningFileInfo({
        'id': null,
        'name': name.isEmpty ? '$title instructions' : name,
        if (raw['attachment_downloadable'] != null)
          'downloadable': raw['attachment_downloadable'],
        'download_path': path,
      }),
    ];
  }

  /// Offline key for one of [attachments].
  String attachmentDownloadKey(LearningFileInfo file) {
    final key = 'assessment_${id ?? raw['id']}';
    return file.id == null ? key : '${key}_file_${file.id}';
  }

  /// Files of the latest submission (`latest_submission.files[]`): her own
  /// work, so always downloadable.
  List<LearningFileInfo> get submittedFiles {
    final latest = raw['latest_submission'];
    final list = latest is Map ? latest['files'] : null;
    if (list is! List) return const [];
    return list
        .whereType<Map>()
        .map((f) => LearningFileInfo(Map<String, dynamic>.from(f), ownSubmission: true))
        .where((f) => f.downloadPath != null)
        .toList();
  }

  /// Sort key: overdue and soonest-due first, submitted work last.
  int get sortRank {
    if (status == AssignmentStatus.overdue) return 0;
    if (status == AssignmentStatus.notSubmitted) return 1;
    if (status == AssignmentStatus.submitted) return 2;
    return 3;
  }
}

/// Counts shown at the top of the assignments list.
class AssignmentCounts {
  const AssignmentCounts({
    required this.total,
    required this.submitted,
    required this.overdue,
    required this.graded,
  });

  factory AssignmentCounts.of(Iterable<AssignmentInfo> items) {
    var total = 0, submitted = 0, overdue = 0, graded = 0;
    for (final a in items) {
      total++;
      if (a.hasSubmitted) submitted++;
      if (a.isGraded) graded++;
      if (a.status == AssignmentStatus.overdue) overdue++;
    }
    return AssignmentCounts(
      total: total,
      submitted: submitted,
      overdue: overdue,
      graded: graded,
    );
  }

  final int total;
  final int submitted;
  final int overdue;
  final int graded;
}

/// Client-side validation for an extension reason (API: 10-1000 chars).
String? validateExtensionReason(String? value) {
  final text = value?.trim() ?? '';
  if (text.isEmpty) return 'Tell your instructor why you need more time';
  if (text.length < 10) return 'Please write at least 10 characters';
  if (text.length > 1000) return 'Please keep it under 1000 characters';
  return null;
}
