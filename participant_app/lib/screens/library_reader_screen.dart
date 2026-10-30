import 'dart:async';
import 'dart:math' as math;
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:printing/printing.dart';

import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/secure_screen.dart';
import '../widgets/state_views.dart';

/// Reads a library PDF page by page: opens at the saved page, swipe or use
/// the arrows to turn pages, pinch to zoom. The current page is saved to the
/// server (shared with the website's "Continue reading"). Books are read
/// online only; nothing is saved to the phone, and on Android screenshots
/// are blocked while the reader is open.
class LibraryReaderScreen extends StatefulWidget {
  const LibraryReaderScreen({
    super.key,
    required this.bookId,
    required this.title,
    this.startPage = 1,
    this.loadPdf,
    this.saveProgress,
  });

  final int bookId;
  final String title;

  /// 1-based page to open at.
  final int startPage;

  /// Injectable for tests; default to [ApiService].
  final Future<Uint8List> Function()? loadPdf;
  final Future<void> Function(int page, int totalPages)? saveProgress;

  @override
  State<LibraryReaderScreen> createState() => _LibraryReaderScreenState();
}

class _LibraryReaderScreenState extends State<LibraryReaderScreen> {
  /// How long to wait after the last page turn before saving.
  static const _saveDelay = Duration(seconds: 2);

  Uint8List? _pdf;

  /// Page sizes in inches (rendered at 1 dpi), used to pick a sharp dpi.
  List<Size> _pages = const [];
  Object? _error;
  PageController? _controller;
  int _current = 0;
  int? _savedPage;
  Timer? _saveTimer;
  final _images = <int, Future<ui.Image?>>{};

  @override
  void initState() {
    super.initState();
    unawaited(SecureScreen.acquire());
    _open();
  }

  @override
  void dispose() {
    _saveTimer?.cancel();
    unawaited(_save());
    _controller?.dispose();
    for (final image in _images.values) {
      image.then((i) => i?.dispose());
    }
    unawaited(SecureScreen.release());
    super.dispose();
  }

  Future<void> _open() async {
    setState(() => _error = null);
    try {
      final pdf = await (widget.loadPdf ?? () => ApiService.instance.libraryBookPdf(widget.bookId))();
      final sizes = <Size>[];
      await for (final page in Printing.raster(pdf, dpi: 1)) {
        sizes.add(Size(page.width.toDouble(), page.height.toDouble()));
      }
      if (sizes.isEmpty) throw StateError("This book couldn't be opened.");
      final start = (widget.startPage - 1).clamp(0, sizes.length - 1);
      if (!mounted) return;
      setState(() {
        _pdf = pdf;
        _pages = sizes;
        _current = start;
        _savedPage = start + 1;
        _controller = PageController(initialPage: start);
      });
    } catch (error) {
      if (mounted) setState(() => _error = error);
    }
  }

  void _onPageChanged(int index) {
    setState(() => _current = index);
    _saveTimer?.cancel();
    _saveTimer = Timer(_saveDelay, _save);
    _evictFarPages();
  }

  Future<void> _save() async {
    if (_pages.isEmpty) return;
    final page = _current + 1;
    if (page == _savedPage) return;
    try {
      final save = widget.saveProgress ??
          (int p, int total) => ApiService.instance.saveLibraryProgress(widget.bookId, page: p, totalPages: total);
      await save(page, _pages.length);
      _savedPage = page;
    } catch (_) {
      // Progress is a convenience; reading carries on if it can't be saved.
    }
  }

