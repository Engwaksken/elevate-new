import 'package:flutter/material.dart';

import '../core/assignment_info.dart';
import '../core/formatters.dart';
import '../core/session_info.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/participant_data_service.dart';
import '../services/reading_time_service.dart';
import '../services/sync_service.dart';
import '../widgets/app_drawer.dart';
import '../widgets/cached_data.dart';
import '../widgets/course_card.dart';
import '../widgets/decorations.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';
import '../widgets/user_avatar.dart';
import 'course_detail_screen.dart';

class _DashboardData {
  const _DashboardData({
    required this.name,
    required this.courses,
    required this.progress,
    required this.counts,
    required this.nextSession,
    required this.lastSync,
    required this.queued,
    required this.summary,
  });

  final String? name;
  final List<Map<String, dynamic>> courses;
  final Map<int, double> progress;
  final Map<String, int> counts;
  final SessionInfo? nextSession;
  final String? lastSync;
  final int queued;
  final ProgressSnapshot summary;
}

/// The handful of numbers shown in the dashboard's progress row.
class ProgressSnapshot {
  const ProgressSnapshot({
    required this.lessonsCompleted,
    required this.lessonsTotal,
    required this.timeSpentSeconds,
    required this.assignmentsSubmitted,
    required this.assignmentsTotal,
  });

  final int lessonsCompleted;
  final int lessonsTotal;
  final int timeSpentSeconds;
  final int assignmentsSubmitted;
  final int assignmentsTotal;

