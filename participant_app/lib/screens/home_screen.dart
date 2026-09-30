import 'dart:async';

import 'package:flutter/material.dart';

import '../core/connectivity_banner.dart';
import '../core/logger.dart';
import '../services/local_database.dart';
import '../services/notification_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import 'dashboard_screen.dart';
import 'jobs_screen.dart';
import 'learning_screen.dart';
import 'mentorship_screen.dart';
import 'more_screen.dart';
import 'notifications_screen.dart';

/// Root shell: five-tab bottom navigation with a shared AppBar.
class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _Tab {
  const _Tab(this.title, this.label, this.icon, this.selectedIcon);

  final String title;
  final String label;
  final IconData icon;
  final IconData selectedIcon;
}

const _tabs = [
  _Tab('Home', 'Home', Icons.home_outlined, Icons.home),
  _Tab('My learning', 'Learning', Icons.menu_book_outlined, Icons.menu_book),
  _Tab('Mentorship', 'Mentorship', Icons.diversity_3_outlined, Icons.diversity_3),
  _Tab('Jobs & opportunities', 'Jobs', Icons.work_outline, Icons.work),
  _Tab('More', 'More', Icons.grid_view_outlined, Icons.grid_view),
];

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  int _unread = 0;
  StreamSubscription<String>? _notificationSubscription;

  late final List<Widget> _pages = [
    DashboardScreen(onOpenTab: _selectTab),
    const LearningScreen(),
    const MentorshipScreen(),
    const JobsScreen(),
    const MoreScreen(),
  ];

  @override
  void initState() {
    super.initState();

    SyncService.instance.startAutoSync();
    SyncService.instance.dataVersion.addListener(_loadUnread);
    _loadUnread();

    // Refresh quietly in the background when the app opens.
    unawaited(
      SyncService.instance.syncNow().catchError((Object error) {
        appLog('Startup sync failed', error);
      }),
    );

    _notificationSubscription = NotificationService.instance.destinationStream
        .listen(_openNotificationDestination);

    final pending = NotificationService.instance.consumePendingDestination();
    if (pending != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _openNotificationDestination(pending);
      });
    }
  }

  @override
  void dispose() {
    SyncService.instance.dataVersion.removeListener(_loadUnread);
    _notificationSubscription?.cancel();
    super.dispose();
  }

  Future<void> _loadUnread() async {
    final items = await LocalDatabase.instance.readCollection('notifications');
    final unread = items.where((item) => item['read_at'] == null).length;
    if (mounted) setState(() => _unread = unread);
  }

  void _selectTab(int index) => setState(() => _index = index);

  void _openNotificationDestination(String destination) {
    final value = destination.toLowerCase();

    int? index;
    if (value.contains('course') ||
        value.contains('lesson') ||
        value.contains('learning')) {
      index = 1;
    } else if (value.contains('mentor')) {
      index = 2;
    } else if (value.contains('job')) {
      index = 3;
    } else if (value.contains('dashboard')) {
      index = 0;
    }

    if (!mounted) return;

    if (index == null) {
      _openNotifications();
    } else {
      _selectTab(index);
    }
  }

  void _openNotifications() {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const NotificationsScreen()),
    );
  }

  Future<void> _sync() async {
    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        showAppSnackBar(
          context,
          "You're offline. Your saved content is still available.",
        );
      }
      return;
    }

    try {
      await SyncService.instance.syncNow();
      await NotificationService.instance.registerCurrentDevice();
      if (mounted) showAppSnackBar(context, 'Everything is up to date.');
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error, onRetry: _sync);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tab = _tabs[_index];

    return Scaffold(
      appBar: AppBar(
        title: Text(tab.title),
        actions: [
          ValueListenableBuilder<bool>(
            valueListenable: SyncService.instance.syncing,
            builder: (context, syncing, _) => IconButton(
              tooltip: syncing ? 'Syncing…' : 'Sync now',
              onPressed: syncing ? null : _sync,
              icon: syncing
                  ? const SizedBox.square(
                      dimension: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.sync),
            ),
          ),
          IconButton(
            tooltip: _unread == 0
                ? 'Notifications'
                : 'Notifications, $_unread unread',
            onPressed: _openNotifications,
            icon: Badge(
              isLabelVisible: _unread > 0,
              label: Text(_unread > 99 ? '99+' : '$_unread'),
              child: const Icon(Icons.notifications_outlined),
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Column(
        children: [
          const ConnectivityBanner(),
          Expanded(
            child: IndexedStack(index: _index, children: _pages),
          ),
        ],
      ),
      // Five labels can't grow without limit on a phone-width bar; clamp
      // like the platform navigation bars do. Screen content still scales
      // fully, and each destination keeps its tooltip and semantics label.
      bottomNavigationBar: MediaQuery.withClampedTextScaling(
        maxScaleFactor: 1.3,
        child: NavigationBar(
          selectedIndex: _index,
          onDestinationSelected: _selectTab,
          destinations: [
            for (final t in _tabs)
              NavigationDestination(
                icon: Icon(t.icon),
                selectedIcon: Icon(t.selectedIcon),
                label: t.label,
                tooltip: t.title,
              ),
          ],
        ),
      ),
    );
  }
}
