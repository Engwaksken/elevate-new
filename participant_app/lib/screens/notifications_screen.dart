import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  Future<List<Map<String, dynamic>>> _load() =>
      LocalDatabase.instance.readCollection('notifications');

  Future<void> _markRead(Map<String, dynamic> item) async {
    final id = int.tryParse(item['id']?.toString() ?? '');
    if (id == null || item['read_at'] != null) return;

    if (await SyncService.instance.isOnline()) {
      await ApiService.instance.markNotificationRead(id);
    } else {
      await SyncService.instance.queueAction(
        'notification_read',
        {'notification_id': id},
      );
    }

    item['read_at'] = DateTime.now().toUtc().toIso8601String();

    await LocalDatabase.instance.cacheItem(
      collection: 'notifications',
      itemId: id.toString(),
      payload: item,
    );

    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _load(),
      builder: (context, snapshot) {
        final items = snapshot.data ?? [];

        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (items.isEmpty) {
          return const Center(child: Text('No notifications available.'));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(12),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (context, index) {
            final item = items[index];
            final unread = item['read_at'] == null;

            return Card(
              child: ListTile(
                leading: Icon(
                  unread
                      ? Icons.notifications_active
                      : Icons.notifications_none,
                  color: unread ? const Color(0xFF800000) : null,
                ),
                title: Text(
                  item['title']?.toString() ?? 'Notification',
                  style: TextStyle(
                    fontWeight: unread ? FontWeight.w700 : FontWeight.w400,
                  ),
                ),
                subtitle: Text(item['message']?.toString() ?? ''),
                trailing: unread
                    ? TextButton(
                        onPressed: () => _markRead(item),
                        child: const Text('Mark read'),
                      )
                    : null,
                onTap: () => _markRead(item),
              ),
            );
          },
        );
      },
    );
  }
}