  double get lessonShare =>
      lessonsTotal == 0 ? 0 : (lessonsCompleted / lessonsTotal).clamp(0, 1).toDouble();
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.onOpen});

  /// Opens a destination via the home shell (tab switch or push).
  final ValueChanged<AppDestination> onOpen;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with CachedDataMixin<DashboardScreen, _DashboardData> {
  @override
  void initState() {
    super.initState();
    ParticipantDataService.instance.profileVersion.addListener(reload);
    ReadingTimeService.instance.version.addListener(reload);
  }

  @override
  void dispose() {
    ParticipantDataService.instance.profileVersion.removeListener(reload);
    ReadingTimeService.instance.version.removeListener(reload);
    super.dispose();
  }

  @override
  Future<_DashboardData> readCache() async {
    final db = LocalDatabase.instance;
    final user = await ApiService.instance.currentUser();

    final counts = <String, int>{};
    for (final key in ['assignments', 'mentorship', 'jobs', 'events', 'announcements']) {
      counts[key] = (await db.readCollection(key)).length;
    }

    final sessions = (await db.readCollection('mentorship'))
        .map(SessionInfo.new)
        .where((s) => s.isUpcoming && !s.isCancelled && s.scheduledAt != null)
        .toList()
      ..sort((a, b) => a.scheduledAt!.compareTo(b.scheduledAt!));

    final progress = await db.courseProgress();
    final courses = await db.readCollection('courses');
    // In-progress courses first, then not started, then completed.
    double rank(Map<String, dynamic> c) {
      final p = progress[int.tryParse(c['id']?.toString() ?? '')] ?? 0;
      return p >= 100 ? 1000 : (p > 0 ? -p : 0);
    }
    courses.sort((a, b) => rank(a).compareTo(rank(b)));

    return _DashboardData(
      name: user?['name']?.toString(),
      courses: courses,
      progress: progress,
      counts: counts,
      nextSession: sessions.isEmpty ? null : sessions.first,
      lastSync: await db.getMeta('last_synced_at'),
      queued: await db.pendingOperationCount(),
      summary: await _snapshot(db),
    );
  }

  /// Uses the cached GET /progress summary; falls back to local data
  /// (synced lesson progress and assignments) before it has loaded.
  Future<ProgressSnapshot> _snapshot(LocalDatabase db) async {
    final unsent = await ReadingTimeService.instance.unsentSeconds();
    final cached = await ParticipantDataService.instance.cachedProgress();
    final summary = cached?['summary'];
    if (summary is Map) {
      return ProgressSnapshot(
        lessonsCompleted: asInt(summary['lessons_completed']) ?? 0,
        lessonsTotal: asInt(summary['lessons_total']) ?? 0,
        timeSpentSeconds: (asInt(summary['time_spent_seconds']) ?? 0) + unsent,
        assignmentsSubmitted: asInt(summary['assignments_submitted']) ?? 0,
        assignmentsTotal: asInt(summary['assignments_total']) ?? 0,
      );
    }

    final assignments =
        (await db.readCollection('assignments')).map(AssignmentInfo.new).toList();
    final counts = AssignmentCounts.of(assignments);
    var lessonsTotal = 0;
    for (final c in await db.readCollection('course_details')) {
      final modules = c['modules'];
      if (modules is! List) continue;
      for (final m in modules.whereType<Map>()) {
        final lessons = m['lessons'];
        if (lessons is List) lessonsTotal += lessons.length;
      }
    }
    return ProgressSnapshot(
      lessonsCompleted: (await db.completedLessonIds()).length,
      lessonsTotal: lessonsTotal,
      timeSpentSeconds: unsent,
      assignmentsSubmitted: counts.submitted,
      assignmentsTotal: counts.total,
    );
  }

  void _push(Widget screen) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const LoadingSkeleton(itemHeight: 96);
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final theme = Theme.of(context);
    final d = data!;

    final tiles = [
      StatCard(
        icon: Icons.assignment_outlined,
        value: '${d.counts['assignments'] ?? 0}',
        label: 'Assignments',
        tint: theme.colorScheme.secondaryContainer,
        compact: true,
        onTap: () => widget.onOpen(AppDestination.assignments),
      ),
      StatCard(
        icon: Icons.diversity_3_outlined,
        value: '${d.counts['mentorship'] ?? 0}',
        label: 'Mentorship',
        tint: theme.colorScheme.tertiaryContainer,
        compact: true,
        onTap: () => widget.onOpen(AppDestination.mentorship),
      ),
      StatCard(
        icon: Icons.work_outline_rounded,
        value: '${d.counts['jobs'] ?? 0}',
        label: 'Jobs',
        compact: true,
        onTap: () => widget.onOpen(AppDestination.jobs),
      ),
      StatCard(
        icon: Icons.event_outlined,
        value: '${d.counts['events'] ?? 0}',
        label: 'Events',
        tint: theme.colorScheme.secondaryContainer,
        compact: true,
        onTap: () => widget.onOpen(AppDestination.events),
      ),
      StatCard(
        icon: Icons.campaign_outlined,
        value: '${d.counts['announcements'] ?? 0}',
        label: 'Announcements',
        tint: theme.colorScheme.tertiaryContainer,
        compact: true,
        onTap: () => widget.onOpen(AppDestination.announcements),
      ),
      CareerDocumentsShortcut(onTap: () => widget.onOpen(AppDestination.careerDocuments)),
    ];

    return SoftBackground(
      child: RefreshIndicator(
        onRefresh: refreshFromServer,
        child: ListView(
          padding: AppSpacing.listPadding,
          children: [
            const SizedBox(height: AppSpacing.sm),
            GreetingHeader(
              name: d.name,
              summary: d.summary,
              onTap: () => widget.onOpen(AppDestination.progress),
            ),
            const SizedBox(height: AppSpacing.md),
            ProgressSummaryRow(
              summary: d.summary,
              onTap: () => widget.onOpen(AppDestination.progress),
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              d.lastSync == null
                  ? 'Pull down to download your latest content.'
                  : 'Last updated ${relativeTime(d.lastSync)}'
                      '${d.queued > 0 ? ' · ${d.queued} change${d.queued == 1 ? '' : 's'} waiting to sync' : ''}',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            if (d.nextSession != null) ...[
              const SectionHeader(title: 'Your next mentorship session'),
              Card(
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: theme.colorScheme.tertiaryContainer,
                    foregroundColor: theme.colorScheme.onTertiaryContainer,
                    child: const Icon(Icons.event_available_outlined),
                  ),
                  title: Text(d.nextSession!.title),
                  subtitle: Text([
                    d.nextSession!.mentorName,
                    formatDateTime(d.nextSession!.raw['scheduled_at']),
                  ].whereType<Object>().join(' · ')),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => widget.onOpen(AppDestination.mentorship),
                ),
              ),
            ],
            SectionHeader(
              title: 'Continue learning',
              actionLabel: d.courses.length > 3 ? 'See all' : null,
              onAction: () => widget.onOpen(AppDestination.learning),
            ),
            if (d.courses.isEmpty)
              ValueListenableBuilder<bool>(
                valueListenable: SyncService.instance.syncing,
                builder: (context, syncing, _) =>
                    syncing && (d.lastSync == null || d.lastSync!.isEmpty)
                        // First sign-in: the first sync is still bringing data down.
                        ? const Card(
                            child: ListTile(
                              leading: SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(strokeWidth: 2.5),
                              ),
                              title: Text('Loading your courses…'),
                              subtitle: Text('This only takes a moment the first time you sign in.'),
                            ),
                          )
                        : Card(
                            child: ListTile(
                              leading: const Icon(Icons.menu_book_outlined),
                              title: const Text('No courses yet'),
                              subtitle: const Text(
                                'Your courses will appear here as soon as you are enrolled.',
                              ),
                              onTap: refreshFromServer,
                            ),
                          ),
              )
            else
              for (final course in d.courses.take(3))
                Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.md),
                  child: CourseCard(
                    course: course,
                    progress: d.progress[int.tryParse(course['id']?.toString() ?? '')],
                    onTap: () {
                      final id = int.tryParse(course['id']?.toString() ?? '');
                      if (id == null) return;
                      _push(CourseDetailScreen(courseId: id, initialCourse: course));
                    },
                  ),
                ),
            const SectionHeader(title: 'At a glance'),
            ResponsiveGrid(minTileWidth: 140, children: tiles),
          ],
        ),
      ),
    );
  }
}

