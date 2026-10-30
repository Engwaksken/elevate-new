import 'dart:async';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'library_reader_screen.dart';

/// Digital library: Continue reading, bookmarks, and the catalogue with
/// search and categories. Books are read online in [LibraryReaderScreen].
class LibraryScreen extends StatefulWidget {
  const LibraryScreen({super.key});

  @override
  State<LibraryScreen> createState() => _LibraryScreenState();
}

class _LibraryScreenState extends State<LibraryScreen> {
  final _search = TextEditingController();
  Timer? _debounce;

  List<Map<String, dynamic>>? _books;
  List<Map<String, dynamic>> _continue = [];
  List<Map<String, dynamic>> _bookmarks = [];
  List<Map<String, dynamic>> _categories = [];
  int? _categoryId;
  Object? _error;
  bool _busy = false;
  int _page = 1;
  bool _more = false;

  bool get _filtering => _search.text.trim().isNotEmpty || _categoryId != null;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load({bool next = false}) async {
    if (_busy) return;
    if (!await SyncService.instance.isOnline()) {
      if (mounted) setState(() => _error = AppException.offline);
      return;
    }
    setState(() => _busy = true);
    try {
      final page = next ? _page + 1 : 1;
      final results = await Future.wait([
        ApiService.instance.library(page: page, search: _search.text, categoryId: _categoryId),
        if (!next) ApiService.instance.libraryMine(),
      ]);
      final data = results[0];
      List<Map<String, dynamic>> maps(Object? list) =>
          (list as List? ?? []).whereType<Map>().map(Map<String, dynamic>.from).toList();
      if (!mounted) return;
      setState(() {
        _books = next ? [...?_books, ...maps(data['data'])] : maps(data['data']);
        _categories = maps(data['categories']);
        if (!next) {
          _continue = maps(results[1]['continue']);
          _bookmarks = maps(results[1]['bookmarks']);
        }
        _page = page;
        _more = page < ((data['last_page'] as num?)?.toInt() ?? 1);
        _error = null;
      });
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _load());
  }

  Future<void> _openBook(Map<String, dynamic> book) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => LibraryBookScreen(book: book)));
    if (mounted) unawaited(_load()); // progress or bookmarks may have changed
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final books = _books;

    return Scaffold(
      appBar: AppBar(title: const Text('Library')),
      body: books == null
          ? (_error != null ? ErrorState(error: _error!, onRetry: () => _load()) : const LoadingSkeleton())
          : RefreshIndicator(
              onRefresh: () => _load(),
              child: ListView(
                padding: const EdgeInsets.all(AppSpacing.page),
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  TextField(
                    controller: _search,
                    onChanged: _onSearchChanged,
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => _load(),
                    decoration: InputDecoration(
                      hintText: 'Search by title, author or topic',
                      prefixIcon: const Icon(Icons.search),
                      suffixIcon: _search.text.isEmpty
                          ? null
                          : IconButton(
                              tooltip: 'Clear search',
                              icon: const Icon(Icons.clear),
                              onPressed: () {
                                _search.clear();
                                _load();
                              },
                            ),
                    ),
                  ),
                  if (_categories.isNotEmpty) ...[
                    const SizedBox(height: AppSpacing.sm),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(children: [
                        for (final c in [<String, dynamic>{'id': null, 'name': 'All'}, ..._categories])
                          Padding(
                            padding: const EdgeInsets.only(right: AppSpacing.sm),
                            child: ChoiceChip(
                              label: Text(c['name'].toString()),
                              selected: _categoryId == (c['id'] as num?)?.toInt(),
                              onSelected: (_) {
                                setState(() => _categoryId = (c['id'] as num?)?.toInt());
                                _load();
                              },
                            ),
                          ),
                      ]),
                    ),
                  ],
                  if (_busy) const Padding(padding: EdgeInsets.only(top: AppSpacing.sm), child: LinearProgressIndicator()),
                  if (_error != null)
                    TextButton(onPressed: () => _load(), child: const Text('Unable to refresh. Retry')),
                  if (!_filtering && _continue.isNotEmpty) ...[
                    _SectionTitle('Continue reading'),
                    for (final b in _continue) BookTile(book: b, onTap: () => _openBook(b)),
                  ],
                  if (!_filtering && _bookmarks.isNotEmpty) ...[
                    _SectionTitle('Bookmarks'),
                    for (final b in _bookmarks) BookTile(book: b, onTap: () => _openBook(b)),
                  ],
                  _SectionTitle(_filtering ? 'Results' : 'All books'),
                  if (books.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(AppSpacing.xl),
                      child: Text(
                        _filtering ? 'No books match your search.' : 'No books in the library yet.',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodyLarge,
                      ),
                    ),
                  for (final b in books) BookTile(book: b, onTap: () => _openBook(b)),
                  if (_more)
                    TextButton(onPressed: _busy ? null : () => _load(next: true), child: const Text('Load more')),
                ],
              ),
            ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: AppSpacing.lg, bottom: AppSpacing.sm),
        child: Text(text, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
      );
}

