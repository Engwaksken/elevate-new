import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import 'decorations.dart';
import 'user_avatar.dart';

/// Every place the drawer can take the participant, in display order.
enum AppDestination {
  home('Home', Icons.home_outlined, Icons.home_rounded),
  learning('My learning', Icons.menu_book_outlined, Icons.menu_book_rounded),
  assignments('Assignments', Icons.assignment_outlined, Icons.assignment_rounded),
  progress('My progress', Icons.insights_outlined, Icons.insights_rounded),
  mentorship('Mentorship', Icons.diversity_3_outlined, Icons.diversity_3_rounded),
  jobs('Jobs & opportunities', Icons.work_outline_rounded, Icons.work_rounded),
  careerDocuments('Resumes & cover letters', Icons.description_outlined, Icons.description_rounded),
  events('Events', Icons.event_outlined, Icons.event_rounded),
  announcements('Announcements', Icons.campaign_outlined, Icons.campaign_rounded),
  downloads('Downloads', Icons.download_for_offline_outlined, Icons.download_for_offline_rounded),
  notifications('Notifications', Icons.notifications_outlined, Icons.notifications_rounded),
  profile('My profile', Icons.person_outline_rounded, Icons.person_rounded),
  settings('Settings', Icons.tune_outlined, Icons.tune_rounded),
  about('About', Icons.info_outline_rounded, Icons.info_rounded),
  help('Help & support', Icons.support_agent_outlined, Icons.support_agent_rounded),
  signOut('Sign out', Icons.logout_rounded, Icons.logout_rounded);

  const AppDestination(this.label, this.icon, this.selectedIcon);

  final String label;
  final IconData icon;
  final IconData selectedIcon;
}

/// Section breaks (a divider and heading are drawn before these).
const _sections = <AppDestination, String>{
  AppDestination.events: 'Community',
  AppDestination.downloads: 'Your space',
  AppDestination.about: 'Support',
};

/// Material 3 navigation drawer with a warm profile header. Stateless: the
/// home shell passes the signed-in user, unread count and current page.
class AppDrawer extends StatelessWidget {
  const AppDrawer({
    super.key,
    required this.name,
    required this.email,
    required this.selected,
    required this.onSelected,
    this.unreadNotifications = 0,
  });

  final String? name;
  final String? email;
  final AppDestination selected;
  final ValueChanged<AppDestination> onSelected;
  final int unreadNotifications;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    const all = AppDestination.values;

    final children = <Widget>[
      _DrawerHeader(
        name: name,
        email: email,
        onViewProfile: () => onSelected(AppDestination.profile),
      ),
      const SizedBox(height: AppSpacing.sm),
    ];

    for (final d in all) {
      if (_sections.containsKey(d)) {
        children.add(const Padding(
          padding: EdgeInsets.fromLTRB(28, AppSpacing.sm, 28, AppSpacing.xs),
          child: Divider(),
        ));
        children.add(Padding(
          padding: const EdgeInsets.fromLTRB(28, AppSpacing.sm, 16, AppSpacing.sm),
          child: Semantics(
            header: true,
            child: Text(
              _sections[d]!,
              style: theme.textTheme.titleSmall?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ),
        ));
      }
      if (d == AppDestination.signOut) {
        children.add(const Padding(
          padding: EdgeInsets.fromLTRB(28, AppSpacing.sm, 28, AppSpacing.sm),
          child: Divider(),
        ));
      }
      children.add(_destination(context, d));
    }
    children.add(const SizedBox(height: AppSpacing.lg));

    // Labels may wrap to two lines at large text sizes; grow the tiles so
    // they never clip (and stay well above the 48 dp minimum).
    final scale = MediaQuery.textScalerOf(context).scale(1);
    final drawerTheme = NavigationDrawerTheme.of(context);
    return NavigationDrawerTheme(
      data: drawerTheme.copyWith(tileHeight: scale > 1.3 ? 44 * scale : 52),
      child: NavigationDrawer(
        selectedIndex: all.indexOf(selected),
        onDestinationSelected: (index) => onSelected(all[index]),
        children: children,
      ),
    );
  }

  Widget _destination(BuildContext context, AppDestination d) {
    final scheme = Theme.of(context).colorScheme;
    Widget icon(IconData data) {
      final base = Icon(
        data,
        color: d == AppDestination.signOut ? scheme.error : null,
      );
      if (d != AppDestination.notifications || unreadNotifications == 0) return base;
      return Badge(
        label: Text(unreadNotifications > 99 ? '99+' : '$unreadNotifications'),
        child: base,
      );
    }

    final label = d == AppDestination.notifications && unreadNotifications > 0
        ? '${d.label}, $unreadNotifications unread'
        : d.label;

    return NavigationDrawerDestination(
      icon: icon(d.icon),
      selectedIcon: icon(d.selectedIcon),
      // Expanded: the destination lays the label out directly in a Row.
      label: Expanded(
        child: Padding(
          padding: const EdgeInsets.only(right: AppSpacing.lg),
          child: Semantics(
            label: label,
            excludeSemantics: true,
            child: Text(
              d.label,
              style: d == AppDestination.signOut ? TextStyle(color: scheme.error) : null,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ),
      ),
    );
  }
}

class _DrawerHeader extends StatelessWidget {
  const _DrawerHeader({
    required this.name,
    required this.email,
    required this.onViewProfile,
  });

  final String? name;
  final String? email;
  final VoidCallback onViewProfile;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final brand = BrandColors.of(context);
    final displayName = (name ?? '').trim().isEmpty ? 'Welcome' : name!.trim();

    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.md, AppSpacing.md, 0),
      child: GradientHeader(
        seed: 1,
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          AppSpacing.lg,
          AppSpacing.lg,
          AppSpacing.sm,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                UserAvatar(name: name, radius: 30, ring: true),
                const Spacer(),
                ExcludeSemantics(
                  child: ClipOval(
                    child: Container(
                      color: Colors.white,
                      padding: const EdgeInsets.all(2),
                      child: Image.asset('assets/logo.png', width: 36, height: 36),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              displayName,
              style: theme.textTheme.titleMedium?.copyWith(
                color: brand.onHeader,
                fontWeight: FontWeight.w700,
              ),
            ),
            if ((email ?? '').isNotEmpty)
              Text(
                email!,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: brand.onHeader.withValues(alpha: 0.9),
                ),
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: AppSpacing.xs),
            Semantics(
              button: true,
              label: 'View profile',
              excludeSemantics: true,
              child: TextButton.icon(
                style: TextButton.styleFrom(
                  foregroundColor: brand.onHeader,
                  padding: EdgeInsets.zero,
                  minimumSize: const Size(0, AppSpacing.minTouchTarget),
                ),
                onPressed: onViewProfile,
                iconAlignment: IconAlignment.end,
                icon: const Icon(Icons.arrow_forward_rounded, size: 18),
                label: const Text('View profile'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
