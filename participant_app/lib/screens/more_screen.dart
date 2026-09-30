import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/auth_flow.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'about_screen.dart';
import 'assignments_screen.dart';
import 'cached_list_screen.dart';
import 'downloads_screen.dart';
import 'login_screen.dart';
import 'notifications_screen.dart';
import 'settings_screen.dart';

class MoreScreen extends StatefulWidget {
  const MoreScreen({super.key});

  @override
  State<MoreScreen> createState() => _MoreScreenState();
}

class _MoreScreenState extends State<MoreScreen> {
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    ApiService.instance.currentUser().then((user) {
      if (mounted) setState(() => _user = user);
    });
  }

  void _push(Widget screen) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
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

  Widget _tile(IconData icon, String title, VoidCallback onTap, {String? subtitle}) {
    return ListTile(
      leading: Icon(icon),
      title: Text(title),
      subtitle: subtitle == null ? null : Text(subtitle),
      trailing: const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }

  Widget _group(List<Widget> tiles) {
    return Card(
      child: Column(
        children: [
          for (var i = 0; i < tiles.length; i++) ...[
            if (i > 0) const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
            tiles[i],
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final name = _user?['name']?.toString() ?? 'Participant';
    final email = _user?['email']?.toString() ?? '';
    final initials = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((p) => p.isNotEmpty)
        .take(2)
        .map((p) => p[0].toUpperCase())
        .join();

    return ListView(
      padding: AppSpacing.listPadding,
      children: [
        const SizedBox(height: AppSpacing.sm),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: scheme.primary,
                  foregroundColor: scheme.onPrimary,
                  child: Text(initials.isEmpty ? '?' : initials,
                      style: theme.textTheme.titleMedium?.copyWith(color: scheme.onPrimary)),
                ),
                const SizedBox(width: AppSpacing.lg),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name, style: theme.textTheme.titleMedium),
                      if (email.isNotEmpty)
                        Text(email,
                            style: theme.textTheme.bodyMedium
                                ?.copyWith(color: scheme.onSurfaceVariant)),
                      if ((_user?['phone']?.toString() ?? '').isNotEmpty)
                        Text(_user!['phone'].toString(),
                            style: theme.textTheme.bodyMedium
                                ?.copyWith(color: scheme.onSurfaceVariant)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SectionHeader(title: 'Learning'),
        _group([
          _tile(Icons.assignment_outlined, 'Assignments', () => _push(const AssignmentsScreen())),
          _tile(Icons.event_outlined, 'Events', () => _push(CachedListScreen.events())),
          _tile(Icons.campaign_outlined, 'Announcements',
              () => _push(CachedListScreen.announcements())),
          _tile(Icons.download_done_outlined, 'Offline downloads',
              () => _push(const DownloadsScreen())),
        ]),
        const SectionHeader(title: 'Settings'),
        _group([
          _tile(Icons.notifications_outlined, 'Notifications',
              () => _push(const NotificationsScreen())),
          _tile(Icons.data_saver_on_outlined, 'Data & offline', () => _push(const SettingsScreen()),
              subtitle: 'Wi-Fi only downloads, sync status'),
        ]),
        const SectionHeader(title: 'About'),
        _group([
          _tile(Icons.info_outline, 'About & privacy policy', () => _push(const AboutScreen())),
        ]),
        const SizedBox(height: AppSpacing.xl),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: scheme.error,
            side: BorderSide(color: scheme.error),
          ),
          onPressed: _signOut,
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
        ),
      ],
    );
  }
}