/// A book in a list: cover, title, author and reading progress.
class BookTile extends StatelessWidget {
  const BookTile({super.key, required this.book, required this.onTap});
  final Map<String, dynamic> book;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final progress = book['progress'] is Map ? Map<String, dynamic>.from(book['progress']) : null;
    final percent = ((progress?['percent'] as num?) ?? 0).toDouble();
    final subtitle = [book['author'], (book['category'] as Map?)?['name']].whereType<Object>().join(' · ');

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            BookCover(path: book['cover_path']?.toString(), width: 56, height: 78),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(book['title']?.toString() ?? 'Untitled', style: theme.textTheme.titleSmall, maxLines: 2, overflow: TextOverflow.ellipsis),
                if (subtitle.isNotEmpty)
                  Text(subtitle, style: theme.textTheme.bodySmall, maxLines: 1, overflow: TextOverflow.ellipsis),
                if (progress != null && progress['completed'] == true)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text('Finished', style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.primary)),
                  )
                else if (progress?['page'] != null) ...[
                  const SizedBox(height: AppSpacing.xs),
                  LinearProgressIndicator(value: percent / 100, minHeight: 4, borderRadius: BorderRadius.circular(4)),
                  const SizedBox(height: AppSpacing.xxs),
                  Text('Page ${progress!['page']} of ${progress['total_pages']}', style: theme.textTheme.labelSmall),
                ],
              ]),
            ),
            if (book['bookmarked'] == true)
              Icon(Icons.bookmark, size: 20, color: theme.colorScheme.primary, semanticLabel: 'Bookmarked'),
          ]),
        ),
      ),
    );
  }
}

/// A book cover fetched with the signed-in API client, cached for the session.
class BookCover extends StatelessWidget {
  const BookCover({super.key, required this.path, this.width = 56, this.height = 78});
  final String? path;
  final double width;
  final double height;

  static final _cache = <String, Future<Uint8List>>{};

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final placeholder = Container(
      width: width,
      height: height,
      color: scheme.primaryContainer,
      alignment: Alignment.center,
      child: Icon(Icons.menu_book_rounded, color: scheme.onPrimaryContainer),
    );
    final p = path;

    return ClipRRect(
      borderRadius: BorderRadius.circular(AppRadius.sm),
      child: p == null
          ? placeholder
          : FutureBuilder<Uint8List>(
              future: _cache.putIfAbsent(p, () => ApiService.instance.libraryCover(p)..catchError((_) {
                    _cache.remove(p); // try again next time
                    return Uint8List(0);
                  })),
              builder: (context, snap) {
                final bytes = snap.data;
                if (bytes == null || bytes.isEmpty) return placeholder;
                return Image.memory(bytes, width: width, height: height, fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => placeholder);
              },
            ),
    );
  }
}

/// One book: details, reading progress, bookmark and download request.
class LibraryBookScreen extends StatefulWidget {
  const LibraryBookScreen({super.key, required this.book});
  final Map<String, dynamic> book;

  @override
  State<LibraryBookScreen> createState() => _LibraryBookScreenState();
}

class _LibraryBookScreenState extends State<LibraryBookScreen> {
  late Map<String, dynamic> _book = widget.book;
  bool _busy = false;

