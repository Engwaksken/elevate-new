import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../widgets/cached_data.dart';
import '../widgets/course_card.dart';
import '../widgets/state_views.dart';
import 'assignments_screen.dart';
import 'cached_list_screen.dart';
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
  });

  final String? name;
  final List<Map<String, dynamic>> courses;
  final Map<int, double> progress;
  final Map<String, int> counts;
  final Map<String, dynamic>? nextSession;
  final String? lastSync;
  final int queued;
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.onOpenTab});

  /// Switches the bottom-navigation tab on the home shell.
  final ValueChanged<int> onOpenTab;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with CachedDataMixin<DashboardScreen, _DashboardData> {
  @override
  Future<_DashboardData> readCache() async {
    final db = LocalDatabase.instance;
    final user = await ApiService.instance.currentUser();

    final counts = <String, int>{};
    for (final key in ['assignments', 'mentorship', 'jobs', 'events', 'announcements']) {
      counts[key] = (await db.readCollection(key)).length;
    }

    final now = DateTime.now();
    final sessions = (await db.readCollection('mentorship'))
        .where((s) =>
            s['status']?.toString() == 'scheduled' &&
            (DateTime.tryParse(s['scheduled_at']?.toString() ?? '')?.isAfter(now) ?? false))
        .toList()
      ..sort((a, b) => a['scheduled_at'].toString().compareTo(b['scheduled_at'].toString()));

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
    final firstName = (d.name ?? '').trim().split(' ').first;

    final tiles = [
      _Tile('Assignments', d.counts['assignments'] ?? 0, Icons.assignment_outlined,
          () => _push(const AssignmentsScreen())),
      _Tile('Mentorship', d.counts['mentorship'] ?? 0, Icons.diversity_3_outlined,
          () => widget.onOpenTab(2)),
      _Tile('Jobs', d.counts['jobs'] ?? 0, Icons.work_outline, () => widget.onOpenTab(3)),
      _Tile('Events', d.counts['events'] ?? 0, Icons.event_outlined,
          () => _push(CachedListScreen.events())),
      _Tile('Announcements', d.counts['announcements'] ?? 0, Icons.campaign_outlined,
          () => _push(CachedListScreen.announcements())),
    ];

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          const SizedBox(height: AppSpacing.sm),
          Semantics(
            header: true,
            child: Text(
              firstName.isEmpty ? 'Welcome back' : 'Welcome back, $firstName',
              style: theme.textTheme.headlineSmall,
            ),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            d.lastSync == null
                ? 'Pull down to download your latest content.'
                : 'Last updated ${relativeTime(d.lastSync)}'
                    '${d.queued > 0 ? ' · ${d.queued} change${d.queued == 1 ? '' : 's'} waiting to sync' : ''}',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          if (d.nextSession != null) ...[
            const SectionHeader(title: 'Next mentorship session'),
            Card(
              child: ListTile(
                leading: const Icon(Icons.event_available_outlined),
                title: Text(tidyTitle(d.nextSession!['title']?.toString(),
                    fallback: 'Mentorship session')),
                subtitle: Text([
                  d.nextSession!['mentor_name'],
                  formatDateTime(d.nextSession!['scheduled_at']),
                ].whereType<Object>().join(' · ')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => widget.onOpenTab(2),
              ),
            ),
          ],
          SectionHeader(
            title: 'Continue learning',
            actionLabel: d.courses.length > 3 ? 'See all' : null,
            onAction: () => widget.onOpenTab(1),
          ),
          if (d.courses.isEmpty)
            Card(
              child: ListTile(
                leading: const Icon(Icons.menu_book_outlined),
                title: const Text('No courses yet'),
                subtitle: const Text('Courses you are enrolled in will appear here.'),
                onTap: refreshFromServer,
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
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: tiles.length,
            gridDelegate: SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 220,
              // Grows with the user's text size so tiles never overflow.
              mainAxisExtent: 60 + 60 * MediaQuery.textScalerOf(context).scale(1),
              crossAxisSpacing: AppSpacing.md,
              mainAxisSpacing: AppSpacing.md,
            ),
            itemBuilder: (context, index) => _TileCard(tile: tiles[index]),
          ),
        ],
      ),
    );
  }
}

class _Tile {
  const _Tile(this.label, this.count, this.icon, this.onTap);

  final String label;
  final int count;
  final IconData icon;
  final VoidCallback onTap;
}

class _TileCard extends StatelessWidget {
  const _TileCard({required this.tile});

  final _Tile tile;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      child: InkWell(
        onTap: tile.onTap,
        child: Semantics(
          button: true,
          label: '${tile.label}: ${tile.count}',
          excludeSemantics: true,
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(tile.icon, color: theme.colorScheme.primary),
                const Spacer(),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text('${tile.count}', style: theme.textTheme.headlineSmall),
                ),
                Text(
                  tile.label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
