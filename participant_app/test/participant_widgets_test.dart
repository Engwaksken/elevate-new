import 'package:elevateher360_participant/core/assignment_info.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/screens/assignments_screen.dart';
import 'package:elevateher360_participant/screens/dashboard_screen.dart';
import 'package:elevateher360_participant/screens/progress_screen.dart';
import 'package:elevateher360_participant/widgets/app_drawer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _app(Widget child, {ThemeData? theme}) => MaterialApp(
      theme: theme ?? AppTheme.light(),
      home: child,
    );

/// Small phone (360x640 dp) with the given text scale.
void _smallPhone(WidgetTester tester, {double textScale = 2}) {
  tester.view.physicalSize = const Size(360, 640);
  tester.view.devicePixelRatio = 1;
  tester.platformDispatcher.textScaleFactorTestValue = textScale;
  addTearDown(tester.view.reset);
  addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
}

final _progress = ProgressData({
  'summary': {
    'courses_enrolled': 2, 'courses_completed': 1,
    'lessons_total': 12, 'lessons_completed': 5, 'time_spent_seconds': 12000,
    'assignments_total': 4, 'assignments_submitted': 2, 'assignments_graded': 1,
    'assignments_pending': 1, 'assignments_overdue': 1, 'extension_requests_pending': 1,
    'mentorship_sessions_total': 6, 'mentorship_sessions_attended': 3,
    'mentorship_sessions_missed': 1, 'mentorship_sessions_upcoming': 2,
    'events_attended': 1,
  },
  'courses': [
    {'id': 1, 'title': 'Digital Marketing', 'status': 'in_progress', 'progress_percent': 41.67,
     'lessons_total': 12, 'lessons_completed': 5, 'time_spent_seconds': 5400,
     'last_activity_at': '2026-09-30T10:00:00+00:00'},
  ],
  'recent_activity': [
    {'type': 'lesson_completed', 'title': 'Lesson 3', 'at': '2026-09-30T10:00:00+00:00'},
    {'type': 'assignment_submitted', 'title': 'Brief', 'at': '2026-09-29T10:00:00+00:00'},
  ],
});

