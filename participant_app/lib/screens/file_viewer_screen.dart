import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:printing/printing.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

import '../core/learning_file.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/secure_screen.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// In-app viewer for view-only course files.
///
/// The file is fetched with the signed-in client into memory (PDF, images,
/// text) or streamed by the player (audio, video). Nothing is written to
/// the offline downloads folder, and there is no share, print, save or
/// "open in another app" action. On Android the screen is marked
/// FLAG_SECURE while open, which blocks screenshots and screen recording.
class FileViewerScreen extends StatefulWidget {
  const FileViewerScreen({
    super.key,
    required this.file,
    this.websiteUrl,
    this.offlineCacheKey,
  });

  final LearningFileInfo file;

  /// Page on the ElevateHer360 website that shows the file (lesson or
  /// assignment page), offered for types the app can't display.
  final String? websiteUrl;
  final String? offlineCacheKey;

  static Future<void> open(
    BuildContext context,
    LearningFileInfo file, {
    String? websiteUrl,
    String? offlineCacheKey,
  }) =>
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => FileViewerScreen(
            file: file,
            websiteUrl: websiteUrl,
            offlineCacheKey: offlineCacheKey,
          ),
        ),
      );

  @override
  State<FileViewerScreen> createState() => _FileViewerScreenState();
}

class _FileViewerScreenState extends State<FileViewerScreen> {
  final CancelToken _cancel = CancelToken();
  FileBytes? _bytes;
  Object? _error;
  double? _progress;
  bool _loading = false;

  FileViewKind get _kind => widget.file.viewKind;

  bool get _needsBytes =>
      _kind == FileViewKind.pdf ||
      _kind == FileViewKind.image ||
      _kind == FileViewKind.text;

  @override
  void initState() {
    super.initState();
    unawaited(SecureScreen.acquire());
    if (_needsBytes) _load();
  }

  @override
  void dispose() {
    _cancel.cancel();
    unawaited(SecureScreen.release());
    // The bytes only lived in this State; drop the reference right away.
    _bytes = null;
    super.dispose();
  }

  Future<void> _load() async {
    final path = widget.file.readerPath;
    if (path == null) return;
    setState(() {
      _loading = true;
      _error = null;
      _progress = null;
    });
    try {
      final cacheKey = widget.offlineCacheKey;
      if (cacheKey != null) {
        String? local;
        try {
          local = await DownloadService.instance.localPath(cacheKey);
        } catch (_) {
          // Fall through to the network if the cache index is unavailable.
        }
        if (local != null) {
          final bytes = await File(local).readAsBytes();
          if (mounted) setState(() => _bytes = FileBytes(bytes));
          return;
        }
      }
      final bytes = await ApiService.instance.fetchFileBytes(
        path: path,
        cancelToken: _cancel,
        onProgress: (received, total) {
          if (mounted && total > 0) {
            setState(() => _progress = received / total);
          }
        },
      );
      if (mounted) setState(() => _bytes = bytes);
    } catch (error) {
      if (mounted && !_cancel.isCancelled) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.file.name, overflow: TextOverflow.ellipsis),
      ),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_error != null) {
      return ErrorState(
          error: _error!, title: "Couldn't open this file", onRetry: _load);
    }

    switch (_kind) {
      case FileViewKind.video:
      case FileViewKind.audio:
        return _MediaPlayer(
          file: widget.file,
          audioOnly: _kind == FileViewKind.audio,
          offlineCacheKey: widget.offlineCacheKey,
        );
      case FileViewKind.other:
        return _UnsupportedView(
            file: widget.file, websiteUrl: widget.websiteUrl);
      case FileViewKind.pdf:
      case FileViewKind.image:
      case FileViewKind.text:
        break;
    }

    final bytes = _bytes;
    if (bytes == null || _loading) return _LoadingView(progress: _progress);

    switch (_kind) {
      case FileViewKind.pdf:
        return PdfPreview(
          build: (_) async => bytes.bytes,
          useActions: false,
          allowPrinting: false,
          allowSharing: false,
          canChangePageFormat: false,
          canChangeOrientation: false,
          canDebug: false,
          pdfFileName: widget.file.name,
          onError: (context, _) => const _MessageView(
            icon: Icons.picture_as_pdf_outlined,
            message: "This PDF couldn't be shown in the app.",
          ),
        );
      case FileViewKind.image:
        return Semantics(
          image: true,
          label: widget.file.name,
          child: InteractiveViewer(
            minScale: 1,
            maxScale: 5,
            child: Center(
              child: Image.memory(
                bytes.bytes,
                fit: BoxFit.contain,
                gaplessPlayback: true,
                errorBuilder: (_, __, ___) => const _MessageView(
                  icon: Icons.broken_image_outlined,
                  message: "This image couldn't be shown.",
                ),
              ),
            ),
          ),
        );
      default:
        return Scrollbar(
          child: SingleChildScrollView(
            padding: AppSpacing.pagePadding,
            child: Text(
              utf8.decode(bytes.bytes, allowMalformed: true),
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontFamily: 'monospace',
                    height: 1.4,
                  ),
            ),
          ),
        );
    }
  }
}

