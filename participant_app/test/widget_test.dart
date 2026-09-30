import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/formatters.dart';
import 'package:elevateher360_participant/core/lesson_info.dart';
import 'package:elevateher360_participant/core/network/app_exception.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/main.dart';
import 'package:elevateher360_participant/widgets/state_views.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

DioException _badResponse(int status, [dynamic data]) {
  final options = RequestOptions(path: '/lessons/1');
  return DioException(
    requestOptions: options,
    type: DioExceptionType.badResponse,
    response: Response(requestOptions: options, statusCode: status, data: data),
  );
}

void main() {
  testWidgets('login screen renders with accessible controls', (tester) async {
    await tester.pumpWidget(const ElevateHer360App(signedIn: false));

    expect(find.text('Participant sign in'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
    expect(find.byTooltip('Show password'), findsOneWidget);
    expect(find.text('About & privacy policy'), findsOneWidget);
  });

  testWidgets('login validates input before calling the API', (tester) async {
    await tester.pumpWidget(const ElevateHer360App(signedIn: false));

    await tester.tap(find.text('Sign in'));
    await tester.pump();

    expect(find.text('Enter your email address'), findsOneWidget);
    expect(find.text('Enter your password'), findsOneWidget);
  });

  testWidgets('login layout survives 2x text scaling on a small phone',
      (tester) async {
    tester.view.physicalSize = const Size(360, 740);
    tester.view.devicePixelRatio = 1;
    tester.platformDispatcher.textScaleFactorTestValue = 2;
    addTearDown(tester.view.reset);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);

    await tester.pumpWidget(const ElevateHer360App(signedIn: false));
    await tester.pump();
    expect(tester.takeException(), isNull);
  });

  testWidgets('login layout survives 2x text scaling in landscape',
      (tester) async {
    tester.view.physicalSize = const Size(800, 400);
    tester.view.devicePixelRatio = 1;
    tester.platformDispatcher.textScaleFactorTestValue = 2;
    addTearDown(tester.view.reset);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);

    await tester.pumpWidget(const ElevateHer360App(signedIn: false));
    await tester.pump();
    expect(tester.takeException(), isNull);
  });

  testWidgets('ErrorState shows a friendly 404 message and Retry', (tester) async {
    var retried = false;
    await tester.pumpWidget(MaterialApp(
      theme: AppTheme.light(),
      home: Scaffold(
        body: ErrorState(error: _badResponse(404), onRetry: () => retried = true),
      ),
    ));

    expect(find.text("This content isn't available yet."), findsOneWidget);
    expect(find.textContaining('DioException'), findsNothing);
    await tester.tap(find.text('Retry'));
    expect(retried, isTrue);
  });

  group('AppException', () {
    test('404 on an unknown route becomes a friendly message', () {
      final e = AppException.from(_badResponse(404, {
        'message': 'The requested API endpoint does not exist.',
      }));
      expect(e.kind, AppErrorKind.notFound);
      expect(e.message, "This content isn't available yet.");
      expect(e.message.contains('DioException'), isFalse);
    });

    test('specific 404 server message is kept', () {
      final e = AppException.from(_badResponse(404, {
        'message': 'This lesson has no downloadable file.',
      }));
      expect(e.message, 'This lesson has no downloadable file.');
    });

    test('422 exposes field errors', () {
      final e = AppException.from(_badResponse(422, {
        'message': 'Invalid email or password.',
        'errors': {
          'email': ['Invalid email or password.'],
        },
      }));
      expect(e.kind, AppErrorKind.validation);
      expect(e.fieldError('email'), 'Invalid email or password.');
      expect(e.isRetryable, isFalse);
    });

    test('5xx hides server detail and is retryable', () {
      final e = AppException.from(_badResponse(500, {'message': 'SQLSTATE[42S02] boom'}));
      expect(e.kind, AppErrorKind.server);
      expect(e.message.contains('SQLSTATE'), isFalse);
      expect(e.isRetryable, isTrue);
    });

    test('timeouts and connection errors map to friendly kinds', () {
      final options = RequestOptions(path: '/sync');
      expect(
        AppException.from(DioException(
          requestOptions: options,
          type: DioExceptionType.receiveTimeout,
        )).kind,
        AppErrorKind.timeout,
      );
      expect(
        AppException.from(DioException(
          requestOptions: options,
          type: DioExceptionType.connectionError,
        )).kind,
        AppErrorKind.offline,
      );
    });

    test('non-Dio errors never leak their text', () {
      expect(friendlyError(StateError('secret')), AppException.genericMessage);
    });
  });

  group('formatting', () {
    test('tidyTitle normalises inconsistent lesson names', () {
      expect(tidyTitle('lesson 1'), 'Lesson 1');
      expect(tidyTitle('Lesson2'), 'Lesson 2');
      expect(tidyTitle('  Intro   to  SEO '), 'Intro to SEO');
      expect(tidyTitle(null, fallback: 'Lesson'), 'Lesson');
    });

    test('lesson types get human-readable labels', () {
      expect(LessonKind.fromLesson({'content_type': 'file'}).label, 'Document');
      expect(LessonKind.fromLesson({'content_type': 'text'}).label, 'Reading');
      expect(LessonKind.fromLesson({'type': 'video'}).label, 'Video');
      expect(LessonKind.fromLesson({'content_type': 'link'}).label, 'Web link');
    });

    test('plainParagraphs strips HTML safely', () {
      expect(
        plainParagraphs('<p>Hello &amp; welcome</p><p>Line<br>two</p>'),
        ['Hello & welcome', 'Line\ntwo'],
      );
    });
  });

  group('LessonInfo', () {
    test('hides download when the server reports no file', () {
      final lesson = LessonInfo({
        'id': 1,
        'content_type': 'file',
        'has_file': false,
        'resource_url': null,
      });
      expect(lesson.hasFile, isFalse);
    });

    test('ignores legacy public /storage URLs', () {
      final lesson = LessonInfo({
        'id': 1,
        'resource_url': 'https://site.elevateher360.org/storage/courses/1/x.pdf',
      });
      expect(lesson.downloadPath, isNull);
      expect(lesson.hasFile, isFalse);
    });

    test('uses the authenticated download_path', () {
      final lesson = LessonInfo({
        'id': 1,
        'has_file': true,
        'download_path': '/lessons/1/download',
        'duration_minutes': 30,
      });
      expect(lesson.hasFile, isTrue);
      expect(lesson.downloadPath, '/lessons/1/download');
      expect(lesson.subtitle, 'Document · 30 min');
    });

    test('locked module locks its lessons', () {
      expect(LessonInfo({'id': 2}, moduleLocked: true).isLocked, isTrue);
      expect(LessonInfo({'id': 2, 'is_locked': true}).isLocked, isTrue);
    });
  });
}