  void _goTo(int index) {
    final target = index.clamp(0, _pages.length - 1);
    _controller?.animateToPage(target, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
  }

  /// Keeps only the pages near the current one in memory.
  void _evictFarPages() {
    for (final key in _images.keys.toList()) {
      if ((key - _current).abs() > 2) {
        _images.remove(key)?.then((i) => i?.dispose());
      }
    }
  }

  Future<ui.Image?> _render(int index, double dpi) => _images.putIfAbsent(index, () async {
        final pdf = _pdf;
        if (pdf == null) return null;
        final raster = await Printing.raster(pdf, pages: [index], dpi: dpi).first;
        return raster.toImage();
      });

  Future<void> _askPage() async {
    final controller = TextEditingController(text: '${_current + 1}');
    final page = await showDialog<int>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Go to page'),
        content: TextField(
          controller: controller,
          autofocus: true,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(helperText: '1 to ${_pages.length}'),
          onSubmitted: (v) => Navigator.of(context).pop(int.tryParse(v)),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.of(context).pop(int.tryParse(controller.text)), child: const Text('Go')),
        ],
      ),
    );
    controller.dispose();
    if (page != null) _goTo(page - 1);
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;

    return Scaffold(
      appBar: AppBar(title: Text(widget.title, maxLines: 1, overflow: TextOverflow.ellipsis)),
      body: controller == null
          ? (_error != null
              ? ErrorState(error: _error!, onRetry: _open)
              : const Center(
                  child: Column(mainAxisSize: MainAxisSize.min, children: [
                    CircularProgressIndicator(),
                    SizedBox(height: AppSpacing.md),
                    Text('Opening book…'),
                  ]),
                ))
          : Column(children: [
              LinearProgressIndicator(value: (_current + 1) / _pages.length, minHeight: 3),
              Expanded(
                child: ColoredBox(
                  color: Theme.of(context).colorScheme.surfaceContainerHighest,
                  child: PageView.builder(
                    controller: controller,
                    itemCount: _pages.length,
                    onPageChanged: _onPageChanged,
                    itemBuilder: (context, index) => _PageView(
                      size: _pages[index],
                      render: (dpi) => _render(index, dpi),
                      pageNumber: index + 1,
                    ),
                  ),
                ),
              ),
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
                  child: Row(children: [
                    IconButton(
                      tooltip: 'Previous page',
                      onPressed: _current > 0 ? () => _goTo(_current - 1) : null,
                      icon: const Icon(Icons.chevron_left),
                    ),
                    Expanded(
                      child: TextButton(
                        onPressed: _askPage,
                        child: Text('Page ${_current + 1} of ${_pages.length}'),
                      ),
                    ),
                    IconButton(
                      tooltip: 'Next page',
                      onPressed: _current < _pages.length - 1 ? () => _goTo(_current + 1) : null,
                      icon: const Icon(Icons.chevron_right),
                    ),
                  ]),
                ),
              ),
            ]),
    );
  }
}

/// One page, rendered sharp for the screen and zoomable.
class _PageView extends StatelessWidget {
  const _PageView({required this.size, required this.render, required this.pageNumber});

  /// Page size in inches.
  final Size size;
  final Future<ui.Image?> Function(double dpi) render;
  final int pageNumber;

  @override
  Widget build(BuildContext context) {
    final pixelRatio = MediaQuery.devicePixelRatioOf(context);

    return LayoutBuilder(builder: (context, constraints) {
      // Fit the page to the screen, then render at enough dpi to be sharp
      // at that size (capped so huge pages don't exhaust memory).
      final scale = math.min(constraints.maxWidth / size.width, constraints.maxHeight / size.height);
      final dpi = (scale * pixelRatio).clamp(72.0, 220.0);

      return InteractiveViewer(
        maxScale: 4,
        child: Center(
          child: FutureBuilder<ui.Image?>(
            future: render(dpi),
            builder: (context, snap) {
              final image = snap.data;
              if (image == null) {
                return SizedBox(
                  width: size.width * scale,
                  height: size.height * scale,
                  child: snap.hasError
                      ? const Center(child: Text("This page couldn't be shown."))
                      : const Center(child: CircularProgressIndicator()),
                );
              }
              return Semantics(
                label: 'Page $pageNumber',
                image: true,
                child: Container(
                  color: Colors.white,
                  width: size.width * scale,
                  height: size.height * scale,
                  child: RawImage(image: image, fit: BoxFit.contain),
                ),
              );
            },
          ),
        ),
      );
    });
  }
}