class _LoadingView extends StatelessWidget {
  const _LoadingView({this.progress});

  final double? progress;

  @override
  Widget build(BuildContext context) {
    final percent = progress == null ? null : (progress! * 100).round();
    return Center(
      child: Padding(
        padding: AppSpacing.pagePadding,
        child: Semantics(
          liveRegion: true,
          label: percent == null
              ? 'Opening file'
              : 'Opening file, $percent percent',
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(value: progress),
              const SizedBox(height: AppSpacing.md),
              Text(percent == null ? 'Opening…' : 'Opening… $percent%'),
            ],
          ),
        ),
      ),
    );
  }
}

class _MessageView extends StatelessWidget {
  const _MessageView({required this.icon, required this.message, this.action});

  final IconData icon;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 56, color: theme.colorScheme.primary),
              const SizedBox(height: AppSpacing.lg),
              Text(message,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyLarge),
              if (action != null) ...[
                const SizedBox(height: AppSpacing.lg),
                action!,
              ],
            ],
          ),
        ),
      ),
    );
  }
}

/// Word, PowerPoint and other types the app can't display.
class _UnsupportedView extends StatelessWidget {
  const _UnsupportedView({required this.file, this.websiteUrl});

  final LearningFileInfo file;
  final String? websiteUrl;

  Future<void> _openWebsite(BuildContext context) async {
    final uri = Uri.tryParse(websiteUrl ?? '');
    final ok = uri != null &&
        await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      showAppSnackBar(context, "The website couldn't be opened.", error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _MessageView(
      icon: Icons.visibility_outlined,
      message:
          'This file is view-only. Open it on the ElevateHer360 website to view it.',
      action: websiteUrl == null
          ? null
          : FilledButton.icon(
              onPressed: () => _openWebsite(context),
              icon: const Icon(Icons.open_in_browser),
              label: const Text('Open the website'),
            ),
    );
  }
}

/// Streams audio/video online, or plays its app-private cached copy offline.
class _MediaPlayer extends StatefulWidget {
  const _MediaPlayer(
      {required this.file, required this.audioOnly, this.offlineCacheKey});

  final LearningFileInfo file;
  final bool audioOnly;
  final String? offlineCacheKey;

