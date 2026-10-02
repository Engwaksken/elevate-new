import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/lesson_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/lesson_progress_service.dart';
import '../services/local_database.dart';
import '../services/participant_data_service.dart';
import '../services/reading_time_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';
import 'lesson_detail_screen.dart';

class CourseDetailScreen extends StatefulWidget {
  const CourseDetailScreen({
    super.key,
    required this.courseId,
    required this.initialCourse,
  });

  final int courseId;
  final Map<String, dynamic> initialCourse;

  @override
  State<CourseDetailScreen> createState() => _CourseDetailScreenState();
}

class _CourseDetailScreenState extends State<CourseDetailScreen> {
  Map<String, dynamic>? _course;
  bool _loading = true;
  Object? _error;
  Set<int> _completed = {};
  Set<String> _downloaded = {};
  int _timeSpentSeconds = 0;

  String get _cacheKey => 'course_${widget.courseId}';

  @override
  void initState() {
    super.initState();
    ReadingTimeService.instance.version.addListener(_refreshLocalState);
    _load();
  }

  @override
  void dispose() {
    ReadingTimeService.instance.version.removeListener(_refreshLocalState);
    super.dispose();
  }

  /// Best known course time: the larger of GET /progress's course total
  /// and the per-lesson totals, each including unsent seconds.
  Future<int> _courseSeconds() async {
    final perLesson = <int, int>{};
    for (final l in _allLessons) {
      final id = l.id;
      if (id == null) continue;
      final progress = l.raw['progress'];
      perLesson[id] = progress is Map ? asInt(progress['time_spent_seconds']) ?? 0 : 0;
    }
    final fromLessons = await ReadingTimeService.instance.lessonsTotal(perLesson);
    final server = (await ParticipantDataService.instance.courseTimeSeconds())[widget.courseId];
    if (server == null) return fromLessons;
    final unsent = await ReadingTimeService.instance.unsentSeconds(lessonIds: perLesson.keys);
    final fromProgress = server + unsent;
    return fromProgress > fromLessons ? fromProgress : fromLessons;
  }

  List<Map<String, dynamic>> get _modules {
    final modules = _course?['modules'];
    if (modules is! List) return const [];
    return modules.whereType<Map>().map(Map<String, dynamic>.from).toList();
  }

  List<LessonInfo> _lessonsOf(Map<String, dynamic> module) {
    final lessons = module['lessons'];
    if (lessons is! List) return const [];
    final locked = isTruthy(module['is_locked']);
    return lessons
        .whereType<Map>()
        .map((l) => LessonInfo(Map<String, dynamic>.from(l), moduleLocked: locked))
        .toList();
  }

  List<LessonInfo> get _allLessons => [for (final m in _modules) ..._lessonsOf(m)];

  Future<void> _refreshLocalState() async {
    final serverDone = _allLessons
        .where((l) => l.completedOnServer && l.id != null)
        .map((l) => l.id!);
    final completed =
        await LocalDatabase.instance.completedLessonIds(alsoCompleted: serverDone);

    final downloads = await LocalDatabase.instance.downloads();
    final keys = downloads.map((row) => row['download_key'].toString()).toSet();
    final seconds = await _courseSeconds();

    if (mounted) {
      setState(() {
        _completed = completed;
        _downloaded = keys;
        _timeSpentSeconds = seconds;
      });
    }
  }

