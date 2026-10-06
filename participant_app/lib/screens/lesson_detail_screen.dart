import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/formatters.dart';
import '../core/lesson_info.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import '../core/reading_time_accumulator.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/lesson_progress_service.dart';
import '../services/local_database.dart';
import '../services/reading_time_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/learning_file_tile.dart';
import '../widgets/state_views.dart';

/// Lesson viewer for reading, document, video and link lessons, with
/// every lesson file (view-only in the app, or downloadable for offline
/// use when the policy allows) and a "Mark as complete" action.
class LessonDetailScreen extends StatefulWidget {
  const LessonDetailScreen({
    super.key,
    required this.lesson,
    required this.courseId,
    required this.courseTitle,
    required this.courseLessonIds,
  });

  final Map<String, dynamic> lesson;
  final int courseId;
  final String courseTitle;
  final Set<int> courseLessonIds;

  @override
  State<LessonDetailScreen> createState() => _LessonDetailScreenState();
}

class _LessonDetailScreenState extends State<LessonDetailScreen>
    with WidgetsBindingObserver {
  late LessonInfo _lesson = LessonInfo(widget.lesson);

  /// Active reading time: only while this screen is visible, the app is in
  /// the foreground and she interacted in the last 5 minutes.
  final ReadingTimeAccumulator _reading = ReadingTimeAccumulator();
  Timer? _ticker;
  int _ticks = 0;

  /// Best known total from the server and this device (excluding the
  /// seconds still in [_reading]).
  int _storedSeconds = 0;

  bool _completed = false;
  bool _saving = false;
  AppException? _accessError;

  int? get _id => _lesson.id;
  String get _cacheKey => 'lesson_$_id';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _reading.setVisible(true);
    // Save counted seconds every 15 s (crash-safe) and send them about
    // every 60 s while the lesson is open, as the API recommends.
    _ticker = Timer.periodic(const Duration(seconds: 15), (_) {
      _ticks++;
      _persistReading(flush: _ticks % 4 == 0);
    });
    _init();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // False while another route covers this one.
    _reading.setVisible(TickerMode.valuesOf(context).enabled);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final foreground = state == AppLifecycleState.resumed;
    _reading.setForeground(foreground);
    if (!foreground) _persistReading(flush: true);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _ticker?.cancel();
    _reading.setVisible(false);
    final id = _id;
    final seconds = _reading.takeSeconds();
    if (id != null) {
      // Leaving the lesson: store what's left, then send it (or keep it
      // queued until she is back online).
      unawaited(() async {
        await ReadingTimeService.instance.record(
          lessonId: id,
          courseId: widget.courseId,
          seconds: seconds,
        );
        await ReadingTimeService.instance.flush(lessonId: id);
      }());
    }
    super.dispose();
  }

  void _interacted() => _reading.recordInteraction();

  int get _serverSeconds {
    final progress = _lesson.raw['progress'];
    return progress is Map ? asInt(progress['time_spent_seconds']) ?? 0 : 0;
  }

  Future<void> _refreshStoredSeconds() async {
    final id = _id;
    if (id == null) return;
    final total = await ReadingTimeService.instance.lessonTotal(
      id,
      serverSeconds: _serverSeconds,
    );
    if (mounted) setState(() => _storedSeconds = total);
  }

  /// Moves counted seconds into SQLite; optionally sends them.
  Future<void> _persistReading({bool flush = false}) async {
    final id = _id;
    if (id == null) return;
    final seconds = _reading.takeSeconds();
    try {
      await ReadingTimeService.instance.record(
        lessonId: id,
        courseId: widget.courseId,
        seconds: seconds,
      );
      if (flush) await ReadingTimeService.instance.flush(lessonId: id);
    } catch (error) {
      appLog('Saving reading time failed', error);
    }
    await _refreshStoredSeconds();
  }

  Future<void> _init() async {
    final id = _id;
    if (id == null) return;

    final cached = await LocalDatabase.instance.readItem('lesson_details', _cacheKey);
    if (cached != null && mounted) {
      setState(() => _lesson = LessonInfo({...widget.lesson, ...cached}));
    }
    await _refreshStoredSeconds();
    await _loadCompletion();
    await _purgeViewOnlyCopies();
    await _fetch();
  }

  /// Offline copies saved before a file became view-only are deleted.
  Future<void> _purgeViewOnlyCopies() async {
    final files = _lesson.files;
    await DownloadService.instance.purgeViewOnlyCopies(viewOnlyKeys: [
      for (var i = 0; i < files.length; i++)
        if (files[i].viewOnly) _lesson.fileDownloadKey(files[i], i),
    ].whereType<String>());
  }

  /// The lesson page on the website (for files the app can't display).
  String? get _websiteUrl => _id == null ? null : '${AppConfig.siteUrl}/learning/lessons/$_id';

  Future<void> _loadCompletion() async {
    final id = _id;
    if (id == null) return;
    final done = await LocalDatabase.instance.completedLessonIds(
      alsoCompleted: [if (_lesson.completedOnServer) id],
    );
    if (mounted) setState(() => _completed = done.contains(id));
  }

  Future<void> _fetch() async {
    final id = _id;
    if (id == null || !await SyncService.instance.isOnline()) return;

    try {
      final fresh = await ApiService.instance.lesson(id);
      await LocalDatabase.instance.cacheItem(
        collection: 'lesson_details',
        itemId: _cacheKey,
        payload: fresh,
      );
      if (!mounted) return;
      setState(() {
        _lesson = LessonInfo({...widget.lesson, ...fresh});
        _accessError = null;
      });
      await ReadingTimeService.instance.rememberServerTotal(
        id,
        _serverSeconds,
        courseId: widget.courseId,
      );
      await _refreshStoredSeconds();
      await _loadCompletion();
      await _purgeViewOnlyCopies();
    } catch (error) {
      final mapped = AppException.from(error);
      // The course payload already holds the lesson content, so only an
      // access problem (locked module / not enrolled) blocks the screen.
      if (mapped.kind == AppErrorKind.forbidden) {
        if (mounted) setState(() => _accessError = mapped);
      } else {
        appLog('Lesson detail refresh failed (${mapped.statusCode})');
      }
    }
  }

  Future<void> _toggleComplete() async {
    final id = _id;
    if (id == null || _saving) return;

    final target = !_completed;
    final ids = {...widget.courseLessonIds};
    final done = await LocalDatabase.instance.completedLessonIds();
    final after = done.intersection(ids);
    target ? after.add(id) : after.remove(id);
    final percent = ids.isEmpty ? 0.0 : after.length / ids.length * 100;

    setState(() {
      _saving = true;
      _completed = target;
    });

    try {
      final result = await LessonProgressService.instance.setCompleted(
        lessonId: id,
        completed: target,
        courseId: widget.courseId,
        localCoursePercent: percent,
      );
      // Reading time goes separately as a delta (never with completed).
      await _persistReading(flush: true);
      if (!mounted) return;
      showAppSnackBar(
        context,
        result == ProgressSaveResult.queued
            ? "Saved on this device. It will sync when you're back online."
            : target
                ? 'Nice work! Lesson marked as complete.'
                : 'Lesson marked as not complete.',
      );
    } catch (error) {
      if (!mounted) return;
      setState(() => _completed = !target);
      showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _launch(String url) async {
    final uri = Uri.tryParse(url);
    final ok = uri != null &&
        await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      showAppSnackBar(context, "This link couldn't be opened.", error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.courseTitle, overflow: TextOverflow.ellipsis),
      ),
      body: SafeArea(
        top: false,
        child: _accessError != null
            ? ErrorState(
                error: _accessError!,
                title: 'This lesson is locked',
                onRetry: _fetch,
              )
            : _content(context),
      ),
      bottomNavigationBar: _accessError != null || _id == null
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.page,
                  AppSpacing.sm,
                  AppSpacing.page,
                  AppSpacing.sm,
                ),
                child: _completed
                    ? FilledButton.tonalIcon(
                        onPressed: _saving ? null : _toggleComplete,
                        icon: const Icon(Icons.check_circle),
                        label: const Text('Completed · tap to undo'),
                      )
                    : FilledButton.icon(
                        onPressed: _saving ? null : _toggleComplete,
                        icon: _saving
                            ? const SizedBox.square(
                                dimension: 18,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.check_circle_outline),
                        label: const Text('Mark as complete'),
                      ),
              ),
            ),
    );
  }

  Widget _content(BuildContext context) {
    final theme = Theme.of(context);
    final lesson = _lesson;
    final paragraphs = plainParagraphs(lesson.body);
    final hasAnything = paragraphs.isNotEmpty ||
        lesson.files.isNotEmpty ||
        lesson.videoUrl != null ||
        lesson.externalUrl != null;

    return Listener(
      behavior: HitTestBehavior.translucent,
      onPointerDown: (_) => _interacted(),
      onPointerSignal: (_) => _interacted(),
      child: NotificationListener<ScrollNotification>(
        onNotification: (_) {
          _interacted();
          return false;
        },
        child: _scrollable(context, theme, lesson, paragraphs, hasAnything),
      ),
    );
  }

  Widget _scrollable(
    BuildContext context,
    ThemeData theme,
    LessonInfo lesson,
    List<String> paragraphs,
    bool hasAnything,
  ) {
    return RefreshIndicator(
      onRefresh: _fetch,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.page,
          AppSpacing.lg,
          AppSpacing.page,
          AppSpacing.xxl,
        ),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: AppSpacing.readableWidth),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: AppSpacing.sm,
                    runSpacing: AppSpacing.sm,
                    children: [
                      InfoChip(label: lesson.kind.label, icon: lesson.kind.icon, emphasis: true),
                      if (lesson.durationLabel != null)
                        InfoChip(label: lesson.durationLabel!, icon: Icons.schedule),
                      if (_completed)
                        const InfoChip(label: 'Completed', icon: Icons.check),
                      _TimeSpentChip(
                        seconds: _storedSeconds + _reading.pendingSeconds,
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Semantics(
                    header: true,
                    child: Text(lesson.title, style: theme.textTheme.headlineSmall),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  if (lesson.videoUrl != null) ...[
                    _ActionCard(
                      icon: Icons.play_circle_outline,
                      title: 'Watch the video',
                      subtitle: 'Opens in your video app or browser',
                      buttonLabel: 'Watch video',
                      buttonIcon: Icons.play_arrow,
                      onPressed: () => _launch(lesson.videoUrl!),
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  for (final (index, file) in lesson.files.indexed) ...[
                    LearningFileTile(
                      key: ValueKey('lesson-file-$index-${file.id}'),
                      file: file,
                      downloadKey: lesson.fileDownloadKey(file, index),
                      websiteUrl: _websiteUrl,
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  if (lesson.externalUrl != null) ...[
                    _ActionCard(
                      icon: Icons.link,
                      title: 'Web resource',
                      subtitle: Uri.tryParse(lesson.externalUrl!)?.host ?? lesson.externalUrl!,
                      buttonLabel: 'Open link',
                      buttonIcon: Icons.open_in_new,
                      onPressed: () => _launch(lesson.externalUrl!),
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  if (paragraphs.isNotEmpty)
                    SelectionArea(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          for (final paragraph in paragraphs)
                            Padding(
                              padding: const EdgeInsets.only(bottom: AppSpacing.lg),
                              child: Text(
                                paragraph,
                                style: theme.textTheme.bodyLarge?.copyWith(fontSize: 17),
                              ),
                            ),
                        ],
                      ),
                    ),
                  if (!hasAnything)
                    const EmptyState(
                      icon: Icons.hourglass_empty,
                      title: 'Content coming soon',
                      message: "This lesson doesn't have any content yet. Check back later.",
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionCard extends StatelessWidget {
  const _ActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.buttonLabel,
    required this.buttonIcon,
    required this.onPressed,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final String buttonLabel;
  final IconData buttonIcon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: theme.colorScheme.primary, size: 32),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: theme.textTheme.titleMedium),
                      Text(
                        subtitle,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            FilledButton.icon(
              onPressed: onPressed,
              icon: Icon(buttonIcon),
              label: Text(buttonLabel),
            ),
          ],
        ),
      ),
    );
  }
}

/// "Time spent: 12 min" on the lesson.
class _TimeSpentChip extends StatelessWidget {
  const _TimeSpentChip({required this.seconds});

  final int seconds;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: 'Time spent on this lesson: ${spokenDuration(seconds)}',
      excludeSemantics: true,
      child: InfoChip(
        label: 'Time spent: ${formatDuration(seconds)}',
        icon: Icons.timer_outlined,
      ),
    );
  }
}
