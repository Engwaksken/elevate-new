import 'dart:async';

import 'package:flutter/material.dart';

import '../core/connectivity_banner.dart';
import '../core/logger.dart';
import '../services/api_service.dart';
import '../services/auth_flow.dart';
import '../services/local_database.dart';
import '../services/notification_service.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/app_drawer.dart';
import '../widgets/feedback.dart';
import 'about_screen.dart';
import 'assignments_screen.dart';
import 'cached_list_screen.dart';
import 'career_documents_screen.dart';
import 'dashboard_screen.dart';
import 'downloads_screen.dart';
import 'help_support_screen.dart';
import 'jobs_screen.dart';
import 'learning_screen.dart';
import 'login_screen.dart';
import 'mentorship_screen.dart';
import 'notifications_screen.dart';
import 'profile_screen.dart';
import 'progress_screen.dart';
import 'settings_screen.dart';

/// Root shell: bottom navigation for the five most used places, and a
/// navigation drawer (menu button) listing every destination.
class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _Tab {
  const _Tab(this.destination, this.title, this.label);

  final AppDestination destination;
  final String title;
  final String label;
}

const _tabs = [
  _Tab(AppDestination.home, 'Home', 'Home'),
  _Tab(AppDestination.learning, 'My learning', 'Learning'),
  _Tab(AppDestination.mentorship, 'Mentorship', 'Mentorship'),
  _Tab(AppDestination.jobs, 'Jobs & opportunities', 'Jobs'),
];

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  int _unread = 0;
  Map<String, dynamic>? _user;
  StreamSubscription<String>? _notificationSubscription;

  late final List<Widget> _pages = [
    DashboardScreen(onOpen: _open),
    const LearningScreen(),
    const MentorshipScreen(),
    const JobsScreen(),
  ];

  @override
  void initState() {
    super.initState();

    SyncService.instance.startAutoSync();
    SyncService.instance.dataVersion.addListener(_loadUnread);
    ParticipantDataService.instance.profileVersion.addListener(_loadUser);
    _loadUnread();
    _loadUser();

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
    ParticipantDataService.instance.profileVersion.removeListener(_loadUser);
    _notificationSubscription?.cancel();
    super.dispose();
  }

  Future<void> _loadUnread() async {
    final items = await LocalDatabase.instance.readCollection('notifications');
    final unread = items.where((item) => item['read_at'] == null).length;
    if (mounted) setState(() => _unread = unread);
  }

  Future<void> _loadUser() async {
    final user = await ApiService.instance.currentUser();
    if (mounted) setState(() => _user = user);
  }

  void _selectTab(int index) => setState(() => _index = index);

  void _push(Widget screen) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
  }

  /// Opens any destination: switches tab for bottom-bar places, pushes a
  /// screen for the rest.
  void _open(AppDestination destination) {
    final tab = _tabs.indexWhere((t) => t.destination == destination);
    if (tab >= 0) {
      _selectTab(tab);
      return;
    }

    switch (destination) {
      case AppDestination.careerDocuments:
        _push(const CareerDocumentsScreen());
      case AppDestination.assignments:
        _push(const AssignmentsScreen());
      case AppDestination.progress:
        _push(const ProgressScreen());
      case AppDestination.events:
        _push(CachedListScreen.events());
      case AppDestination.announcements:
        _push(CachedListScreen.announcements());
      case AppDestination.downloads:
        _push(const DownloadsScreen());
      case AppDestination.notifications:
        _push(const NotificationsScreen());
      case AppDestination.profile:
        _push(const ProfileScreen());
      case AppDestination.settings:
        _push(const SettingsScreen());
      case AppDestination.about:
        _push(const AboutScreen());
      case AppDestination.help:
        _push(const HelpSupportScreen());
      case AppDestination.signOut:
        _signOut();
      default:
        break;
    }
  }

  void _onDrawerSelected(AppDestination destination) {
    Navigator.of(context).pop(); // close the drawer first
    _open(destination);
  }

  Future<void> _signOut() async {
    final confirmed = await confirmDialog(
      context,
      title: 'Sign out?',
      message: 'Your downloaded lessons and saved data will be removed from this '
          'device. Changes that have not synced yet will be lost.',
      confirmLabel: 'Sign out',
      destructive: true,
    );
    if (!confirmed || !mounted) return;

    await AuthFlow.signOut();
    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  void _openNotificationDestination(String destination) {
    final value = destination.toLowerCase();

    AppDestination? target;
    if (value.contains('course') ||
        value.contains('lesson') ||
        value.contains('learning')) {
      target = AppDestination.learning;
    } else if (value.contains('assign') || value.contains('assessment')) {
      target = AppDestination.assignments;
    } else if (value.contains('mentor')) {
      target = AppDestination.mentorship;
    } else if (value.contains('job')) {
      target = AppDestination.jobs;
    } else if (value.contains('progress')) {
      target = AppDestination.progress;
    } else if (value.contains('dashboard')) {
      target = AppDestination.home;
    }

    if (!mounted) return;
    _open(target ?? AppDestination.notifications);
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
      if (mounted) showAppSnackBar(context, "You're all up to date.");
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error, onRetry: _sync);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tab = _tabs[_index];

    return Scaffold(
      drawer: AppDrawer(
        name: _user?['name']?.toString(),
        email: _user?['email']?.toString(),
        selected: tab.destination,
        unreadNotifications: _unread,
        onSelected: _onDrawerSelected,
      ),
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
                  : const Icon(Icons.sync_rounded),
            ),
          ),
          IconButton(
            tooltip: _unread == 0
                ? 'Notifications'
                : 'Notifications, $_unread unread',
            onPressed: () => _open(AppDestination.notifications),
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
                icon: Icon(t.destination.icon),
                selectedIcon: Icon(t.destination.selectedIcon),
                label: t.label,
                tooltip: t.title,
              ),
          ],
        ),
      ),
    );
  }
}