  Future<void> _load() async {
    final cached =
        await LocalDatabase.instance.readItem('course_details', _cacheKey);

    if (cached != null && mounted) {
      setState(() {
        _course = cached;
        _loading = false;
      });
      await _refreshLocalState();
    }

    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        setState(() {
          _loading = false;
          if (_course == null) _error = AppException.offline;
        });
      }
      return;
    }

    try {
      final response = await ApiService.instance.course(widget.courseId);
      final raw = response['course'];
      if (raw is! Map) {
        throw const AppException(AppErrorKind.unknown, AppException.genericMessage);
      }
      final course = Map<String, dynamic>.from(raw);

      await LocalDatabase.instance.cacheItem(
        collection: 'course_details',
        itemId: _cacheKey,
        payload: course,
      );

      final enrolment = course['enrolment'];
      if (enrolment is Map) {
        final percent =
            double.tryParse(enrolment['progress_percent']?.toString() ?? '');
        if (percent != null) {
          await LocalDatabase.instance.setCourseProgress(
            widget.courseId,
            percent,
            status: enrolment['status']?.toString(),
          );
        }
      }

      if (mounted) {
        setState(() {
          _course = course;
          _error = null;
          _loading = false;
        });
      }
      await _refreshLocalState();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (_course == null) _error = error;
      });
      if (_course != null) showErrorSnackBar(context, error, onRetry: _load);
    }
  }

  double get _percent {
    final lessons = _allLessons.where((l) => l.id != null).toList();
    if (lessons.isEmpty) return 0;
    final done = lessons.where((l) => _completed.contains(l.id)).length;
    return done / lessons.length * 100;
  }

  Future<void> _openLesson(LessonInfo lesson) async {
    if (lesson.isLocked) {
      showAppSnackBar(context, _lockedMessage);
      return;
    }

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => LessonDetailScreen(
          lesson: lesson.raw,
          courseId: widget.courseId,
          courseTitle: tidyTitle(_course?['title']?.toString(), fallback: 'Course'),
          courseLessonIds: _allLessons.map((l) => l.id).whereType<int>().toSet(),
        ),
      ),
    );
    await _refreshLocalState();
  }

  Future<void> _toggleComplete(LessonInfo lesson) async {
    final id = lesson.id;
    if (id == null) return;
    final completed = !_completed.contains(id);

    final ids = _allLessons.map((l) => l.id).whereType<int>().toSet();
    final after = {..._completed.intersection(ids)};
    completed ? after.add(id) : after.remove(id);
    final percent = ids.isEmpty ? 0.0 : after.length / ids.length * 100;

    setState(() => completed ? _completed.add(id) : _completed.remove(id));

    try {
      final result = await LessonProgressService.instance.setCompleted(
        lessonId: id,
        completed: completed,
        courseId: widget.courseId,
        localCoursePercent: percent,
      );
      if (!mounted) return;
      showAppSnackBar(
        context,
        result == ProgressSaveResult.synced
            ? (completed ? 'Lesson marked as complete.' : 'Lesson marked as not complete.')
            : "Saved on this device. It will sync when you're back online.",
      );
    } catch (error) {
      if (!mounted) return;
      showErrorSnackBar(context, error);
    }
    await _refreshLocalState();
  }

  Future<void> _download(LessonInfo lesson) async {
    final id = lesson.id;
    final path = lesson.downloadPath;
    if (id == null || path == null) return;

    showAppSnackBar(context, 'Downloading "${lesson.title}"…');
    try {
      await DownloadService.instance.download(
        key: DownloadService.lessonKey(id),
        apiPath: path,
        title: lesson.title,
        fileName: lesson.fileName,
      );
      if (mounted) showAppSnackBar(context, 'Saved for offline use.');
    } catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error, onRetry: () => _download(lesson));
      }
    }
    await _refreshLocalState();
  }

  static const _lockedMessage =
      'This lesson is locked. Complete the previous module or wait for your instructor to release it.';

  @override
  Widget build(BuildContext context) {
    final title = tidyTitle(
      (_course ?? widget.initialCourse)['title']?.toString(),
      fallback: 'Course',
    );

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _course == null) {
      return const LoadingSkeleton(itemHeight: 64);
    }

    if (_error != null && _course == null) {
      final offline = AppException.from(_error!).kind == AppErrorKind.offline;
      return ErrorState(
        error: _error!,
        title: offline ? 'Course not downloaded yet' : null,
        onRetry: () {
          setState(() {
            _loading = true;
            _error = null;
          });
          _load();
        },
      );
    }

    final theme = Theme.of(context);
    final course = _course ?? widget.initialCourse;
    final summary = plainParagraphs(course['summary']?.toString()).join('\n\n');
    final modules = _modules;
    final lessonCount = _allLessons.length;
    final doneCount =
        _allLessons.where((l) => l.id != null && _completed.contains(l.id)).length;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          if (course['enrolment'] is Map && course['enrolment']['enrolment_code'] != null)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: SelectableText('Enrollment ID: ${course['enrolment']['enrolment_code']}'),
            ),
          if (summary.isNotEmpty) ...[
            Text(summary, style: theme.textTheme.bodyLarge),
            const SizedBox(height: AppSpacing.lg),
          ],
          if (lessonCount > 0)
            _ProgressSummary(
              percent: _percent,
              done: doneCount,
              total: lessonCount,
              seconds: _timeSpentSeconds,
            ),
          if (modules.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: AppSpacing.xl),
              child: EmptyState(
                icon: Icons.view_module_outlined,
                title: 'No lessons published yet',
                message: 'Your instructor has not published any modules. Pull down to check again.',
              ),
            ),
          for (var i = 0; i < modules.length; i++)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.md),
              child: _ModuleCard(
                index: i,
                module: modules[i],
                lessons: _lessonsOf(modules[i]),
                completed: _completed,
                downloaded: _downloaded,
                onOpen: _openLesson,
                onToggleComplete: _toggleComplete,
                onDownload: _download,
              ),
            ),
        ],
      ),
    );
  }
}

class _ProgressSummary extends StatelessWidget {
  const _ProgressSummary({
    required this.percent,
    required this.done,
    required this.total,
    required this.seconds,
  });

