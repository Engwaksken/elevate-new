import 'package:flutter/material.dart';

import '../services/api_service.dart';
import 'cached_list_screen.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  void _open(
    BuildContext context, {
    required String collection,
    required String title,
    required IconData icon,
  }) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          appBar: AppBar(title: Text(title)),
          body: CachedListScreen(
            collection: collection,
            title: title,
            icon: icon,
          ),
        ),
      ),
    );
  }

  Future<void> _profile(BuildContext context) async {
    final user = await ApiService.instance.currentUser();
    if (!context.mounted) return;

    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (_) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Profile',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.person_outline),
              title: Text(user?['name']?.toString() ?? 'Participant'),
            ),
            ListTile(
              leading: const Icon(Icons.email_outlined),
              title: Text(user?['email']?.toString() ?? ''),
            ),
            if ((user?['phone']?.toString() ?? '').isNotEmpty)
              ListTile(
                leading: const Icon(Icons.phone_outlined),
                title: Text(user!['phone'].toString()),
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final items = [
      ('Assignments', 'assignments', Icons.assignment_outlined),
      ('Events', 'events', Icons.event_outlined),
      ('Announcements', 'announcements', Icons.campaign_outlined),
      ('Notifications', 'notifications', Icons.notifications_outlined),
    ];

    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        for (final item in items)
          Card(
            child: ListTile(
              leading: Icon(item.$3, color: const Color(0xFF800000)),
              title: Text(item.$1),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => _open(
                context,
                collection: item.$2,
                title: item.$1,
                icon: item.$3,
              ),
            ),
          ),
        Card(
          child: ListTile(
            leading: const Icon(Icons.person_outline, color: Color(0xFF800000)),
            title: const Text('Profile'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _profile(context),
          ),
        ),
      ],
    );
  }
}
