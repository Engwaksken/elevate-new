import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/appointment_info.dart';
import 'package:elevateher360_participant/core/network/app_exception.dart';
import 'package:elevateher360_participant/services/api_service.dart';
import 'package:elevateher360_participant/screens/appointments_screen.dart';
import 'package:elevateher360_participant/services/appointments_api.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> _appointment([Map<String, dynamic> changes = const {}]) => {
      'id': 7,
      'status': 'pending',
      'status_label': 'Pending',
      'topic': 'Help with assignment 2',
      'details': 'Stuck on CSS grid.',
      'starts_at': '2026-10-08T10:00:00+03:00',
      'ends_at': '2026-10-08T10:30:00+03:00',
      'timezone': 'Africa/Kampala',
      'duration_minutes': 30,
      'mode': 'online',
      'mode_label': 'Online',
      'location': null,
      'meeting_url': null,
      'instructor': {'id': 3, 'name': 'Grace Instructor'},
      'course': {'id': 5, 'title': 'web basics'},
      'proposed_starts_at': null,
      'proposed_ends_at': null,
      'proposal_note': null,
      'decision_reason': null,
      'cancelled_by_me': false,
      'can_cancel': true,
      'can_respond_to_proposal': false,
      'can_accept_proposal': false,
      ...changes,
    };

