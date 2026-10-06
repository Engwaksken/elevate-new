import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../core/lesson_info.dart';
import '../core/theme/app_theme.dart';
import '../screens/file_viewer_screen.dart';
import '../services/download_service.dart';
import 'feedback.dart';
import 'state_views.dart';

/// One lesson file, assignment attachment or submitted file.
///
/// View-only files only get a "View" action (in-app viewer, nothing saved).
/// Downloadable files (spreadsheets, CSV, ZIP and her own submissions) keep
/// Download → Open / Remove offline copy.
class LearningFileTile extends StatefulWidget {
  const LearningFileTile({
    super.key,
    required this.file,
    this.downloadKey,
    this.websiteUrl,
    this.compact = false,
  });

  final LearningFileInfo file;

  /// Offline downloads key; required for downloadable files.
  final String? downloadKey;

  /// Website page that shows the file, for types the app can't display.
  final String? websiteUrl;

  /// A row inside another card (assignments) instead of its own card.
  final bool compact;

  @override
  State<LearningFileTile> createState() => _LearningFileTileState();
}

class _LearningFileTileState extends State<LearningFileTile> {
  bool _available = false;
  bool _downloading = false;
  double? _progress;
  CancelToken? _cancel;

  LearningFileInfo get _file => widget.file;

  bool get _canDownload => _file.downloadable && widget.downloadKey != null;

  @override
  void initState() {
    super.initState();
    _check();
  }

  @override
  void didUpdateWidget(LearningFileTile oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.downloadKey != widget.downloadKey ||
        oldWidget.file.downloadable != widget.file.downloadable) {
      _check();
    }
  }

  @override
  void dispose() {
    _cancel?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    final key = widget.downloadKey;
    // View-only files never have an offline copy (old ones are purged by
    // DownloadService.purgeViewOnlyCopies when the screen loads).
    if (key == null || !_file.downloadable) {
      if (_available && mounted) setState(() => _available = false);
      return;
    }
    final path = await DownloadService.instance.localPath(key);
    if (mounted) setState(() => _available = path != null);
  }

  Future<void> _view() => FileViewerScreen.open(context, _file, websiteUrl: widget.websiteUrl);

  Future<void> _download() async {
    final key = widget.downloadKey;
    final path = _file.downloadPath;
    if (key == null || path == null) return;

    _cancel = CancelToken();
    setState(() {
      _downloading = true;
      _progress = null;
    });

    try {
      await DownloadService.instance.download(
        key: key,
        apiPath: path,
        title: _file.name,
        fileName: _file.name,
        downloadable: _file.downloadable,
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
    final key = widget.downloadKey;
    if (key == null) return;
    try {
      await DownloadService.instance.open(key);
    } catch (error) {
      if (!mounted) return;
      showErrorSnackBar(context, error);
      await _check();
    }
  }

  Future<void> _remove() async {
    final key = widget.downloadKey;
    if (key == null) return;
    final ok = await confirmDialog(
      context,
      title: 'Remove offline copy?',
      message: 'The file will be deleted from this device. You can download it again later.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok) return;
    await DownloadService.instance.remove(key);
    await _check();
  }

  IconData get _icon {
    if (_available) return Icons.offline_pin_outlined;
    return switch (_file.viewKind) {
      FileViewKind.pdf => Icons.picture_as_pdf_outlined,
      FileViewKind.image => Icons.image_outlined,
      FileViewKind.video => Icons.movie_outlined,
      FileViewKind.audio => Icons.headphones_outlined,
      FileViewKind.text => Icons.article_outlined,
      FileViewKind.other => _file.downloadable
          ? Icons.table_chart_outlined
          : Icons.description_outlined,
    };
  }

  String get _details => [
        fileSizeLabel(_file.sizeBytes),
        if (!_canDownload) 'View only',
        if (_available) 'Available offline',
      ].whereType<String>().join(' · ');

  List<Widget> _actions({required bool compact}) {
    final name = _file.name;

    if (!_canDownload) {
      return [
        compact
            ? TextButton.icon(
                onPressed: _view,
                icon: const Icon(Icons.visibility_outlined),
                label: Text('View', semanticsLabel: 'View $name'),
              )
            : FilledButton.icon(
                onPressed: _view,
                icon: const Icon(Icons.visibility_outlined),
                label: Text('View', semanticsLabel: 'View $name'),
              ),
      ];
    }

    if (_downloading) {
      final percent = _progress == null ? null : (_progress! * 100).round();
      return [
        Semantics(
          label: percent == null ? 'Downloading $name' : 'Downloading $name, $percent percent',
          child: SizedBox(
            width: compact ? 120 : 200,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              child: LinearProgressIndicator(value: _progress),
            ),
          ),
        ),
        TextButton(
          onPressed: () => _cancel?.cancel(),
          child: Text('Cancel', semanticsLabel: 'Cancel download of $name'),
        ),
      ];
    }

    if (_available) {
      return [
        compact
            ? TextButton.icon(
                onPressed: _open,
                icon: const Icon(Icons.open_in_new),
                label: Text('Open', semanticsLabel: 'Open $name'),
              )
            : FilledButton.icon(
                onPressed: _open,
                icon: const Icon(Icons.open_in_new),
                label: Text('Open', semanticsLabel: 'Open $name'),
              ),
        compact
            ? IconButton(
                tooltip: 'Remove offline copy of $name',
                onPressed: _remove,
                icon: const Icon(Icons.delete_outline),
              )
            : OutlinedButton.icon(
                onPressed: _remove,
                icon: const Icon(Icons.delete_outline),
                label: const Text('Remove offline copy'),
              ),
      ];
    }

    return [
      compact
          ? TextButton.icon(
              onPressed: _download,
              icon: const Icon(Icons.download_outlined),
              label: Text('Download', semanticsLabel: 'Download $name'),
            )
          : FilledButton.icon(
              onPressed: _download,
              icon: const Icon(Icons.download_outlined),
              label: Text('Download', semanticsLabel: 'Download $name'),
            ),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final details = _details;

    final header = Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(_icon, color: scheme.primary, size: widget.compact ? 24 : 32),
        SizedBox(width: widget.compact ? AppSpacing.sm : AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _file.name,
                style: widget.compact ? theme.textTheme.bodyMedium : theme.textTheme.titleMedium,
              ),
              if (details.isNotEmpty)
                Text(
                  details,
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
            ],
          ),
        ),
      ],
    );

    final actions = Wrap(
      spacing: AppSpacing.sm,
      runSpacing: AppSpacing.xs,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: _actions(compact: widget.compact),
    );

    if (widget.compact) {
      return Padding(
        padding: const EdgeInsets.only(top: AppSpacing.sm),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            header,
            Padding(
              padding: const EdgeInsets.only(left: AppSpacing.lg),
              child: actions,
            ),
          ],
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            header,
            if (!_canDownload) ...[
              const SizedBox(height: AppSpacing.sm),
              const InfoChip(label: 'View only · stays in the app', icon: Icons.lock_outline),
            ],
            const SizedBox(height: AppSpacing.md),
            actions,
          ],
        ),
      ),
    );
  }
}
