import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/reading_time_service.dart';
import '../services/sync_service.dart';
import '../widgets/decorations.dart';
import '../widgets/feedback.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';

/// Typed view over GET /progress.
class ProgressData {
  ProgressData(this.raw, {this.unsentSeconds = 0});

  final Map<String, dynamic> raw;

  /// Reading time on this device not yet acknowledged by the server.
  final int unsentSeconds;

  Map<String, dynamic> get summary {
    final value = raw['summary'];
    return value is Map ? Map<String, dynamic>.from(value) : const {};
  }

  int n(String key) => asInt(summary[key]) ?? 0;

  int get timeSpentSeconds => n('time_spent_seconds') + unsentSeconds;

  List<Map<String, dynamic>> get courses {
    final value = raw['courses'];
    if (value is! List) return const [];
    return value.whereType<Map>().map(Map<String, dynamic>.from).toList();
  }

  List<Map<String, dynamic>> get activity {
    final value = raw['recent_activity'];
    if (value is! List) return const [];
    final items = value.whereType<Map>().map(Map<String, dynamic>.from).toList();
    items.sort((a, b) => (b['at']?.toString() ?? '').compareTo(a['at']?.toString() ?? ''));
    return items;
  }

  bool get isEmpty =>
      n('courses_enrolled') == 0 &&
      n('lessons_total') == 0 &&
      n('assignments_total') == 0 &&
      n('mentorship_sessions_total') == 0 &&
      courses.isEmpty;

  String? get cachedAt => raw['cached_at']?.toString();
}

class ProgressScreen extends StatefulWidget {
  const ProgressScreen({super.key});

  @override
  State<ProgressScreen> createState() => _ProgressScreenState();
}

class _ProgressScreenState extends State<ProgressScreen> {
  ProgressData? _data;
  bool _loading = true;
  Object? _error;

  @override
  void initState() {
    super.initState();
    SyncService.instance.dataVersion.addListener(_readCache);
    ReadingTimeService.instance.version.addListener(_readCache);
    _load();
  }

  @override
  void dispose() {
    SyncService.instance.dataVersion.removeListener(_readCache);
    ReadingTimeService.instance.version.removeListener(_readCache);
    super.dispose();
  }

  Future<void> _readCache() async {
    final cached = await ParticipantDataService.instance.cachedProgress();
    final unsent = await ReadingTimeService.instance.unsentSeconds();
    if (!mounted) return;
    setState(() {
      if (cached != null) _data = ProgressData(cached, unsentSeconds: unsent);
      _loading = false;
    });
  }

  Future<void> _load() async {
    await _readCache();
    await _refresh(quiet: true);
  }

  Future<void> _refresh({bool quiet = false}) async {
    if (!await SyncService.instance.isOnline()) {
      if (!mounted) return;
      if (_data == null) {
        setState(() {
          _error = AppException.offline;
          _loading = false;
        });
      } else if (!quiet) {
        showAppSnackBar(context, "You're offline. Showing your progress saved on this device.");
      }
      return;
    }

    try {
      // Send unsent reading time first so the totals include it.
      await ReadingTimeService.instance.flush();
      await ParticipantDataService.instance.refreshProgress();
      if (mounted) setState(() => _error = null);
      await _readCache();
    } catch (error) {
      if (!mounted) return;
      if (_data == null) {
        setState(() {
          _error = error;
          _loading = false;
        });
      } else if (!quiet) {
        showErrorSnackBar(context, error, onRetry: _refresh);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My progress')),
      body: SafeArea(top: false, child: _body()),
    );
  }

  Widget _body() {
    if (_loading && _data == null) return const LoadingSkeleton(itemHeight: 110);
    if (_data == null) {
      return RefreshIndicator(
        onRefresh: _refresh,
        child: ErrorState(
          error: _error ?? AppException.offline,
          title: AppException.from(_error ?? AppException.offline).kind == AppErrorKind.offline
              ? 'Your progress will appear here'
              : null,
          onRetry: () {
            setState(() => _loading = true);
            _refresh();
          },
        ),
      );
    }
    return RefreshIndicator(onRefresh: _refresh, child: ProgressView(data: _data!));
  }
}

/// The progress page content (separate so it can be tested without I/O).
class ProgressView extends StatelessWidget {
  const ProgressView({super.key, required this.data});

  final ProgressData data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    if (data.isEmpty) {
      return const EmptyState(
        icon: Icons.insights_outlined,
        title: 'Your progress story starts soon',
        message: 'Once you begin your courses, your lessons, reading time and '
            'achievements will be celebrated here.',
      );
    }

    final lessonsDone = data.n('lessons_completed');
    final lessonsTotal = data.n('lessons_total');
    final submitted = data.n('assignments_submitted');
    final assignmentsTotal = data.n('assignments_total');
    final attended = data.n('mentorship_sessions_attended');
    final sessionsTotal = data.n('mentorship_sessions_total');
    double share(int a, int b) => b == 0 ? 0 : a / b;