class CareerDocumentsShortcut extends StatelessWidget {
  const CareerDocumentsShortcut({super.key, required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => StatCard(
    icon: Icons.description_outlined,
    value: 'CV',
    label: 'Resumes & cover letters',
    compact: true,
    onTap: onTap,
  );
}

const _encouragements = [
  'Every lesson you finish is a step towards your goals.',
  "You're building skills that open doors. Keep going!",
  'Small steps every day add up to big wins.',
  'Proud of you for showing up for yourself today.',
  'Your future self will thank you for this.',
  'Learning is your superpower. Let it shine.',
  'One more lesson today? You have got this.',
];

/// "Good morning, Jane" on the brand gradient, with an encouraging line
/// and overall lesson progress.
class GreetingHeader extends StatelessWidget {
  const GreetingHeader({
    super.key,
    required this.name,
    required this.summary,
    this.onTap,
    this.now,
  });

  final String? name;
  final ProgressSnapshot summary;
  final VoidCallback? onTap;
  final DateTime? now;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final brand = BrandColors.of(context);
    final time = now ?? DateTime.now();
    final first = (name ?? '').trim().split(RegExp(r'\s+')).first;
    final greeting = first.isEmpty ? greetingFor(time) : '${greetingFor(time)}, $first';
    final dayOfYear = time.difference(DateTime(time.year)).inDays;
    final line = _encouragements[dayOfYear % _encouragements.length];
    final percent = (summary.lessonShare * 100).round();

    final progressText = summary.lessonsTotal == 0
        ? 'Your learning journey starts here.'
        : '${summary.lessonsCompleted} of ${summary.lessonsTotal} lessons complete';

    return GradientHeader(
      padding: const EdgeInsets.all(AppSpacing.lg),
      child: Material(
        type: MaterialType.transparency,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          child: Semantics(
            button: onTap != null,
            label: '$greeting. $line $progressText. Open my progress',
            excludeSemantics: true,
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          UserAvatar(name: name, radius: 20, ring: true),
                          const SizedBox(width: AppSpacing.sm),
                          Icon(Icons.wb_sunny_outlined, color: brand.accentFill, size: 20),
                        ],
                      ),
                      const SizedBox(height: AppSpacing.md),
                      Text(
                        greeting,
                        style: theme.textTheme.headlineSmall?.copyWith(color: brand.onHeader),
                      ),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        line,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: brand.onHeader.withValues(alpha: 0.92),
                        ),
                      ),
                      const SizedBox(height: AppSpacing.md),
                      Text(
                        progressText,
                        style: theme.textTheme.labelLarge?.copyWith(color: brand.onHeader),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                ProgressRing(
                  value: summary.lessonShare,
                  size: 76,
                  strokeWidth: 9,
                  trackColor: brand.onHeader.withValues(alpha: 0.25),
                  center: Text(
                    '$percent%',
                    style: theme.textTheme.titleMedium?.copyWith(
                      color: brand.onHeader,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Compact lessons / reading time / assignments row linking to My progress.
class ProgressSummaryRow extends StatelessWidget {
  const ProgressSummaryRow({super.key, required this.summary, required this.onTap});

  final ProgressSnapshot summary;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    Widget stat(IconData icon, String value, String label) => Expanded(
          child: Column(
            children: [
              Icon(icon, color: scheme.secondary, size: 22),
              const SizedBox(height: AppSpacing.xs),
              Text(value, style: theme.textTheme.titleMedium, textAlign: TextAlign.center),
              Text(
                label,
                textAlign: TextAlign.center,
                style: theme.textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ],
          ),
        );

    final lessons = '${summary.lessonsCompleted}/${summary.lessonsTotal}';
    final time = formatDuration(summary.timeSpentSeconds);
    final tasks = '${summary.assignmentsSubmitted}/${summary.assignmentsTotal}';

    return Card(
      child: InkWell(
        onTap: onTap,
        child: Semantics(
          button: true,
          label: 'My progress: ${summary.lessonsCompleted} of ${summary.lessonsTotal} lessons, '
              '${spokenDuration(summary.timeSpentSeconds)} reading, '
              '${summary.assignmentsSubmitted} of ${summary.assignmentsTotal} assignments submitted',
          excludeSemantics: true,
          child: Padding(
            padding: const EdgeInsets.symmetric(
              vertical: AppSpacing.md,
              horizontal: AppSpacing.sm,
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                stat(Icons.auto_stories_outlined, lessons, 'Lessons'),
                stat(Icons.schedule_rounded, time, 'Reading time'),
                stat(Icons.task_alt_rounded, tasks, 'Submitted'),
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.md),
                  child: Icon(Icons.chevron_right, color: scheme.onSurfaceVariant),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
