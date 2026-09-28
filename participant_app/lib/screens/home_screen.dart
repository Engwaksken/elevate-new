import 'dart:async';

import 'package:flutter/material.dart';

import '../core/connectivity_banner.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/notification_service.dart';
import '../services/sync_service.dart';
import 'dashboard_screen.dart';
import 'jobs_screen.dart';
import 'learning_screen.dart';
import 'login_screen.dart';
import 'mentorship_screen.dart';
import 'more_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  bool _syncing = false;
  String? _lastSync;
  int _queuedActions = 0;
  StreamSubscription<String>? _notificationSubscription;

  final _pages = const [
    DashboardScreen(),
    LearningScreen(),
    MentorshipScreen(),
    JobsScreen(),
    MoreScreen(),
  ];

  @override
  void initState() {
    super.initState();

    _loadSyncState();
    SyncService.instance.startAutoSync();

    _notificationSubscription =
        NotificationService.instance.destinationStream.listen(
      _openNotificationDestination,
    );

    final pending = NotificationService.instance.consumePendingDestination();

    if (pending != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _openNotificationDestination(pending);
      });
    }
  }

  @override
  void dispose() {
    _notificationSubscription?.cancel();
    super.dispose();
  }

  void _openNotificationDestination(String destination) {
    final normalised = destination.toLowerCase();

    var index = 4;

    if (normalised.contains('course') ||
        normalised.contains('lesson') ||
        normalised.contains('learning')) {
      index = 1;
    } else if (normalised.contains('mentor')) {
      index = 2;
    } else if (normalised.contains('job')) {
      index = 3;
    } else if (normalised.contains('dashboard')) {
      index = 0;
    }

    if (mounted) {
      setState(() => _index = index);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            index == 4
                ? 'Open More to view the related update.'
                : 'Opened the related app section.',
          ),
          duration: const Duration(seconds: 3),
        ),
      );
    }
  }

  Future<void> _loadSyncState() async {
    final value = await LocalDatabase.instance.getMeta('last_synced_at');
    final queued = await LocalDatabase.instance.pendingOperationCount();

    if (mounted) {
      setState(() {
        _lastSync = value;
        _queuedActions = queued;
      });
    }
  }

  Future<void> _sync() async {
    if (_syncing) return;

    setState(() => _syncing = true);

    try {
      final online = await SyncService.instance.isOnline();

      if (!online) {
        if (!mounted) return;

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'You are offline. Your saved data is still available.',
            ),
          ),
        );
        return;
      }

      await SyncService.instance.syncNow();
      await NotificationService.instance.registerCurrentDevice();
      await _loadSyncState();

      if (mounted) {
        setState(() {});
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Sync completed.')),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sync could not complete. Try again later.'),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  Future<void> _logout() async {
    await ApiService.instance.logout();
    await LocalDatabase.instance.clearAll();

    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    const maroon = Color(0xFF800000);
    final titles = ['Dashboard', 'Learning', 'Mentorship', 'Jobs', 'More'];

    return Scaffold(
      appBar: AppBar(
        backgroundColor: maroon,
        foregroundColor: Colors.white,
        title: Text(titles[_index]),
        actions: [
          IconButton(
            tooltip: 'Sync Now',
            onPressed: _syncing ? null : _sync,
            icon: _syncing
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.sync),
          ),
          PopupMenuButton<String>(
            onSelected: (value) {
              if (value == 'logout') _logout();
            },
            itemBuilder: (_) => const [
              PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, color: Colors.black54),
                    SizedBox(width: 8),
                    Text('Sign Out'),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          const ConnectivityBanner(),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
            color: Colors.grey.shade100,
            child: Wrap(
              spacing: 14,
              runSpacing: 4,
              children: [
                Text(
                  _lastSync == null
                      ? 'Not synced yet'
                      : 'Last synced: ${_lastSync!.replaceFirst('T', ' ')}',
                  style: const TextStyle(fontSize: 12),
                ),
                if (_queuedActions > 0)
                  Text(
                    'Queued offline actions: $_queuedActions',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
              ],
            ),
          ),
          Expanded(child: _pages[_index]),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Learning',
          ),
          NavigationDestination(
            icon: Icon(Icons.diversity_3_outlined),
            selectedIcon: Icon(Icons.diversity_3),
            label: 'Mentorship',
          ),
          NavigationDestination(
            icon: Icon(Icons.work_outline),
            selectedIcon: Icon(Icons.work),
            label: 'Jobs',
          ),
          NavigationDestination(
            icon: Icon(Icons.grid_view_outlined),
            selectedIcon: Icon(Icons.grid_view),
            label: 'More',
          ),
        ],
      ),
    );
  }
}