    final stats = <Widget>[
      StatCard(
        icon: Icons.auto_stories_outlined,
        value: '$lessonsDone/$lessonsTotal',
        label: 'Lessons completed',
        progress: share(lessonsDone, lessonsTotal),
      ),
      StatCard(
        icon: Icons.schedule_rounded,
        value: formatDuration(data.timeSpentSeconds),
        label: 'Reading time',
        tint: scheme.secondaryContainer,
      ),
      StatCard(
        icon: Icons.task_alt_rounded,
        value: '$submitted/$assignmentsTotal',
        label: 'Assignments submitted',
        progress: share(submitted, assignmentsTotal),
        tint: scheme.secondaryContainer,
      ),
      StatCard(
        icon: Icons.grade_outlined,
        value: '${data.n('assignments_graded')}',
        label: 'Graded',
        caption: data.n('assignments_pending') > 0
            ? '${data.n('assignments_pending')} waiting for a grade'
            : null,
        tint: scheme.tertiaryContainer,
      ),
      StatCard(
        icon: Icons.event_busy_outlined,
        value: '${data.n('assignments_overdue')}',
        label: 'Overdue',
        caption: data.n('extension_requests_pending') > 0
            ? '${data.n('extension_requests_pending')} extension request pending'
            : null,
        tint: scheme.errorContainer,
      ),
      StatCard(
        icon: Icons.diversity_3_outlined,
        value: '$attended/$sessionsTotal',
        label: 'Sessions attended',
        progress: share(attended, sessionsTotal),
        tint: scheme.tertiaryContainer,
      ),
      StatCard(
        icon: Icons.event_note_outlined,
        value: '${data.n('mentorship_sessions_missed')}',
        label: 'Sessions missed',
      ),
      StatCard(
        icon: Icons.upcoming_outlined,
        value: '${data.n('mentorship_sessions_upcoming')}',
        label: 'Upcoming sessions',
        tint: scheme.secondaryContainer,
      ),
      if (data.summary.containsKey('events_attended'))
        StatCard(
          icon: Icons.celebration_outlined,
          value: '${data.n('events_attended')}',
          label: 'Events attended',
          tint: scheme.tertiaryContainer,
        ),
    ];

    final courses = data.courses;
    final activity = data.activity;

    return ListView(
      padding: AppSpacing.listPadding,
      children: [
        const SizedBox(height: AppSpacing.sm),
        _Hero(
          lessonsDone: lessonsDone,
          lessonsTotal: lessonsTotal,
          coursesCompleted: data.n('courses_completed'),
          coursesEnrolled: data.n('courses_enrolled'),
        ),
        const SectionHeader(title: 'Highlights'),
        ResponsiveGrid(minTileWidth: 150, children: stats),
        const SectionHeader(title: 'Your courses'),
        if (courses.isEmpty)
          const Card(
            child: ListTile(
              leading: Icon(Icons.menu_book_outlined),
              title: Text('No course progress yet'),
              subtitle: Text('Open a lesson to get started.'),
            ),
          )
        else
          for (final c in courses) ...[
            _CourseProgressCard(course: c),
            const SizedBox(height: AppSpacing.sm),
          ],
        const SectionHeader(title: 'Recent activity'),
        if (activity.isEmpty)
          const Card(
            child: ListTile(
              leading: Icon(Icons.history_rounded),
              title: Text('Nothing here yet'),
              subtitle: Text('Your lessons, submissions and sessions will show up here.'),
            ),
          )
        else
          Card(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Column(
                children: [
                  for (var i = 0; i < activity.length && i < 20; i++)
                    _TimelineTile(
                      item: activity[i],
                      isLast: i == activity.length - 1 || i == 19,
                    ),
                ],
              ),
            ),
          ),
        if (data.cachedAt != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.md),
            child: Text(
              'Updated ${relativeTime(data.cachedAt)}',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ),
      ],
    );
  }
}

class _Hero extends StatelessWidget {
  const _Hero({
    required this.lessonsDone,
    required this.lessonsTotal,
    required this.coursesCompleted,
    required this.coursesEnrolled,
  });

  final int lessonsDone;
  final int lessonsTotal;
  final int coursesCompleted;
  final int coursesEnrolled;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final brand = BrandColors.of(context);
    final share = lessonsTotal == 0 ? 0.0 : lessonsDone / lessonsTotal;
    final percent = (share * 100).round();
    final message = percent >= 100
        ? 'You did it! Every lesson is complete.'
        : percent >= 50
            ? "More than halfway there. You're doing brilliantly!"
            : percent > 0
                ? "Great start! Keep the momentum going."
                : 'Your first lesson is waiting for you.';

