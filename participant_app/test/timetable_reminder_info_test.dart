import 'package:elevateher360_participant/core/timetable_reminder_info.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
      'timetable reminders fire ten minutes before the session across timezones',
      () {
    const reminder = TimetableReminderInfo(
        {'source_id': 7, 'scheduled_at': '2026-10-05T09:30:00+03:00'});
    expect(reminder.notifyAt, DateTime.utc(2026, 10, 5, 6, 20));
  });

  test('rescheduling keeps the same stable notification ID', () {
    const first = TimetableReminderInfo(
        {'source_id': 7, 'scheduled_at': '2026-10-05T06:30:00Z'});
    const updated = TimetableReminderInfo(
        {'source_id': 7, 'scheduled_at': '2026-10-06T08:00:00Z'});
    const other = TimetableReminderInfo({'source_id': 8});
    expect(first.notificationId, updated.notificationId);
    expect(first.notificationId, isNot(other.notificationId));
    expect(first.notificationId, greaterThanOrEqualTo(0));
  });
}
