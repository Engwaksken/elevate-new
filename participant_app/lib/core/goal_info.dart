import 'formatters.dart';

/// Priority of a personal goal.
enum GoalPriority { low, medium, high }

/// Typed view over a participant goal from GET /goals, POST /goals and the
/// progress endpoints. Parsing is defensive because cached payloads may be
/// partial or carry numbers as strings.
class GoalInfo {
  GoalInfo(this.raw);

  final Map<String, dynamic> raw;

  int? get id => asInt(raw['id']);

  String get title => tidyTitle(raw['title']?.toString(), fallback: 'Goal');

  String? get description {
    final text = raw['description']?.toString().trim() ?? '';
    return text.isEmpty ? null : text;
  }

  String get category {
    final value = raw['category']?.toString().trim() ?? '';
    return value.isEmpty ? 'career' : value;
  }

  String get categoryLabel {
    final label = humanise(category);
    return label.isEmpty ? 'Career' : label;
  }

  String? get unit {
    final text = raw['unit']?.toString().trim() ?? '';
    return text.isEmpty ? null : text;
  }

  /// Feedback left by her mentor, when present.
  String? get mentorComment {
    final text = raw['mentor_comment']?.toString().trim() ?? '';
    return text.isEmpty ? null : text;
  }

  DateTime? get mentorReviewedAt => _parseDate(raw['mentor_reviewed_at']);

  double? get targetValue => asDouble(raw['target_value']);
  double? get currentValue => asDouble(raw['current_value']);
  double? get baselineValue => asDouble(raw['baseline_value']);

  double get progressPercent {
    final value = asDouble(raw['progress_percent']) ?? 0;
    return value.clamp(0, 100).toDouble();
  }

  /// The target as a fraction 0..1 for progress widgets.
  double get progressFraction => progressPercent / 100;

  DateTime? get startDate => _parseDate(raw['start_date']);
  DateTime? get targetDate => _parseDate(raw['target_date']);

  GoalPriority get priority {
    switch (raw['priority']?.toString().toLowerCase()) {
      case 'high':
        return GoalPriority.high;
      case 'low':
        return GoalPriority.low;
      default:
        return GoalPriority.medium;
    }
  }

  String get priorityLabel => switch (priority) {
        GoalPriority.high => 'High priority',
        GoalPriority.medium => 'Medium priority',
        GoalPriority.low => 'Low priority',
      };

  String get status {
    final value = raw['status']?.toString().trim() ?? '';
    return value.isEmpty ? 'not_started' : value;
  }

  String get statusLabel => switch (status) {
        'completed' => 'Completed',
        'in_progress' => 'In progress',
        'cancelled' => 'Cancelled',
        _ => 'Not started',
      };

  bool get isCompleted => status == 'completed' || progressPercent >= 100;

  /// e.g. "3 / 10 applications". Null when the goal has no measured target.
  String? get valueLabel {
    final target = targetValue;
    if (target == null) return null;
    final current = currentValue ?? 0;
    final suffix = unit == null ? '' : ' ${unit!}';
    return '${formatNumber(current)} / ${formatNumber(target)}$suffix';
  }

  /// "Due 12 Mar 2026", null when no target date.
  String? get dueLabel {
    final date = targetDate;
    if (date == null) return null;
    return 'Due ${formatDateTime(date.toIso8601String(), withTime: false)}';
  }

  static DateTime? _parseDate(dynamic value) {
    final text = value?.toString().trim() ?? '';
    if (text.isEmpty) return null;
    final parsed = DateTime.tryParse(text);
    if (parsed != null) return parsed;
    final match = RegExp(r'^(\d{4})-(\d{2})-(\d{2})').firstMatch(text);
    if (match == null) return null;
    return DateTime(
      int.parse(match.group(1)!),
      int.parse(match.group(2)!),
      int.parse(match.group(3)!),
    );
  }
}

/// Counts shown at the top of the goals screen.
class GoalSummary {
  const GoalSummary({
    required this.total,
    required this.inProgress,
    required this.completed,
    required this.averageProgress,
  });

  factory GoalSummary.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const GoalSummary(total: 0, inProgress: 0, completed: 0, averageProgress: 0);
    return GoalSummary(
      total: asInt(json['total']) ?? 0,
      inProgress: asInt(json['in_progress']) ?? 0,
      completed: asInt(json['completed']) ?? 0,
      averageProgress: asDouble(json['average_progress']) ?? 0,
    );
  }

  factory GoalSummary.of(Iterable<GoalInfo> goals) {
    var total = 0, inProgress = 0, completed = 0;
    var progress = 0.0;
    for (final goal in goals) {
      total++;
      progress += goal.progressPercent;
      if (goal.isCompleted) {
        completed++;
      } else if (goal.status == 'in_progress') {
        inProgress++;
      }
    }
    return GoalSummary(
      total: total,
      inProgress: inProgress,
      completed: completed,
      averageProgress: total == 0 ? 0 : progress / total,
    );
  }

  final int total;
  final int inProgress;
  final int completed;
  final double averageProgress;
}
