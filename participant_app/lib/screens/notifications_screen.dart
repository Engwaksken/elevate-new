import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'assignments_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen>
    with CachedDataMixin<NotificationsScreen, List<Map<String, dynamic>>> {
  @override
  Future<List<Map<String, dynamic>>> readCache() async {
    final items = await LocalDatabase.instance.readCollection('notifications');
    items.sort((a, b) => (b['created_at']?.toString() ?? '')
        .compareTo(a['created_at']?.toString() ?? ''));
    return items;
  }

  Future<void> _markRead(Map<String, dynamic> item, {bool quiet = false}) async {
    final id = int.tryParse(item['id']?.toString() ?? '');
    if (id == null || item['read_at'] != null) return;

    Future<void> queue() => SyncService.instance
        .queueAction('notification_read', {'notification_id': id});

    try {
      if (await SyncService.instance.isOnline()) {
        await ApiService.instance.markNotificationRead(id);
      } else {
        await queue();
      }
    } catch (error) {
      final mapped = AppException.from(error);
      if (mapped.isRetryable) {
        await queue();
      } else if (mapped.kind != AppErrorKind.notFound) {
        if (mounted && !quiet) showErrorSnackBar(context, error);
        return;
      }
    }

    item['read_at'] = DateTime.now().toUtc().toIso8601String();
    await LocalDatabase.instance.cacheItem(
      collection: 'notifications',
      itemId: id.toString(),
      payload: item,
    );
    SyncService.instance.notifyLocalChange();
  }

  Future<void> _markAll() async {
    for (final item in (data ?? const <Map<String, dynamic>>[])
        .where((i) => i['read_at'] == null)
        .toList()) {
      await _markRead(item, quiet: true);
    }
    if (mounted) showAppSnackBar(context, 'All notifications marked as read.');
  }

  static bool _isExtensionDecision(Map<String, dynamic> item) =>
      (item['type']?.toString() ?? '').startsWith('assignment_extension');

  /// Extension decisions arrive only as notifications (no push): refresh
  /// assignments so the new due date or reviewer note is shown.
  Future<void> _openAssignments() async {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const AssignmentsScreen()),
    );
    if (await SyncService.instance.isOnline()) {
      try {
        await ParticipantDataService.instance.refreshAssignments();
        SyncService.instance.notifyLocalChange();
      } catch (_) {
        // The cached list is still shown; the next sync retries.
      }
    }
  }

  void _open(Map<String, dynamic> item) {
    _markRead(item);
    final theme = Theme.of(context);
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) => SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(tidyTitle(item['title']?.toString(), fallback: 'Notification'),
                  style: theme.textTheme.titleLarge),
              const SizedBox(height: AppSpacing.xs),
              Text(formatDateTime(item['created_at']) ?? '',
                  style: theme.textTheme.bodyMedium
                      ?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
              const SizedBox(height: AppSpacing.lg),
              SelectableText(
                plainParagraphs(item['message']?.toString()).join('\n\n'),
                style: theme.textTheme.bodyLarge,
              ),
              if (_isExtensionDecision(item)) ...[
                const SizedBox(height: AppSpacing.lg),
                FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _openAssignments();
                  },
                  icon: const Icon(Icons.assignment_outlined),
                  label: const Text('View assignment'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final unread = (data ?? const []).where((i) => i['read_at'] == null).length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (unread > 0)
            TextButton(onPressed: _markAll, child: const Text('Mark all read')),
        ],
      ),
      body: SafeArea(top: false, child: _content(context)),
    );
  }

  Widget _content(BuildContext context) {
    if (loading) return const LoadingSkeleton();
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final items = data ?? const [];
    final scheme = Theme.of(context).colorScheme;

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: items.isEmpty
          ? const EmptyState(
              icon: Icons.notifications_none,
              title: "You're all caught up",
              message: 'Updates about your courses, mentorship and jobs will appear here.',
            )
          : ListView.separated(
              padding: AppSpacing.listPadding,
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, index) {
                final item = items[index];
                final isUnread = item['read_at'] == null;
                final title = tidyTitle(item['title']?.toString(), fallback: 'Notification');
                final message = plainParagraphs(item['message']?.toString()).join(' ');

                return Card(
                  color: isUnread ? scheme.secondaryContainer.withValues(alpha: 0.5) : null,
                  child: ListTile(
                    onTap: () => _open(item),
                    leading: Icon(
                      isUnread ? Icons.notifications_active : Icons.notifications_none,
                      color: isUnread ? scheme.primary : null,
                      semanticLabel: isUnread ? 'Unread' : 'Read',
                    ),
                    title: Text(
                      title,
                      style: TextStyle(fontWeight: isUnread ? FontWeight.w700 : FontWeight.w400),
                    ),
                    subtitle: Text(
                      [message, relativeTime(item['created_at'])]
                          .where((v) => v.isNotEmpty)
                          .join('\n'),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                );
              },
            ),
    );
  }
}