    return GradientHeader(
      seed: 2,
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: Semantics(
        container: true,
        label: 'Overall $percent percent of lessons complete. $message',
        excludeSemantics: true,
        child: Row(
          children: [
            ProgressRing(
              value: share,
              size: 88,
              strokeWidth: 10,
              trackColor: brand.onHeader.withValues(alpha: 0.25),
              center: Text(
                '$percent%',
                style: theme.textTheme.titleLarge?.copyWith(color: brand.onHeader),
              ),
            ),
            const SizedBox(width: AppSpacing.lg),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    message,
                    style: theme.textTheme.titleMedium?.copyWith(color: brand.onHeader),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    '$coursesCompleted of $coursesEnrolled courses completed',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: brand.onHeader.withValues(alpha: 0.92),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CourseProgressCard extends StatelessWidget {
  const _CourseProgressCard({required this.course});

  final Map<String, dynamic> course;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final title = tidyTitle(course['title']?.toString(), fallback: 'Course');
    final percent = (double.tryParse(course['progress_percent']?.toString() ?? '') ?? 0)
        .clamp(0, 100)
        .toDouble();
    final done = asInt(course['lessons_completed']) ?? 0;
    final total = asInt(course['lessons_total']) ?? 0;
    final seconds = asInt(course['time_spent_seconds']) ?? 0;
    final last = course['last_activity_at'];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Semantics(
          container: true,
          label: '$title. ${percent.round()} percent complete. $done of $total lessons. '
              'Time spent ${spokenDuration(seconds)}.',
          excludeSemantics: true,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(child: Text(title, style: theme.textTheme.titleMedium)),
                  const SizedBox(width: AppSpacing.sm),
                  Text('${percent.round()}%', style: theme.textTheme.titleMedium),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              ProgressBar(value: percent / 100),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.xs,
                children: [
                  if (humanise(course['status']).isNotEmpty)
                    InfoChip(
                      label: humanise(course['status']),
                      icon: Icons.flag_outlined,
                      emphasis: true,
                    ),
                  InfoChip(label: '$done/$total lessons', icon: Icons.auto_stories_outlined),
                  InfoChip(label: 'Time spent: ${formatDuration(seconds)}', icon: Icons.schedule_rounded),
                  if (last != null && relativeTime(last).isNotEmpty)
                    InfoChip(label: 'Active ${relativeTime(last).toLowerCase()}', icon: Icons.history_rounded),
                ],
              ),
              if (percent >= 100) ...[
                const SizedBox(height: AppSpacing.sm),
                Row(
                  children: [
                    Icon(Icons.emoji_events_outlined, color: scheme.secondary, size: 20),
                    const SizedBox(width: AppSpacing.xs),
                    Expanded(
                      child: Text(
                        'Course complete. Well done!',
                        style: theme.textTheme.labelLarge?.copyWith(color: scheme.secondary),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _TimelineTile extends StatelessWidget {
  const _TimelineTile({required this.item, required this.isLast});

  final Map<String, dynamic> item;
  final bool isLast;

  static IconData _icon(String type) {
    final t = type.toLowerCase();
    if (t.contains('lesson')) return Icons.auto_stories_outlined;
    if (t.contains('submi') || t.contains('assign')) return Icons.upload_file_outlined;
    if (t.contains('grade')) return Icons.grade_outlined;
    if (t.contains('mentor') || t.contains('session')) return Icons.diversity_3_outlined;
    if (t.contains('course')) return Icons.school_outlined;
    if (t.contains('extension')) return Icons.more_time_outlined;
    return Icons.bolt_outlined;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final brand = BrandColors.of(context);
    final type = item['type']?.toString() ?? '';
    final title = tidyTitle(item['title']?.toString(), fallback: humanise(type));
    final when = relativeTime(item['at']);
    final kind = humanise(type);

    return Semantics(
      container: true,
      label: [title, if (kind.isNotEmpty) kind, if (when.isNotEmpty) when].join(', '),
      excludeSemantics: true,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SizedBox(
              width: 56,
              child: Column(
                children: [
                  const SizedBox(height: AppSpacing.sm),
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: scheme.secondaryContainer,
                    child: Icon(_icon(type), size: 18, color: scheme.onSecondaryContainer),
                  ),
                  if (!isLast)
                    Expanded(
                      child: Container(
                        width: 2,
                        margin: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
                        color: brand.accentFill.withValues(alpha: 0.35),
                      ),
                    ),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(0, AppSpacing.sm, AppSpacing.lg, AppSpacing.md),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: theme.textTheme.bodyLarge?.copyWith(height: 1.3)),
                    Text(
                      [if (kind.isNotEmpty) kind, if (when.isNotEmpty) when].join(' · '),
                      style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
