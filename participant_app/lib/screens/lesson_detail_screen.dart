import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/formatters.dart';
import '../core/lesson_info.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/lesson_progress_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// Lesson viewer for reading, document, video and link lessons, with
/// offline downloads and a "Mark as complete" action.
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

class _LessonDetailScreenState extends State<LessonDetailScreen> {
  late LessonInfo _lesson = LessonInfo(widget.lesson);
  final Stopwatch _timer = Stopwatch()..start();

  bool _completed = false;
  bool _saving = false;
  AppException? _accessError;

  int? get _id => _lesson.id;
  String get _cacheKey => 'lesson_$_id';

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final id = _id;
    if (id == null) return;

    final cached = await LocalDatabase.instance.readItem('lesson_details', _cacheKey);
    if (cached != null && mounted) {
      setState(() => _lesson = LessonInfo({...widget.lesson, ...cached}));
    }
    await _loadCompletion();
    await _fetch();
  }

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
      await _loadCompletion();
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
        timeSpentSeconds: _timer.elapsed.inSeconds,
      );
      _timer
        ..reset()
        ..start();
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
        lesson.hasFile ||
        lesson.extraFiles.isNotEmpty ||
        lesson.videoUrl != null ||
        lesson.externalUrl != null;

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
                  if (lesson.hasFile && _id != null) ...[
                    _FileCard(
                      downloadKey: DownloadService.lessonKey(_id!),
                      apiPath: lesson.downloadPath!,
                      title: lesson.title,
                      fileName: lesson.fileName,
                      sizeBytes: lesson.fileSizeBytes,
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  for (final file in lesson.extraFiles)
                    if (_id != null) ...[
                      _FileCard(
                        downloadKey: DownloadService.lessonFileKey(_id!, file.id),
                        apiPath: file.downloadPath!,
                        title: file.name,
                        fileName: file.name,
                        sizeBytes: file.sizeBytes,
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

/// Download / open / remove one lesson file, with progress.
class _FileCard extends StatefulWidget {
  const _FileCard({
    required this.downloadKey,
    required this.apiPath,
    required this.title,
    this.fileName,
    this.sizeBytes,
  });

  final String downloadKey;
  final String apiPath;
  final String title;
  final String? fileName;
  final int? sizeBytes;

  @override
  State<_FileCard> createState() => _FileCardState();
}

class _FileCardState extends State<_FileCard> {
  bool _available = false;
  bool _downloading = false;
  double? _progress;
  CancelToken? _cancel;

  @override
  void initState() {
    super.initState();
    _check();
  }

  @override
  void dispose() {
    _cancel?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    final path = await DownloadService.instance.localPath(widget.downloadKey);
    if (mounted) setState(() => _available = path != null);
  }

  Future<void> _download() async {
    _cancel = CancelToken();
    setState(() {
      _downloading = true;
      _progress = null;
    });

    try {
      await DownloadService.instance.download(
        key: widget.downloadKey,
        apiPath: widget.apiPath,
        title: widget.title,
        fileName: widget.fileName,
        cancelToken: _cancel,
        onProgress: (value) {
          if (mounted) setState(() => _progress = value);
        },
      );
      if (!mounted) return;
      setState(() => _available = true);
      showAppSnackBar(context, 'Saved for offline use.', actionLabel: 'Open', onAction: _open);
    } catch (error) {
      if (!mounted || (_cancel?.isCancelled ?? false)) return;
      showErrorSnackBar(context, error, onRetry: _download);
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  Future<void> _open() async {
    try {
      await DownloadService.instance.open(widget.downloadKey);
    } catch (error) {
      if (!mounted) return;
      showErrorSnackBar(context, error);
      await _check();
    }
  }

  Future<void> _remove() async {
    final ok = await confirmDialog(
      context,
      title: 'Remove offline copy?',
      message: 'The file will be deleted from this device. You can download it again later.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok) return;
    await DownloadService.instance.remove(widget.downloadKey);
    await _check();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final name = widget.fileName ?? widget.title;
    final details = [
      fileSizeLabel(widget.sizeBytes),
      if (_available) 'Available offline',
    ].whereType<String>().join(' · ');

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  _available ? Icons.offline_pin_outlined : Icons.description_outlined,
                  color: scheme.primary,
                  size: 32,
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name, style: theme.textTheme.titleMedium),
                      if (details.isNotEmpty)
                        Text(
                          details,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: scheme.onSurfaceVariant,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            if (_downloading) ...[
              Semantics(
                label: _progress == null
                    ? 'Downloading'
                    : 'Downloading, ${(_progress! * 100).round()} percent',
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                  child: LinearProgressIndicator(value: _progress),
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      _progress == null
                          ? 'Downloading…'
                          : 'Downloading… ${(_progress! * 100).round()}%',
                      style: theme.textTheme.bodyMedium,
                    ),
                  ),
                  TextButton(
                    onPressed: () => _cancel?.cancel(),
                    child: const Text('Cancel'),
                  ),
                ],
              ),
            ] else if (_available)
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  FilledButton.icon(
                    onPressed: _open,
                    icon: const Icon(Icons.open_in_new),
                    label: const Text('Open'),
                  ),
                  OutlinedButton.icon(
                    onPressed: _remove,
                    icon: const Icon(Icons.delete_outline),
                    label: const Text('Remove offline copy'),
                  ),
                ],
              )
            else
              FilledButton.icon(
                onPressed: _download,
                icon: const Icon(Icons.download_outlined),
                label: const Text('Download'),
              ),
          ],
        ),
      ),
    );
  }
}
