import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/assignment_info.dart';
import 'package:elevateher360_participant/core/formatters.dart';
import 'package:elevateher360_participant/core/network/app_exception.dart';
import 'package:elevateher360_participant/core/profile_fields.dart';
import 'package:elevateher360_participant/core/reading_time_accumulator.dart';
import 'package:elevateher360_participant/core/reading_time_ledger.dart';
import 'package:elevateher360_participant/core/session_info.dart';
import 'package:elevateher360_participant/services/reading_time_service.dart';
import 'package:flutter_test/flutter_test.dart';

/// Manually advanced clock for the accumulator.
class FakeClock {
  FakeClock(this.now);

  DateTime now;

  void advance(Duration d) => now = now.add(d);

  DateTime call() => now;
}

void main() {
  group('ReadingTimeAccumulator', () {
    late FakeClock clock;
    late ReadingTimeAccumulator acc;

    setUp(() {
      clock = FakeClock(DateTime(2026, 9, 30, 10));
      acc = ReadingTimeAccumulator(clock: clock.call);
    });

    test('counts only while visible and in the foreground', () {
      clock.advance(const Duration(seconds: 30));
      expect(acc.pendingSeconds, 0, reason: 'not visible yet');

      acc.setVisible(true);
      clock.advance(const Duration(seconds: 40));
      expect(acc.pendingSeconds, 40);
      expect(acc.isCounting, isTrue);

      acc.setForeground(false); // app backgrounded
      clock.advance(const Duration(minutes: 3));
      expect(acc.pendingSeconds, 40);
      expect(acc.isCounting, isFalse);

      acc.setForeground(true); // back
      clock.advance(const Duration(seconds: 20));
      expect(acc.pendingSeconds, 60);

      acc.setVisible(false); // another screen on top
      clock.advance(const Duration(minutes: 1));
      expect(acc.pendingSeconds, 60);
    });

    test('stops at the 5 minute idle cutoff and resumes on interaction', () {
      acc.setVisible(true);
      clock.advance(const Duration(minutes: 4));
      acc.recordInteraction(); // scroll
      clock.advance(const Duration(minutes: 12)); // phone left on the table
      // 4 min + 5 min after the last interaction, not 16 min.
      expect(acc.pendingSeconds, 9 * 60);
      expect(acc.isIdle, isTrue);
      expect(acc.isCounting, isFalse);

      acc.recordInteraction(); // tap: counting resumes from now
      expect(acc.isCounting, isTrue);
      clock.advance(const Duration(seconds: 30));
      expect(acc.pendingSeconds, 9 * 60 + 30);
    });

    test('returning to the foreground counts as activity', () {
      acc.setVisible(true);
      clock.advance(const Duration(minutes: 6)); // idle after 5
      expect(acc.pendingSeconds, 300);
      acc.setForeground(false);
      clock.advance(const Duration(hours: 1));
      acc.setForeground(true);
      clock.advance(const Duration(seconds: 10));
      expect(acc.pendingSeconds, 310);
    });

    test('takeSeconds drains whole seconds and keeps the remainder', () {
      acc.setVisible(true);
      clock.advance(const Duration(milliseconds: 2500));
      expect(acc.takeSeconds(), 2);
      expect(acc.takeSeconds(), 0, reason: 'never returned twice');
      clock.advance(const Duration(milliseconds: 600));
      // 500 ms remainder + 600 ms = 1.1 s.
      expect(acc.takeSeconds(), 1);
    });
  });

  group('ReadingTimeEntry (flush acknowledgement)', () {
    test('a batch is only cleared when the server acknowledges it', () {
      var e = const ReadingTimeEntry().add(90);
      e = e.beginBatch('op-1')!;
      expect(e.pending, 0);
      expect(e.inFlight, 90);
      expect(e.opId, 'op-1');

      // More reading while the batch is in flight stays pending.
      e = e.add(30);
      expect(e.unsent, 120);

      // A second flush must not re-queue: the in-flight batch is retried
      // with its own id instead, so nothing is sent twice.
      expect(e.beginBatch('op-2'), isNull);

      // An unrelated acknowledgement changes nothing.
      expect(e.acknowledge('other').inFlight, 90);

      e = e.acknowledge('op-1', serverTotal: 590);
      expect(e.inFlight, 0);
      expect(e.opId, isNull);
      expect(e.acknowledged, 590);
      expect(e.pending, 30);

      // Now the remaining seconds can go.
      e = e.beginBatch('op-2')!;
      expect(e.inFlight, 30);
    });

    test('a failed send keeps the seconds (network error = no ack)', () {
      var e = const ReadingTimeEntry().add(45).beginBatch('op-1')!;
      // No acknowledge/reject call happens on a network error.
      expect(e.unsent, 45);
      expect(e.total(serverSeconds: 100), 145);
      // Duplicate reply (server already had it) is an acknowledgement.
      e = e.acknowledge('op-1');
      expect(e.unsent, 0);
      expect(e.acknowledged, 45);
    });

    test('a permanent rejection drops only that batch', () {
      var e = const ReadingTimeEntry().add(50).beginBatch('op-1')!.add(10);
      e = e.reject('op-1');
      expect(e.inFlight, 0);
      expect(e.opId, isNull);
      expect(e.pending, 10);
    });

    test('batches are capped at 3600 seconds', () {
      final e = const ReadingTimeEntry().add(5000).beginBatch('op', maxSeconds: 3600)!;
      expect(e.inFlight, 3600);
      expect(e.pending, 1400);
    });

    test('totals use the larger server figure plus unsent seconds', () {
      final e = const ReadingTimeEntry(acknowledged: 200).add(20);
      expect(e.total(serverSeconds: 150), 220);
      expect(e.total(serverSeconds: 400), 420);
      expect(e.withServerTotal(100).acknowledged, 200, reason: 'never lowered');
    });

    test('time-only payload omits completed and uses the delta field', () {
      final payload = ReadingTimeService.payloadFor(12, 90);
      expect(payload, {'lesson_id': 12, 'time_spent_seconds_delta': 90});
      expect(payload.containsKey('completed'), isFalse);
      expect(ReadingTimeService.payloadFor(12, 99999)['time_spent_seconds_delta'], 3600);
    });
  });

  group('duration formatting', () {
    test('formatDuration', () {
      expect(formatDuration(0), '0 min');
      expect(formatDuration(null), '0 min');
      expect(formatDuration(42), '<1 min');
      expect(formatDuration(12 * 60 + 5), '12 min');
      expect(formatDuration(3600), '1h');
      expect(formatDuration(3 * 3600 + 20 * 60 + 59), '3h 20m');
    });

    test('spokenDuration', () {
      expect(spokenDuration(0), 'no time yet');
      expect(spokenDuration(61), '1 minute');
      expect(spokenDuration(2 * 3600 + 60), '2 hours 1 minute');
    });
  });

  group('due dates', () {
    final now = DateTime(2026, 9, 30, 10);

    test('relativeDueText', () {
      expect(relativeDueText(DateTime(2026, 10, 2, 9), now: now), 'Due in 2 days');
      expect(relativeDueText(DateTime(2026, 10, 1, 9), now: now), 'Due tomorrow');
      expect(relativeDueText(DateTime(2026, 9, 30, 18), now: now), 'Due today');
      expect(relativeDueText(DateTime(2026, 9, 30, 10, 30), now: now), 'Due in 30 min');
      expect(relativeDueText(DateTime(2026, 9, 30, 7), now: now), 'Overdue by 3 hours');
      expect(relativeDueText(DateTime(2026, 9, 27, 9), now: now), 'Overdue by 3 days');
      expect(relativeDueText(DateTime(2026, 9, 29, 9), now: now), 'Overdue by 1 day');
    });

    test('server flags win over local date maths', () {
      final a = AssignmentInfo({
        'id': 3,
        'due_at': '2026-09-01T00:00:00Z',
        'is_overdue': false,
        'can_submit': true,
      }, now: now);
      expect(a.isOverdue, isFalse);
      expect(a.canSubmit, isTrue);
      expect(a.blockedByDueDate, isFalse);
    });

    test('overdue without server flags blocks submission client-side', () {
      final a = AssignmentInfo({'id': 3, 'due_at': '2026-09-27T09:00:00'}, now: now);
      expect(a.status, AssignmentStatus.overdue);
      expect(a.canSubmit, isFalse);
      expect(a.blockedByDueDate, isTrue);
      expect(a.relativeDue, 'Overdue by 3 days');
      expect(a.canRequestExtension, isTrue);
    });

    test('an approved extension moves the effective due date', () {
      final a = AssignmentInfo({
        'id': 3,
        'due_at': '2026-09-27T09:00:00',
        'extension_request': {
          'id': 4,
          'status': 'approved',
          'approved_due_at': '2026-10-03T09:00:00',
        },
      }, now: now);
      expect(a.extensionStatus, ExtensionStatus.approved);
      expect(a.extensionLabel, 'Extension approved');
      expect(a.effectiveDue, DateTime(2026, 10, 3, 9));
      expect(a.dueWasExtended, isTrue);
      expect(a.isOverdue, isFalse);
      expect(a.canSubmit, isTrue);
      expect(a.relativeDue, 'Due in 3 days');
    });

    test('effective_due_at from the server is preferred', () {
      final a = AssignmentInfo({
        'due_at': '2026-09-27T09:00:00',
        'effective_due_at': '2026-10-05T09:00:00',
      }, now: now);
      expect(a.effectiveDue, DateTime(2026, 10, 5, 9));
    });

    test('pending and rejected extension states', () {
      final pending = AssignmentInfo({
        'due_at': '2026-09-27T09:00:00',
        'is_overdue': true,
        'can_submit': false,
        'can_request_extension': false,
        'extension_request': {'status': 'pending', 'reason': 'I was unwell'},
      }, now: now);
      expect(pending.extensionStatus, ExtensionStatus.pending);
      expect(pending.extensionLabel, 'Extension pending');
      expect(pending.canRequestExtension, isFalse);
      expect(pending.blockedByDueDate, isTrue);

      final rejected = AssignmentInfo({
        'extension_request': {'status': 'rejected', 'reviewer_note': 'Sorry'},
      }, now: now);
      expect(rejected.extensionStatus, ExtensionStatus.rejected);
      expect(rejected.extensionLabel, 'Extension declined');
    });

    test('can_request_extension from the server controls the button', () {
      final a = AssignmentInfo({
        'due_at': '2026-10-01T09:00:00',
        'can_submit': true,
        'can_request_extension': true,
      }, now: now);
      expect(a.canRequestExtension, isTrue);
      expect(a.blockedByDueDate, isFalse);
    });

    test('submitted and graded statuses and counts', () {
      final submitted = AssignmentInfo({'submissions_count': 1, 'due_at': '2026-09-01T00:00:00',
          'is_overdue': true, 'can_submit': false}, now: now);
      expect(submitted.status, AssignmentStatus.submitted);
      expect(submitted.relativeDue, startsWith('Was due'));
      final graded = AssignmentInfo({'submissions_count': 1, 'graded_at': '2026-09-02'}, now: now);
      expect(graded.status, AssignmentStatus.graded);
      final attempts = AssignmentInfo({'max_attempts': 2, 'submissions_count': 2}, now: now);
      expect(attempts.attemptsRemaining, 0);
      expect(attempts.canSubmit, isFalse);
      expect(AssignmentInfo({'attempts_remaining': 1}, now: now).attemptsRemaining, 1);

      final counts = AssignmentCounts.of([
        submitted,
        graded,
        AssignmentInfo({'due_at': '2026-09-27T09:00:00'}, now: now),
        AssignmentInfo({'due_at': '2026-10-09T09:00:00'}, now: now),
      ]);
      expect(counts.total, 4);
      expect(counts.submitted, 2);
      expect(counts.graded, 1);
      expect(counts.overdue, 1);
    });

    test('extension reason validation (10-1000 characters)', () {
      expect(validateExtensionReason(''), isNotNull);
      expect(validateExtensionReason('too short'), isNotNull);
      expect(validateExtensionReason('I was unwell all week.'), isNull);
      expect(validateExtensionReason('x' * 1001), isNotNull);
      expect(validateExtensionReason('x' * 1000), isNull);
    });

    test('422 code "overdue" is recognised', () {
      final options = RequestOptions(path: '/assignments/3/submit');
      final e = AppException.from(DioException(
        requestOptions: options,
        type: DioExceptionType.badResponse,
        response: Response(requestOptions: options, statusCode: 422, data: {
          'message': 'This assignment is past its due date. Request an extension from your instructor.',
          'code': 'overdue',
        }),
      ));
      expect(e.isOverdue, isTrue);
      expect(e.isRetryable, isFalse);
      expect(e.code, 'overdue');
    });
  });

  group('mentorship attendance', () {
    final now = DateTime(2026, 9, 30, 10);

    test('asks "Did you attend?" for recent sessions without an answer', () {
      final recent = SessionInfo({'id': 1, 'scheduled_at': '2026-09-25T10:00:00', 'status': 'scheduled'}, now: now);
      expect(recent.needsAttendanceAnswer, isTrue);
      final old = SessionInfo({'id': 2, 'scheduled_at': '2026-09-10T10:00:00'}, now: now);
      expect(old.needsAttendanceAnswer, isFalse);
      final answered = SessionInfo({'id': 3, 'scheduled_at': '2026-09-25T10:00:00', 'mentee_attended': true}, now: now);
      expect(answered.needsAttendanceAnswer, isFalse);
      expect(answered.canChangeAttendance, isTrue);
      final cancelled = SessionInfo({'id': 4, 'scheduled_at': '2026-09-25T10:00:00', 'status': 'cancelled'}, now: now);
      expect(cancelled.needsAttendanceAnswer, isFalse);
      expect(cancelled.statusLabel, 'Cancelled');
      final server = SessionInfo({'id': 5, 'scheduled_at': '2026-09-25T10:00:00', 'can_confirm_attendance': false}, now: now);
      expect(server.needsAttendanceAnswer, isFalse);
    });

    test('status and summary', () {
      final sessions = [
        SessionInfo({'scheduled_at': '2026-09-20T10:00:00', 'mentee_attended': true, 'status': 'completed'}, now: now),
        SessionInfo({'scheduled_at': '2026-09-21T10:00:00', 'mentee_attended': false}, now: now),
        SessionInfo({'scheduled_at': '2026-09-22T10:00:00', 'status': 'missed'}, now: now),
        SessionInfo({'scheduled_at': '2026-09-23T10:00:00', 'status': 'cancelled'}, now: now),
        SessionInfo({'scheduled_at': '2026-10-02T10:00:00', 'status': 'scheduled'}, now: now),
      ];
      expect(sessions[1].statusLabel, 'Missed');
      expect(sessions[4].statusLabel, 'Scheduled');
      expect(sessions[4].isUpcoming, isTrue);
      final s = AttendanceSummary.of(sessions);
      expect(s.total, 3);
      expect(s.attended, 1);
      expect(s.missed, 2);
      expect(s.upcoming, 1);
      expect(s.ratePercent, 33);
    });
  });

  group('profile completion', () {
    test('counts filled editable fields, excluding private optional ones', () {
      final fields = parseEditableFields([
        'name', 'phone', 'gender', 'date_of_birth', 'other_name',
        'is_pwd', 'disability_types', 'disability_other', 'photo',
      ]);
      expect(fields.map((f) => f.name), isNot(contains('photo')));
      expect(fields.firstWhere((f) => f.name == 'gender').type, ProfileFieldType.select);
      expect(fields.firstWhere((f) => f.name == 'date_of_birth').type, ProfileFieldType.date);
      expect(fields.firstWhere((f) => f.name == 'is_pwd').sensitive, isTrue);

      // Counted: name, phone, gender, date_of_birth.
      final profile = {'name': 'Jane Achieng', 'phone': '0700000000', 'gender': null,
          'date_of_birth': '', 'is_pwd': false, 'disability_types': []};
      expect(profileCompletionPercent(profile, fields), 50);
      expect(missingFields(profile, fields).map((f) => f.name), ['gender', 'date_of_birth']);
      expect(profileCompletionPercent({...profile, 'gender': 'female', 'date_of_birth': '1998-04-12'}, fields), 100);
      expect(profileCompletionPercent(profile, const []), 0);
    });

    test('field validation and display', () {
      final phone = ProfileField.fromJson('phone');
      expect(phone.validate('0700 000 000'), isNull);
      expect(phone.validate('abc'), isNotNull);
      final name = ProfileField.fromJson('name');
      expect(name.validate(''), isNotNull);
      final dob = ProfileField.fromJson('date_of_birth');
      expect(dob.validate('2999-01-01'), isNotNull);
      final gender = ProfileField.fromJson('gender');
      expect(displayProfileValue(gender, {'gender': 'prefer_not_to_say'}), 'Prefer not to say');
      final custom = ProfileField.fromJson({'name': 'website_url', 'type': 'url'});
      expect(custom.type, ProfileFieldType.url);
      expect(ProfileField.fromJson('home_address').type, ProfileFieldType.multiline);
    });
  });
}