  int get _id => (_book['id'] as num).toInt();

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    try {
      final data = await ApiService.instance.libraryBook(_id);
      if (mounted && data['data'] is Map) setState(() => _book = Map<String, dynamic>.from(data['data']));
    } catch (_) {
      // The list's copy of the book is still shown.
    }
  }

  Future<void> _run(Future<void> Function() action) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      await action();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _read() async {
    final progress = _book['progress'] is Map ? Map<String, dynamic>.from(_book['progress']) : null;
    final startPage = progress != null && progress['completed'] != true ? (progress['page'] as num?)?.toInt() ?? 1 : 1;
    await Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => LibraryReaderScreen(bookId: _id, title: _book['title']?.toString() ?? 'Book', startPage: startPage),
    ));
    await _refresh();
  }

  Future<void> _toggleBookmark() => _run(() async {
        final bookmarked = await ApiService.instance.toggleLibraryBookmark(_id);
        setState(() => _book = {..._book, 'bookmarked': bookmarked});
        if (mounted) showAppSnackBar(context, bookmarked ? 'Added to your bookmarks.' : 'Removed from your bookmarks.');
      });

  Future<void> _requestDownload() => _run(() async {
        final message = await ApiService.instance.requestLibraryDownload(_id);
        await _refresh();
        if (mounted) showAppSnackBar(context, message);
      });

  Future<void> _openLink(String? url) async {
    if (url == null || url.isEmpty) return;
    final ok = await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    if (!ok && mounted) showAppSnackBar(context, "Couldn't open the link.");
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final progress = _book['progress'] is Map ? Map<String, dynamic>.from(_book['progress']) : null;
    final readable = _book['readable_in_app'] == true;
    final external = _book['external_url']?.toString();
    final request = _book['download_request'] is Map ? Map<String, dynamic>.from(_book['download_request']) : null;
    final onRequest = _book['download_policy'] == 'request';
    final description = _book['description']?.toString().trim() ?? '';

    final String readLabel;
    if (progress?['page'] != null && progress?['completed'] != true) {
      readLabel = 'Continue reading (page ${progress!['page']})';
    } else if (progress?['completed'] == true) {
      readLabel = 'Read again';
    } else {
      readLabel = 'Read';
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(_book['title']?.toString() ?? 'Book'),
        actions: [
          IconButton(
            tooltip: _book['bookmarked'] == true ? 'Remove bookmark' : 'Bookmark',
            onPressed: _busy ? null : _toggleBookmark,
            icon: Icon(_book['bookmarked'] == true ? Icons.bookmark : Icons.bookmark_border),
          ),
        ],
      ),
      body: Align(
        alignment: Alignment.topCenter,
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: AppSpacing.readableWidth),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.page),
            children: [
              Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                BookCover(path: _book['cover_path']?.toString(), width: 96, height: 136),
                const SizedBox(width: AppSpacing.lg),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(_book['title']?.toString() ?? '', style: theme.textTheme.titleLarge),
                    if (_book['author'] != null) Text(_book['author'].toString(), style: theme.textTheme.bodyMedium),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      [(_book['category'] as Map?)?['name'], _book['language']].whereType<Object>().join(' · '),
                      style: theme.textTheme.bodySmall,
                    ),
                    if (progress?['page'] != null && progress?['completed'] != true) ...[
                      const SizedBox(height: AppSpacing.sm),
                      LinearProgressIndicator(value: ((progress!['percent'] as num?) ?? 0) / 100, minHeight: 6, borderRadius: BorderRadius.circular(4)),
                      Text('Page ${progress['page']} of ${progress['total_pages']}', style: theme.textTheme.labelSmall),
                    ],
                  ]),
                ),
              ]),
              const SizedBox(height: AppSpacing.lg),
              if (readable)
                FilledButton.icon(onPressed: _busy ? null : _read, icon: const Icon(Icons.chrome_reader_mode_outlined), label: Text(readLabel))
              else if (external != null && external.isNotEmpty)
                FilledButton.icon(onPressed: () => _openLink(external), icon: const Icon(Icons.open_in_new), label: const Text('Open link'))
              else
                OutlinedButton.icon(
                  onPressed: () => _openLink(_book['web_url']?.toString()),
                  icon: const Icon(Icons.public),
                  label: const Text('Open on the website'),
                ),
              if (!readable && (external == null || external.isEmpty))
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.xs),
                  child: Text('This book is not a PDF, so it opens on the website.', style: theme.textTheme.bodySmall),
                ),
              const SizedBox(height: AppSpacing.md),
              if (onRequest && _book['can_download'] != true) ...[
                if (request?['status'] == 'pending')
                  const _Note(icon: Icons.hourglass_top, text: 'Your download request is waiting for the library team. You can keep reading here meanwhile.')
                else ...[
                  const _Note(icon: Icons.lock_outline, text: 'You can read this book in the app. To save a copy, ask the library team.'),
                  if (request?['status'] == 'declined')
                    const _Note(icon: Icons.info_outline, text: 'Your last request was not approved. You can ask again.'),
                  OutlinedButton.icon(
                    onPressed: _busy ? null : _requestDownload,
                    icon: const Icon(Icons.download_outlined),
                    label: const Text('Request download'),
                  ),
                ],
              ] else if (readable)
                const _Note(icon: Icons.public, text: 'You can save a copy of this book from the library on the website.'),
              if (description.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.lg),
                Text('About this book', style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.xs),
                Text(description, style: theme.textTheme.bodyMedium),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _Note extends StatelessWidget {
  const _Note({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: AppSpacing.sm),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, size: 18, color: Theme.of(context).colorScheme.onSurfaceVariant),
          const SizedBox(width: AppSpacing.sm),
          Expanded(child: Text(text, style: Theme.of(context).textTheme.bodySmall)),
        ]),
      );
}
