import 'package:flutter/material.dart';

import '../services/api_service.dart';
import 'assignments_screen.dart';
import 'cached_list_screen.dart';
import 'notifications_screen.dart';
import 'settings_screen.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  void _push(BuildContext context, Widget screen, String title) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          appBar: AppBar(title: Text(title)),
          body: screen,
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
    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.assignment_outlined,
              color: Color(0xFF800000),
            ),
            title: const Text('Assignments'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () =>
                _push(context, const AssignmentsScreen(), 'Assignments'),
          ),
        ),
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.event_outlined,
              color: Color(0xFF800000),
            ),
            title: const Text('Events'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _push(
              context,
              const CachedListScreen(
                collection: 'events',
                title: 'Events',
                icon: Icons.event_outlined,
              ),
              'Events',
            ),
          ),
        ),
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.campaign_outlined,
              color: Color(0xFF800000),
            ),
            title: const Text('Announcements'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _push(
              context,
              const CachedListScreen(
                collection: 'announcements',
                title: 'Announcements',
                icon: Icons.campaign_outlined,
              ),
              'Announcements',
            ),
          ),
        ),
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.notifications_outlined,
              color: Color(0xFF800000),
            ),
            title: const Text('Notifications'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _push(
              context,
              const NotificationsScreen(),
              'Notifications',
            ),
          ),
        ),
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.settings_outlined,
              color: Color(0xFF800000),
            ),
            title: const Text('Data & Offline Settings'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () =>
                _push(context, const SettingsScreen(), 'Data & Offline'),
          ),
        ),
        Card(
          child: ListTile(
            leading: const Icon(
              Icons.person_outline,
              color: Color(0xFF800000),
            ),
            title: const Text('Profile'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _profile(context),
          ),
        ),
      ],
    );
  }
}