  @override
  State<_MediaPlayer> createState() => _MediaPlayerState();
}

class _MediaPlayerState extends State<_MediaPlayer> {
  VideoPlayerController? _controller;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _start();
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _start() async {
    final cacheKey = widget.offlineCacheKey;
    String? cachedPath;
    try {
      if (cacheKey != null) {
        cachedPath = await DownloadService.instance.localPath(cacheKey);
      }
    } catch (_) {
      // A missing cache index should not prevent online playback.
    }
    if (cachedPath != null) {
      final old = _controller;
      setState(() {
        _controller = null;
        _error = null;
      });
      await old?.dispose();
      final cachedController = VideoPlayerController.file(File(cachedPath));
      try {
        await cachedController.initialize();
        if (!mounted) {
          await cachedController.dispose();
          return;
        }
        cachedController.addListener(_onTick);
        setState(() => _controller = cachedController);
        await cachedController.play();
      } catch (_) {
        await cachedController.dispose();
        if (mounted) {
          setState(() => _error = const AppException(
                AppErrorKind.unknown,
                "This saved media couldn't be played.",
              ));
        }
      }
      return;
    }

    final uri =
        ApiService.instance.inlineFileUri(widget.file.downloadPath ?? '');
    if (uri == null) {
      setState(() => _error = const AppException(
            AppErrorKind.notFound,
            "This file isn't available yet.",
          ));
      return;
    }
    final old = _controller;
    setState(() {
      _controller = null;
      _error = null;
    });
    await old?.dispose();

    final controller = VideoPlayerController.networkUrl(
      uri,
      httpHeaders: await ApiService.instance.authHeaders(),
    );
    try {
      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }
      controller.addListener(_onTick);
      setState(() => _controller = controller);
      await controller.play();
    } catch (_) {
      await controller.dispose();
      if (mounted) {
        setState(() => _error = const AppException(
              AppErrorKind.unknown,
              "This media couldn't be played. Check your connection and try again.",
            ));
      }
    }
  }

  void _onTick() {
    if (mounted) setState(() {});
  }

  static String _time(Duration d) {
    final minutes = d.inMinutes.remainder(60).toString().padLeft(2, '0');
    final seconds = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    return d.inHours > 0
        ? '${d.inHours}:$minutes:$seconds'
        : '$minutes:$seconds';
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return ErrorState(
          error: _error!, title: "Couldn't play this file", onRetry: _start);
    }
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized) {
      return const _LoadingView();
    }

    final theme = Theme.of(context);
    final value = controller.value;
    final playing = value.isPlaying;

    final controls = Padding(
      padding: AppSpacing.pagePadding,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          VideoProgressIndicator(
            controller,
            allowScrubbing: true,
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
            colors: VideoProgressColors(
              playedColor: theme.colorScheme.primary,
              bufferedColor: theme.colorScheme.primary.withValues(alpha: 0.3),
              backgroundColor: theme.colorScheme.surfaceContainerHighest,
            ),
          ),
          Row(
            children: [
              Text(_time(value.position), style: theme.textTheme.labelMedium),
              const Spacer(),
              IconButton(
                tooltip: 'Back 10 seconds',
                onPressed: () => controller
                    .seekTo(value.position - const Duration(seconds: 10)),
                icon: const Icon(Icons.replay_10),
              ),
              IconButton.filled(
                tooltip: playing ? 'Pause' : 'Play',
                iconSize: 32,
                onPressed: () =>
                    playing ? controller.pause() : controller.play(),
                icon: Icon(playing ? Icons.pause : Icons.play_arrow),
              ),
              IconButton(
                tooltip: 'Forward 10 seconds',
                onPressed: () => controller
                    .seekTo(value.position + const Duration(seconds: 10)),
                icon: const Icon(Icons.forward_10),
              ),
              const Spacer(),
              Text(_time(value.duration), style: theme.textTheme.labelMedium),
            ],
          ),
        ],
      ),
    );

    if (widget.audioOnly) {
      return Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: AppSpacing.readableWidth),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.headphones_outlined,
                  size: 72, color: theme.colorScheme.primary),
              const SizedBox(height: AppSpacing.md),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: AppSpacing.page),
                child: Text(
                  widget.file.name,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.titleMedium,
                ),
              ),
              controls,
            ],
          ),
        ),
      );
    }

    return Column(
      children: [
        Expanded(
          child: ColoredBox(
            color: Colors.black,
            child: Center(
              child: AspectRatio(
                aspectRatio:
                    value.aspectRatio == 0 ? 16 / 9 : value.aspectRatio,
                child: Semantics(
                  label: 'Video: ${widget.file.name}',
                  child: VideoPlayer(controller),
                ),
              ),
            ),
          ),
        ),
        controls,
      ],
    );
  }
}
