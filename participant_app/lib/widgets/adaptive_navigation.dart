import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';

/// One top-level destination for [AdaptiveNavigation].
class AdaptiveDestination {
  const AdaptiveDestination({
    required this.icon,
    required this.selectedIcon,
    required this.label,
    this.tooltip,
  });

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final String? tooltip;
}

/// Tablets (and large windows) get a [NavigationRail] on the left and the
/// content kept to a centred column instead of stretching across the whole
/// screen. Phones show [body] as is, with [bottomBar] as the Scaffold's
/// bottom navigation.
class AdaptiveNavigation extends StatelessWidget {
  const AdaptiveNavigation({
    super.key,
    required this.destinations,
    required this.selectedIndex,
    required this.onSelected,
    required this.body,
  });

  /// Width from which the rail replaces the bottom bar (Material "medium").
  static const double railBreakpoint = 600;

  /// Width from which the rail shows its labels beside the icons.
  static const double extendedRailBreakpoint = 1200;

  /// Phones held sideways are wide but short; five rail items don't fit, so
  /// they keep the bottom bar.
  static const double railMinHeight = 480;

  final List<AdaptiveDestination> destinations;
  final int selectedIndex;
  final ValueChanged<int> onSelected;
  final Widget body;

  static bool usesRail(Size size) =>
      size.width >= railBreakpoint && size.height >= railMinHeight;

  /// The phone bottom bar, or null when the rail is shown instead. Use it as
  /// the Scaffold's bottomNavigationBar so it stays behind the keyboard.
  static Widget? bottomBar(
    BuildContext context, {
    required List<AdaptiveDestination> destinations,
    required int selectedIndex,
    required ValueChanged<int> onSelected,
  }) {
    if (usesRail(MediaQuery.sizeOf(context))) return null;

    // Five labels can't grow without limit on a phone-width bar; clamp
    // like the platform navigation bars do. Screen content still scales
    // fully, and each destination keeps its tooltip and semantics label.
    return MediaQuery.withClampedTextScaling(
      maxScaleFactor: 1.3,
      child: NavigationBar(
        selectedIndex: selectedIndex,
        onDestinationSelected: onSelected,
        destinations: [
          for (final d in destinations)
            NavigationDestination(
              icon: Icon(d.icon),
              selectedIcon: Icon(d.selectedIcon),
              label: d.label,
              tooltip: d.tooltip,
            ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    if (!usesRail(size)) return body;

    final extended = size.width >= extendedRailBreakpoint;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        MediaQuery.withClampedTextScaling(
          maxScaleFactor: 1.3,
          child: SafeArea(
            right: false,
            child: NavigationRail(
              selectedIndex: selectedIndex,
              onDestinationSelected: onSelected,
              extended: extended,
              labelType: extended ? NavigationRailLabelType.none : NavigationRailLabelType.all,
              destinations: [
                for (final d in destinations)
                  NavigationRailDestination(
                    icon: Icon(d.icon),
                    selectedIcon: Icon(d.selectedIcon),
                    label: Text(d.label),
                  ),
              ],
            ),
          ),
        ),
        const VerticalDivider(width: 1, thickness: 1),
        Expanded(
          child: Align(
            alignment: Alignment.topCenter,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: AppSpacing.contentMaxWidth),
              // Fill the column whatever the screen widget sizes itself to.
              child: SizedBox.expand(child: body),
            ),
          ),
        ),
      ],
    );
  }
}
