import 'package:elevateher360_participant/core/app_config.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/screens/help_support_screen.dart';
import 'package:elevateher360_participant/widgets/progress_widgets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _app(Widget child) => MaterialApp(
      theme: AppTheme.light(),
      home: Scaffold(body: child),
    );

void main() {
  group('StatCard compact', () {
    testWidgets('puts the value on the same row as the icon', (tester) async {
      await tester.pumpWidget(_app(const SizedBox(
        width: 170,
        child: StatCard(
          icon: Icons.assignment_outlined,
          value: '12',
          label: 'Assignments',
          compact: true,
        ),
      )));

      final icon = tester.getCenter(find.byIcon(Icons.assignment_outlined));
      final value = tester.getCenter(find.text('12'));
      final label = tester.getTopLeft(find.text('Assignments'));

      expect((icon.dy - value.dy).abs(), lessThan(4));
      expect(value.dx, greaterThan(icon.dx));
      expect(label.dy, greaterThan(value.dy));
    });

    testWidgets('is shorter than the default layout', (tester) async {
      Future<double> heightOf({required bool compact}) async {
        await tester.pumpWidget(_app(Align(
          alignment: Alignment.topLeft,
          child: SizedBox(
            width: 170,
            child: StatCard(
              key: ValueKey(compact),
              icon: Icons.event_outlined,
              value: '1',
              label: 'Events',
              compact: compact,
            ),
          ),
        )));
        return tester.getSize(find.byType(StatCard)).height;
      }

      final regular = await heightOf(compact: false);
      final compact = await heightOf(compact: true);
      expect(compact, lessThan(regular));
    });

    testWidgets('does not overflow at 2x text on a small phone', (tester) async {
      tester.view.physicalSize = const Size(360, 640);
      tester.view.devicePixelRatio = 1;
      tester.platformDispatcher.textScaleFactorTestValue = 2;
      addTearDown(tester.view.reset);
      addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);

      await tester.pumpWidget(_app(const SingleChildScrollView(
        child: ResponsiveGrid(minTileWidth: 140, children: [
          StatCard(icon: Icons.campaign_outlined, value: '1250', label: 'Announcements', compact: true),
          StatCard(icon: Icons.event_outlined, value: '3', label: 'Events', compact: true),
        ]),
      )));

      expect(tester.takeException(), isNull);
    });
  });

  group('SupportInfo', () {
    test('uses the admin settings when present', () {
      const info = SupportInfo({
        'email': 'help@example.org',
        'phone': '+256 700 000 001',
        'whatsapp': '+256 700 000 002',
        'whatsapp_url': 'https://wa.me/256700000002',
        'hours': 'Mon–Fri',
        'introduction': 'We are here to help.',
      });

      expect(info.email, 'help@example.org');
      expect(info.phone, '+256 700 000 001');
      expect(info.whatsappUrl, 'https://wa.me/256700000002');
      expect(info.hours, 'Mon–Fri');
      expect(info.introduction, 'We are here to help.');
    });

    test('falls back to the app support email and hides blank fields', () {
      const info = SupportInfo({'email': '  ', 'phone': '', 'address': null});

      expect(info.email, AppConfig.supportEmail);
      expect(info.phone, isNull);
      expect(info.address, isNull);
      expect(SupportInfo.fallback.email, AppConfig.supportEmail);
      expect(SupportInfo.fallback.introduction, isNotEmpty);
    });
  });
}
