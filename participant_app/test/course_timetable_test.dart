import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/widgets/course_timetable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Future<void> open(
      WidgetTester tester, List<Map<String, dynamic>> slots) async {
    await tester.pumpWidget(MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
          body: SingleChildScrollView(child: CourseTimetable(slots: slots)),
        )));
    await tester.tap(find.text('Course timetable'));
    await tester.pumpAndSettle();
  }

  testWidgets(
      'shows session times, timezone, venue and notes from cached course data',
      (tester) async {
    await open(tester, [
      {
        'title': 'Practical workshop',
        'date_label': 'Thu, 15 Oct 2026',
        'time_label': '09:00 – 11:00',
        'timezone': 'Africa/Kampala',
        'venue': 'Room 2',
        'notes': 'Bring your laptop.',
        'status': 'scheduled',
        'meeting_link': 'https://meet.example.test/session',
      }
    ]);
    expect(find.text('Practical workshop'), findsOneWidget);
    expect(find.textContaining('09:00 – 11:00'), findsOneWidget);
    expect(find.textContaining('Africa/Kampala'), findsOneWidget);
    expect(find.text('Venue: Room 2'), findsOneWidget);
    expect(find.text('Bring your laptop.'), findsOneWidget);
    expect(find.text('Online meeting'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('cancelled sessions remain visible without a join button',
      (tester) async {
    await open(tester, [
      {
        'title': 'Cancelled workshop',
        'status': 'cancelled',
        'date_label': 'Thu, 15 Oct 2026',
        'time_label': '09:00 – 11:00',
        'timezone': 'Africa/Kampala',
        'meeting_link': 'https://meet.example.test/session',
      }
    ]);
    expect(find.text('Cancelled'), findsOneWidget);
    expect(find.text('Cancelled workshop'), findsOneWidget);
    expect(find.text('Online meeting'), findsNothing);
  });

  testWidgets('empty timetable has a helpful message', (tester) async {
    await open(tester, []);
    expect(find.text('No sessions scheduled yet'), findsOneWidget);
    expect(find.text('Your instructor has not added any time slots yet.'),
        findsOneWidget);
  });
}
