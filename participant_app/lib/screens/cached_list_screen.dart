import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/local_database.dart';
import '../widgets/cached_data.dart';
import '../widgets/search_field.dart';
import '../widgets/state_views.dart';

/// Generic offline list (events, announcements) with search, pull-to-
/// refresh and a detail sheet.
class CachedListScreen extends StatefulWidget {
  const CachedListScreen({
    super.key,
    required this.collection,
    required this.title,
    required this.icon,
    required this.emptyMessage,
    this.dateKeys = const ['starts_at', 'published_at', 'created_at'],
  });

  factory CachedListScreen.events() => const CachedListScreen(
        collection: 'events',
        title: 'Events',
        icon: Icons.event_outlined,
        emptyMessage: 'Upcoming events for your courses will appear here.',
        dateKeys: ['starts_at', 'created_at'],
      );

  factory CachedListScreen.announcements() => const CachedListScreen(
        collection: 'announcements',
        title: 'Announcements',
        icon: Icons.campaign_outlined,
        emptyMessage: 'Announcements from your instructors will appear here.',
        dateKeys: ['published_at', 'created_at'],
      );

  final String collection;
  final String title;
  final IconData icon;
  final String emptyMessage;
  final List<String> dateKeys;

  @override
  State<CachedListScreen> createState() => _CachedListScreenState();
}

class _CachedListScreenState extends State<CachedListScreen>
    with CachedDataMixin<CachedListScreen, List<Map<String, dynamic>>> {
  String _search = '';

  @override
  Future<List<Map<String, dynamic>>> readCache() async {
    final items = await LocalDatabase.instance.readCollection(widget.collection);
    items.sort((a, b) => (_date(b) ?? '').compareTo(_date(a) ?? ''));
    return items;
  }

  String? _date(Map<String, dynamic> item) {
    for (final key in widget.dateKeys) {
      final value = item[key]?.toString();
      if (value != null && value.isNotEmpty) return value;
    }
    return null;
  }

  String _title(Map<String, dynamic> item) => tidyTitle(
        (item['title'] ?? item['name'])?.toString(),
        fallback: widget.title,
      );

  String _body(Map<String, dynamic> item) =>
      plainParagraphs((item['body'] ?? item['description'] ?? item['message'])?.toString())
          .join('\n\n');

  String _meta(Map<String, dynamic> item) {
    final course = item['course'];
    return [
      formatDateTime(_date(item)),
      item['venue'] ?? item['location'],
      if (course is Map) course['title'],
    ].where((v) => v != null && v.toString().trim().isNotEmpty).join(' · ');
  }

  void _showDetail(Map<String, dynamic> item) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final theme = Theme.of(context);
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.6,
          maxChildSize: 0.95,
          builder: (context, controller) => SafeArea(
            child: ListView(
              controller: controller,
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
              children: [
                Text(_title(item), style: theme.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  _meta(item),
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
                SelectableText(
                  _body(item).isEmpty ? 'No further details.' : _body(item),
                  style: theme.textTheme.bodyLarge,
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: SafeArea(top: false, child: _content()),
    );
  }

  Widget _content() {
    if (loading) return const LoadingSkeleton();
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final all = data ?? const [];
    final term = _search.toLowerCase();
    final items = term.isEmpty
        ? all
        : all
            .where((i) => '${_title(i)} ${_body(i)} ${_meta(i)}'.toLowerCase().contains(term))
            .toList();

    return Column(
      children: [
        if (all.isNotEmpty)
          SearchField(
            hint: 'Search ${widget.title.toLowerCase()}',
            onChanged: (value) => setState(() => _search = value.trim()),
          ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: refreshFromServer,
            child: items.isEmpty
                ? EmptyState(
                    icon: widget.icon,
                    title: all.isEmpty
                        ? 'No ${widget.title.toLowerCase()} yet'
                        : 'No matches',
                    message: all.isEmpty ? widget.emptyMessage : 'Try a different search term.',
                  )
                : ListView.separated(
                    padding: AppSpacing.listPadding,
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      final meta = _meta(item);
                      return Card(
                        child: ListTile(
                          leading: CircleAvatar(child: Icon(widget.icon)),
                          title: Text(_title(item)),
                          subtitle: meta.isEmpty ? null : Text(meta),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () => _showDetail(item),
                        ),
                      );
                    },
                  ),
          ),
        ),
      ],
    );
  }
}