  final double percent;
  final int done;
  final int total;
  final int seconds;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Semantics(
          label: 'Course progress: $done of $total lessons complete. '
              'Time spent: ${spokenDuration(seconds)}',
          excludeSemantics: true,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text('Your progress', style: theme.textTheme.titleMedium),
                  ),
                  Text('${percent.round()}%', style: theme.textTheme.titleMedium),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              ProgressBar(value: percent / 100),
              const SizedBox(height: AppSpacing.sm),
              Text(
                '$done of $total lessons complete · Time spent: ${formatDuration(seconds)}',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({
    required this.index,
    required this.module,
    required this.lessons,
    required this.completed,
    required this.downloaded,
    required this.onOpen,
    required this.onToggleComplete,
    required this.onDownload,
  });

  final int index;
  final Map<String, dynamic> module;
  final List<LessonInfo> lessons;
  final Set<int> completed;
  final Set<String> downloaded;
  final ValueChanged<LessonInfo> onOpen;
  final ValueChanged<LessonInfo> onToggleComplete;
  final ValueChanged<LessonInfo> onDownload;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final locked = isTruthy(module['is_locked']);
    final title = tidyTitle(module['title']?.toString(), fallback: 'Module ${index + 1}');
    final done = lessons.where((l) => l.id != null && completed.contains(l.id)).length;

    return Card(
      child: Theme(
        // Remove ExpansionTile's default divider lines inside the card.
        data: theme.copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: !locked,
          shape: const Border(),
          collapsedShape: const Border(),
          leading: locked
              ? Icon(Icons.lock_outline, color: scheme.onSurfaceVariant)
              : null,
          title: Semantics(
            header: true,
            child: Text(title, style: theme.textTheme.titleMedium),
          ),
          subtitle: Text(
            locked
                ? 'Locked · complete the previous module to unlock'
                : '$done of ${lessons.length} lessons complete',
            style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          childrenPadding: const EdgeInsets.only(bottom: AppSpacing.sm),
          children: [
            if (lessons.isEmpty)
              const ListTile(title: Text('No lessons in this module yet.')),
            for (final lesson in lessons)
              _LessonTile(
                lesson: lesson,
                completed: lesson.id != null && completed.contains(lesson.id),
                downloaded: lesson.id != null &&
                    downloaded.contains(DownloadService.lessonKey(lesson.id!)),
                onOpen: () => onOpen(lesson),
                onToggleComplete: () => onToggleComplete(lesson),
                onDownload: () => onDownload(lesson),
              ),
          ],
        ),
      ),
    );
  }
}

enum _LessonAction { open, complete, download }

class _LessonTile extends StatelessWidget {
  const _LessonTile({
    required this.lesson,
    required this.completed,
    required this.downloaded,
    required this.onOpen,
    required this.onToggleComplete,
    required this.onDownload,
  });

  final LessonInfo lesson;
  final bool completed;
  final bool downloaded;
  final VoidCallback onOpen;
  final VoidCallback onToggleComplete;
  final VoidCallback onDownload;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final locked = lesson.isLocked;

    final status = locked
        ? 'Locked'
        : completed
            ? 'Completed'
            : null;

    final subtitleParts = [
      lesson.subtitle,
      if (downloaded) 'Available offline',
    ];

    return ListTile(
      onTap: onOpen,
      leading: CircleAvatar(
        backgroundColor: completed
            ? scheme.primaryContainer
            : scheme.surfaceContainerHighest,
        foregroundColor: completed
            ? scheme.onPrimaryContainer
            : scheme.onSurfaceVariant,
        child: Icon(
          locked
              ? Icons.lock_outline
              : completed
                  ? Icons.check
                  : lesson.kind.icon,
          semanticLabel: status ?? lesson.kind.label,
        ),
      ),
      title: Text(
        lesson.title,
        style: TextStyle(color: locked ? scheme.onSurfaceVariant : null),
      ),
      subtitle: Text(subtitleParts.join(' · ')),
      trailing: locked
          ? null
          : PopupMenuButton<_LessonAction>(
              tooltip: 'Options for ${lesson.title}',
              icon: const Icon(Icons.more_vert),
              onSelected: (action) {
                switch (action) {
                  case _LessonAction.open:
                    onOpen();
                  case _LessonAction.complete:
                    onToggleComplete();
                  case _LessonAction.download:
                    onDownload();
                }
              },
              itemBuilder: (_) => [
                const PopupMenuItem(
                  value: _LessonAction.open,
                  child: ListTile(
                    leading: Icon(Icons.open_in_full),
                    title: Text('Open lesson'),
                  ),
                ),
                PopupMenuItem(
                  value: _LessonAction.complete,
                  child: ListTile(
                    leading: Icon(
                      completed ? Icons.remove_done : Icons.check_circle_outline,
                    ),
                    title: Text(completed ? 'Mark as not complete' : 'Mark as complete'),
                  ),
                ),
                if (lesson.hasFile && !downloaded)
                  const PopupMenuItem(
                    value: _LessonAction.download,
                    child: ListTile(
                      leading: Icon(Icons.download_outlined),
                      title: Text('Download for offline'),
                    ),
                  ),
              ],
            ),
    );
  }
}