class _Adapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  int createStatus = 201;

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<Uint8List>? requestStream,
      Future<void>? cancelFuture) async {
    requests.add(options);
    ResponseBody json(Map<String, dynamic> data, [int status = 200]) =>
        ResponseBody.fromString(jsonEncode(data), status, headers: {
          Headers.contentTypeHeader: ['application/json'],
        });

    final path = options.uri.path;
    if (options.method == 'POST' && path.endsWith('/appointments')) {
      if (createStatus == 422) {
        return json({
          'message': 'You already have an appointment request that overlaps this time.',
          'errors': {
            'starts_at': ['You already have an appointment request that overlaps this time.'],
          },
        }, 422);
      }
      return json({'message': 'sent', 'appointment': _appointment()}, 201);
    }
    if (options.method == 'POST' && path.endsWith('/accept-proposal')) {
      return json({'message': 'ok', 'appointment': _appointment({'status': 'approved'})});
    }
    if (options.method == 'POST' && path.endsWith('/decline-proposal')) {
      return json({'message': 'ok', 'appointment': _appointment({'status': 'declined'})});
    }
    if (options.method == 'POST' && path.endsWith('/cancel')) {
      return json({'message': 'ok', 'appointment': _appointment({'status': 'cancelled'})});
    }
    if (path.endsWith('/options')) {
      return json({
        'instructors': [
          {
            'id': 3,
            'name': 'Grace Instructor',
            'courses': [
              {'id': 5, 'title': 'Web Basics'},
              {'id': 6, 'title': 'Data 101'},
            ],
          },
        ],
        'durations': [15, 30, 45, 60],
        'modes': [
          {'value': 'online', 'label': 'Online'},
          {'value': 'in_person', 'label': 'In person'},
        ],
        'timezone': 'Africa/Kampala',
        'rules': {'max_open_requests': 3, 'cancel_cutoff_hours': 2, 'max_days_ahead': 180},
      });
    }
    if (RegExp(r'/appointments/\d+$').hasMatch(path)) {
      return json({'appointment': _appointment()});
    }
    return json({
      'scope': 'all',
      'upcoming': [_appointment(), _appointment({'id': 8, 'status': 'rescheduled_proposed'})],
      'past': [_appointment({'id': 9, 'status': 'completed', 'can_cancel': false})],
      'summary': {'pending': 1, 'proposals': 1, 'upcoming': 0, 'completed': 1},
    });
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  group('AppointmentInfo parsing', () {
    test('maps fields, nested instructor/course and flags', () {
      final a = AppointmentInfo(_appointment());
      expect(a.id, 7);
      expect(a.statusLabel, 'Pending');
      expect(a.topic, 'Help with assignment 2');
      expect(a.instructor?.name, 'Grace Instructor');
      expect(a.course?.id, 5);
      expect(a.course?.title, 'Web basics');
      expect(a.durationMinutes, 30);
      expect(a.isOnline, isTrue);
      expect(a.canCancel, isTrue);
      expect(a.canRespondToProposal, isFalse);
      expect(a.canJoin, isFalse);
      // +03:00 is preserved as an absolute instant.
      expect(a.startsAt, DateTime.utc(2026, 10, 8, 7));
      expect(a.endsAt, DateTime.utc(2026, 10, 8, 7, 30));
    });

    test('is defensive about missing and string-typed fields', () {
      final a = AppointmentInfo({'id': '12', 'duration_minutes': '45', 'can_cancel': '1'});
      expect(a.id, 12);
      expect(a.durationMinutes, 45);
      expect(a.canCancel, isTrue);
      expect(a.status, 'pending');
      expect(a.topic, 'Appointment');
      expect(a.startsAt, isNull);
      expect(a.instructor, isNull);
      expect(AppointmentInfo({'status': 'rescheduled_proposed'}).statusLabel, 'New time proposed');
      expect(AppointmentInfo({'status': 'in_person', 'mode': 'in_person'}).modeLabel, 'In person');
    });

    test('join only for approved appointments with a link', () {
      expect(
        AppointmentInfo(_appointment({'status': 'approved', 'meeting_url': 'https://meet.test/x'})).canJoin,
        isTrue,
      );
      expect(AppointmentInfo(_appointment({'status': 'approved'})).canJoin, isFalse);
      expect(
        AppointmentInfo(_appointment({'status': 'pending', 'meeting_url': 'https://meet.test/x'})).canJoin,
        isFalse,
      );
    });

    test('proposal fields and accept fallback', () {
      final a = AppointmentInfo({
        ..._appointment({
          'status': 'rescheduled_proposed',
          'proposed_starts_at': '2026-10-09T14:00:00+03:00',
          'proposal_note': 'Afternoon works better',
          'can_respond_to_proposal': true,
        }),
      }..remove('can_accept_proposal'));
      expect(a.isProposal, isTrue);
      expect(a.proposedStartsAt, DateTime.utc(2026, 10, 9, 11));
      expect(a.proposedEndsAt, DateTime.utc(2026, 10, 9, 11, 30));
      expect(a.canAcceptProposal(now: DateTime.utc(2026, 10, 6)), isTrue);
      expect(a.canAcceptProposal(now: DateTime.utc(2026, 10, 10)), isFalse);
    });

    test('options and list parsing', () {
      final options = AppointmentOptions.fromJson({
        'instructors': [
          {'id': 3, 'name': 'Grace', 'courses': [{'id': 5, 'title': 'Web'}, {'title': 'no id'}]},
          {'name': 'missing id'},
        ],
        'durations': ['15', 30],
        'modes': [],
      });
      expect(options.instructors, hasLength(1));
      expect(options.instructor(3)?.courses.single.id, 5);
      expect(options.durations, [15, 30]);
      expect(options.modes.map((m) => m.value), ['online', 'in_person']);
      expect(options.maxOpenRequests, 3);

      final list = AppointmentList.fromJson({
        'upcoming': [_appointment(), 'junk'],
        'past': null,
        'summary': {'proposals': '2'},
      });
      expect(list.upcoming, hasLength(1));
      expect(list.past, isEmpty);
      expect(list.proposals, 2);
    });
  });

  group('time helpers', () {
    test('start payload is UTC ISO 8601 of the local wall-clock time', () {
      final local = DateTime(2026, 10, 8, 10, 0, 37);
      final payload = appointmentStartPayload(local);
      expect(payload.endsWith('Z'), isTrue);
      // Round-trips to the same instant with seconds zeroed.
      expect(DateTime.parse(payload), DateTime(2026, 10, 8, 10).toUtc());
      expect(appointmentStartPayload(DateTime.utc(2026, 10, 8, 7)), '2026-10-08T07:00:00.000Z');
    });

    test('client-side start validation mirrors the server', () {
      final now = DateTime(2026, 10, 6, 9);
      expect(validateAppointmentStart(null, now: now), isNotNull);
      expect(validateAppointmentStart(DateTime(2026, 10, 6, 8), now: now),
          'Choose a date and time in the future.');
      expect(validateAppointmentStart(DateTime(2026, 10, 6, 9), now: now), isNotNull);
      expect(validateAppointmentStart(DateTime(2026, 10, 8, 10), now: now), isNull);
      expect(validateAppointmentStart(DateTime(2027, 6, 1), now: now),
          'Appointments can be booked up to 180 days ahead.');
    });

    test('overlap and open-request checks', () {
      final existing = [
        AppointmentInfo(_appointment()), // 07:00-07:30 UTC
        AppointmentInfo(_appointment({
          'id': 2,
          'status': 'cancelled',
          'starts_at': '2026-10-08T12:00:00+03:00',
          'ends_at': '2026-10-08T13:00:00+03:00',
        })),
      ];
      expect(validateNoOverlap(DateTime.utc(2026, 10, 8, 7, 15), 30, existing), isNotNull);
      expect(validateNoOverlap(DateTime.utc(2026, 10, 8, 7, 30), 30, existing), isNull);
      expect(validateNoOverlap(DateTime.utc(2026, 10, 8, 6, 30), 30, existing), isNull);
      // Cancelled appointments don't block.
      expect(validateNoOverlap(DateTime.utc(2026, 10, 8, 9, 15), 15, existing), isNull);

      expect(validateOpenRequests(existing), isNull);
      final three = List.generate(3, (i) => AppointmentInfo(_appointment({'id': i})));
      expect(validateOpenRequests(three), contains('3 requests'));
      final approved = List.generate(3, (i) => AppointmentInfo(_appointment({'status': 'approved'})));
      expect(validateOpenRequests(approved), isNull);
    });

    test('offset parsing and backend time hint', () {
      expect(isoOffset('2026-10-08T10:00:00+03:00'), const Duration(hours: 3));
      expect(isoOffset('2026-10-08T07:00:00Z'), Duration.zero);
      expect(isoOffset('2026-10-08T07:00:00-0530'), const Duration(hours: -5, minutes: -30));
      expect(isoOffset('2026-10-08'), isNull);

      const iso = '2026-10-08T10:00:00+03:00';
      expect(backendTimeHint(iso, timezone: 'Africa/Kampala', deviceOffset: const Duration(hours: 3)),
          isNull);
      expect(backendTimeHint(iso, timezone: 'Africa/Kampala', deviceOffset: Duration.zero),
          '10:00 in Africa/Kampala');
    });

    test('range formatting uses local time', () {
      final start = DateTime(2026, 10, 8, 10);
      expect(formatAppointmentRange(start, start.add(const Duration(minutes: 30))),
          'Thu 8 Oct 2026, 10:00 – 10:30');
      expect(formatAppointmentRange(null, null), 'Time to be confirmed');
    });
  });

  group('AppointmentsApi', () {
    late _Adapter adapter;
    late HttpClientAdapter previous;

    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
      adapter = _Adapter();
      previous = ApiService.instance.dio.httpClientAdapter;
      ApiService.instance.dio.httpClientAdapter = adapter;
    });

    tearDown(() => ApiService.instance.dio.httpClientAdapter = previous);

    test('list/options/show hit the participant appointments routes', () async {
      final list = await AppointmentsApi.instance.list();
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments');
      expect(adapter.requests.last.uri.queryParameters['scope'], 'all');
      expect(list.upcoming, hasLength(2));
      expect(list.past.single.status, 'completed');

      final options = await AppointmentsApi.instance.options();
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments/options');
      expect(options.instructors.single.courses, hasLength(2));

      final one = await AppointmentsApi.instance.show(7);
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments/7');
      expect(one.id, 7);
    });

    test('create sends UTC starts_at and the form fields', () async {
      final created = await AppointmentsApi.instance.create(
        instructorId: 3,
        courseId: 5,
        localStart: DateTime(2026, 10, 8, 10),
        durationMinutes: 30,
        mode: 'online',
        topic: '  Help  ',
        details: '   ',
      );
      final req = adapter.requests.last;
      expect(req.method, 'POST');
      expect(req.uri.path, '/api/v1/participant/appointments');
      final body = req.data as Map;
      expect(body['instructor_user_id'], 3);
      expect(body['course_id'], 5);
      expect(body['starts_at'], DateTime(2026, 10, 8, 10).toUtc().toIso8601String());
      expect(body['duration_minutes'], 30);
      expect(body['mode'], 'online');
      expect(body['topic'], 'Help');
      expect(body.containsKey('details'), isFalse);
      expect(created.id, 7);
    });

    test('server validation errors surface as AppException field errors', () async {
      adapter.createStatus = 422;
      try {
        await AppointmentsApi.instance.create(
          instructorId: 3,
          courseId: 5,
          localStart: DateTime(2026, 10, 8, 10),
          durationMinutes: 30,
          mode: 'online',
          topic: 'Help',
        );
        fail('expected AppException');
      } on AppException catch (e) {
        expect(e.kind, AppErrorKind.validation);
        expect(e.fieldError('starts_at'), contains('overlaps'));
      }
    });

    test('actions post to their endpoints', () async {
      expect((await AppointmentsApi.instance.acceptProposal(8)).status, 'approved');
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments/8/accept-proposal');

      expect((await AppointmentsApi.instance.declineProposal(8)).status, 'declined');
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments/8/decline-proposal');

      expect((await AppointmentsApi.instance.cancel(7, reason: ' Busy ')).status, 'cancelled');
      expect(adapter.requests.last.uri.path, '/api/v1/participant/appointments/7/cancel');
      expect((adapter.requests.last.data as Map)['reason'], 'Busy');
    });
  });

  testWidgets('AppointmentCard shows topic, status, mode and proposal', (tester) async {
    var tapped = false;
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(
        body: AppointmentCard(
          appointment: AppointmentInfo(_appointment({
            'status': 'rescheduled_proposed',
            'status_label': 'New time proposed',
            'proposed_starts_at': '2026-10-09T14:00:00+03:00',
          })),
          onTap: () => tapped = true,
        ),
      ),
    ));
    expect(find.text('Help with assignment 2'), findsOneWidget);
    expect(find.text('New time proposed'), findsOneWidget);
    expect(find.text('Online'), findsOneWidget);
    expect(find.text('30 min'), findsOneWidget);
    expect(find.textContaining('Proposed:'), findsOneWidget);
    expect(find.textContaining('Grace Instructor'), findsOneWidget);
    await tester.tap(find.byType(ListTile));
    expect(tapped, isTrue);
  });
}
