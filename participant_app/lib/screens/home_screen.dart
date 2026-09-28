import 'package:flutter/material.dart';

import '../core/connectivity_banner.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import 'cached_list_screen.dart';
import 'dashboard_screen.dart';
import 'more_screen.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  bool _syncing = false;
  String? _lastSync;

  final _pages = const [
    DashboardScreen(),
    CachedListScreen(
      collection: 'courses',
      title: 'Learning',
      icon: Icons.menu_book_outlined,
    ),
    CachedListScreen(
      collection: 'mentorship',
      title: 'Mentorship',
      icon: Icons.diversity_3_outlined,
    ),
    CachedListScreen(
      collection: 'jobs',
      title: 'Jobs',
      icon: Icons.work_outline,
    ),
    MoreScreen(),
  ];

  @override
  void initState() {
    super.initState();
    _loadLastSync();
    SyncService.instance.startAutoSync();
  }

  Future<void> _loadLastSync() async {
    final value = await LocalDatabase.instance.getMeta('last_synced_at');
    if (mounted) setState(() => _lastSync = value);
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
            content:
                Text('You are offline. Your saved data is still available.'),
          ),
        );
        return;
      }

      await SyncService.instance.syncNow();
      await _loadLastSync();

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
              content: Text('Sync could not complete. Try again later.')),
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
            child: Text(
              _lastSync == null
                  ? 'Not synced yet'
                  : 'Last synced: ${_lastSync!.replaceFirst('T', ' ')}',
              style: const TextStyle(fontSize: 12),
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