void main() {
  group('AppDrawer', () {
    testWidgets('lists every destination, highlights the current one and '
        'reports taps', (tester) async {
      AppDestination? tapped;
      final semantics = tester.ensureSemantics();
      // Tall enough that every destination is built without scrolling.
      tester.view.physicalSize = const Size(800, 2000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final key = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(_app(Scaffold(
        key: key,
        drawer: AppDrawer(
          name: 'Jane Achieng',
          email: 'jane@example.org',
          selected: AppDestination.learning,
          unreadNotifications: 3,
          onSelected: (d) => tapped = d,
        ),
        body: const SizedBox(),
      )));
      key.currentState!.openDrawer();
      await tester.pumpAndSettle();

      expect(find.text('Jane Achieng'), findsOneWidget);
      expect(find.text('View profile'), findsOneWidget);
      final drawer = tester.widget<NavigationDrawer>(find.byType(NavigationDrawer));
      expect(drawer.selectedIndex, AppDestination.values.indexOf(AppDestination.learning));

      for (final d in AppDestination.values) {
        expect(find.text(d.label), findsOneWidget, reason: d.label);
      }
      expect(find.text('3'), findsOneWidget); // unread badge
      expect(find.bySemanticsLabel(RegExp('Notifications, 3 unread')), findsOneWidget);

      await tester.tap(find.text('View profile'));
      expect(tapped, AppDestination.profile);
      await tester.tap(find.text('Sign out'));
      expect(tapped, AppDestination.signOut);
      semantics.dispose();
    });

    testWidgets('meets tap-target guidelines', (tester) async {
      final handle = tester.ensureSemantics();
      final key = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(_app(Scaffold(
        key: key,
        drawer: AppDrawer(
          name: 'Jane',
          email: 'jane@example.org',
          selected: AppDestination.home,
          onSelected: (_) {},
        ),
        body: const SizedBox(),
      )));
      key.currentState!.openDrawer();
      await tester.pumpAndSettle();
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      handle.dispose();
    });

    testWidgets('survives 2x text on a small phone', (tester) async {
      _smallPhone(tester);
      final key = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(_app(Scaffold(
        key: key,
        drawer: AppDrawer(
          name: 'Jane Achieng Wanjiru-Otieno',
          email: 'jane.achieng.wanjiru@example.org',
          selected: AppDestination.home,
          unreadNotifications: 120,
          onSelected: (_) {},
        ),
        body: const SizedBox(),
      )));
      key.currentState!.openDrawer();
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
    });
  });

  group('ProgressView', () {
    testWidgets('shows stats, courses and the activity timeline', (tester) async {
      await tester.pumpWidget(_app(Scaffold(body: ProgressView(data: _progress))));
      expect(find.text('5/12'), findsWidgets);
      expect(find.text('3h 20m'), findsOneWidget); // 12000 s reading time
      await tester.scrollUntilVisible(find.text('Digital Marketing'), 200);
      expect(find.text('Digital Marketing'), findsOneWidget);
      expect(find.text('Time spent: 1h 30m'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('Lesson 3'), 200);
      expect(find.text('Lesson 3'), findsOneWidget);
    });

    testWidgets('empty state', (tester) async {
      await tester.pumpWidget(_app(Scaffold(body: ProgressView(data: ProgressData({})))));
      expect(find.text('Your progress story starts soon'), findsOneWidget);
    });

    for (final theme in [AppTheme.light(), AppTheme.dark()]) {
      testWidgets('2x text on a small phone (${theme.brightness.name})', (tester) async {
        _smallPhone(tester);
        await tester.pumpWidget(_app(Scaffold(body: ProgressView(data: _progress)), theme: theme));
        await tester.pump();
        expect(tester.takeException(), isNull);
        await tester.drag(find.byType(ListView), const Offset(0, -2000));
        await tester.pump();
        expect(tester.takeException(), isNull);
      });
    }
  });

  group('AssignmentCard', () {
    testWidgets('overdue work shows the extension card instead of Submit',
        (tester) async {
      var requested = false;
      var submitted = false;
      final a = AssignmentInfo({
        'id': 3,
        'title': 'Business brief',
        'due_at': '2026-09-27T09:00:00',
        'is_overdue': true,
        'can_submit': false,
        'can_request_extension': true,
      }, now: DateTime(2026, 9, 30, 10));
      await tester.pumpWidget(_app(Scaffold(
        body: ListView(children: [
          AssignmentCard(
            assignment: a,
            onSubmit: () => submitted = true,
            onRequestExtension: () => requested = true,
          ),
        ]),
      )));

      expect(find.text('Overdue'), findsOneWidget);
      expect(find.textContaining('Overdue by 3 days', findRichText: true), findsOneWidget);
      expect(
        find.text('The due date has passed. You can ask your instructor for more time.'),
        findsOneWidget,
      );
      expect(find.text('Submit work'), findsNothing);
      await tester.tap(find.text('Request extension'));
      expect(requested, isTrue);
      expect(submitted, isFalse);
    });

    testWidgets('pending extension shows its state and hides the button',
        (tester) async {
      final a = AssignmentInfo({
        'id': 3,
        'title': 'Business brief',
        'due_at': '2026-09-27T09:00:00',
        'is_overdue': true,
        'can_submit': false,
        'can_request_extension': false,
        'extension_request': {'id': 1, 'status': 'pending', 'reason': 'I was unwell'},
      }, now: DateTime(2026, 9, 30, 10));
      await tester.pumpWidget(_app(Scaffold(
        body: ListView(children: [
          AssignmentCard(assignment: a, onSubmit: () {}, onRequestExtension: () {}),
        ]),
      )));
      expect(find.text('Extension pending'), findsOneWidget);
      expect(find.text('Extension request sent'), findsOneWidget);
      expect(find.text('Request extension'), findsNothing);
    });

    testWidgets('2x text on a small phone', (tester) async {
      _smallPhone(tester);
      final a = AssignmentInfo({
        'id': 3,
        'title': 'A rather long assignment title about marketing plans',
        'type': 'assignment',
        'course': {'title': 'Digital Marketing'},
        'due_at': '2026-09-27T09:00:00',
        'is_overdue': true,
        'can_submit': false,
        'can_request_extension': true,
        'submissions_count': 0,
        'attempts_remaining': 1,
        'extension_request': {'status': 'rejected', 'reviewer_note': 'Please submit on time next time.'},
      }, now: DateTime(2026, 9, 30, 10));
      await tester.pumpWidget(_app(Scaffold(
        body: ListView(children: [
          AssignmentCard(assignment: a, onSubmit: () {}, onRequestExtension: () {}),
        ]),
      )));
      await tester.pump();
      expect(tester.takeException(), isNull);
    });
  });

  group('Dashboard header', () {
    const summary = ProgressSnapshot(
      lessonsCompleted: 5,
      lessonsTotal: 12,
      timeSpentSeconds: 12000,
      assignmentsSubmitted: 2,
      assignmentsTotal: 4,
    );

    testWidgets('greets her by first name with progress', (tester) async {
      await tester.pumpWidget(_app(Scaffold(
        body: ListView(children: [
          GreetingHeader(name: 'Jane Achieng', summary: summary, now: DateTime(2026, 9, 30, 9)),
          ProgressSummaryRow(summary: summary, onTap: () {}),
        ]),
      )));
      await tester.pump();
      expect(find.text('Good morning, Jane'), findsOneWidget);
      expect(find.text('5 of 12 lessons complete'), findsOneWidget);
      expect(find.text('42%'), findsOneWidget);
      expect(find.text('3h 20m'), findsOneWidget);
    });

    testWidgets('2x text on a small phone', (tester) async {
      _smallPhone(tester);
      await tester.pumpWidget(_app(Scaffold(
        body: ListView(children: [
          GreetingHeader(name: 'Jane', summary: summary, now: DateTime(2026, 9, 30, 19)),
          ProgressSummaryRow(summary: summary, onTap: () {}),
        ]),
      )));
      await tester.pump();
      expect(tester.takeException(), isNull);
    });
  });
}
