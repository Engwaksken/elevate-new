import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/widgets/adaptive_navigation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const destinations = [
    AdaptiveDestination(icon: Icons.home_outlined, selectedIcon: Icons.home, label: 'Home'),
    AdaptiveDestination(icon: Icons.school_outlined, selectedIcon: Icons.school, label: 'Learn'),
    AdaptiveDestination(icon: Icons.assignment_outlined, selectedIcon: Icons.assignment, label: 'Tasks'),
    AdaptiveDestination(icon: Icons.groups_outlined, selectedIcon: Icons.groups, label: 'Mentor'),
    AdaptiveDestination(icon: Icons.work_outline, selectedIcon: Icons.work, label: 'Jobs'),
  ];

  Future<int> pumpAt(WidgetTester tester, Size size) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    var selected = 0;
    await tester.pumpWidget(MaterialApp(
      home: Builder(
        builder: (context) => Scaffold(
          body: AdaptiveNavigation(
            destinations: destinations,
            selectedIndex: 0,
            onSelected: (i) => selected = i,
            body: const ColoredBox(key: Key('content'), color: Colors.white),
          ),
          bottomNavigationBar: AdaptiveNavigation.bottomBar(
            context,
            destinations: destinations,
            selectedIndex: 0,
            onSelected: (i) => selected = i,
          ),
        ),
      ),
    ));
    await tester.tap(find.text('Jobs'));
    await tester.pump();
    return selected;
  }

  testWidgets('phones use the bottom bar, in portrait and landscape', (tester) async {
    for (final size in const [Size(390, 844), Size(844, 390)]) {
      expect(await pumpAt(tester, size), 4);
      expect(find.byType(NavigationBar), findsOneWidget);
      expect(find.byType(NavigationRail), findsNothing);
    }
  });

  testWidgets('tablets in portrait get a labelled rail and a centred content column', (tester) async {
    expect(await pumpAt(tester, const Size(1280, 1280) / 1.6), 4); // 800 x 800
    expect(find.byType(NavigationBar), findsNothing);
    final rail = tester.widget<NavigationRail>(find.byType(NavigationRail));
    expect(rail.extended, isFalse);
    expect(rail.labelType, NavigationRailLabelType.all);
  });

  testWidgets('large tablets in landscape get the extended rail and content no wider than the readable maximum', (tester) async {
    expect(await pumpAt(tester, const Size(1366, 1024)), 4);
    expect(tester.widget<NavigationRail>(find.byType(NavigationRail)).extended, isTrue);
    expect(tester.getSize(find.byKey(const Key('content'))).width, AppSpacing.contentMaxWidth);
  });
}
