import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/screens/career_documents_screen.dart';
import 'package:elevateher360_participant/services/api_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

class _CareerAdapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  final resumes = <Map<String, dynamic>>[];
  final resumeUploads = <Map<String, dynamic>>[];
  bool aiUnavailable = false;
  final letters = <Map<String, dynamic>>[
    {
      'id': 7,
      'title': 'Existing letter',
      'body': 'Original body',
      'employer_name': 'Example'
    },
  ];

  @override
  Future<ResponseBody> fetch(RequestOptions options,
      Stream<Uint8List>? requestStream, Future<void>? cancelFuture) async {
    requests.add(options);
    ResponseBody json(Map<String, dynamic> data, [int status = 200]) =>
        ResponseBody.fromString(jsonEncode(data), status, headers: {
          Headers.contentTypeHeader: ['application/json']
        });
    if (options.path.endsWith('/ai')) {
      if (aiUnavailable) {
        return json(
            {'message': 'AI assistance is temporarily unavailable.'}, 503);
      }
      return json({
        'draft': {
          ...Map<String, dynamic>.from(options.data['data'] as Map),
          'body': 'Improved cover letter'
        }
      });
    }
    if (options.path.contains('/uploads/resume/')) {
      if (options.path.endsWith('/import')) {
        final document = {
          'id': 42,
          ...Map<String, dynamic>.from(options.data['data'] as Map)
        };
        resumes.add(document);
        resumeUploads[0]['document_id'] = 42;
        return json({'document': document}, 201);
      }
      return json({'upload': resumeUploads.first});
    }
    if (options.method == 'POST') {
      resumes.add({'id': 1, ...Map<String, dynamic>.from(options.data as Map)});
    }
    if (options.method == 'PUT') {
      letters[0] = {'id': 7, ...Map<String, dynamic>.from(options.data as Map)};
    }
    return ResponseBody.fromString(
        jsonEncode({
          'resumes': resumes,
          'cover_letters': letters,
          'templates': ['classic', 'modern'],
          'resume_uploads': resumeUploads,
          'cover_letter_uploads': [],
        }),
        200,
        headers: {
          Headers.contentTypeHeader: ['application/json']
        });
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  late _CareerAdapter adapter;
  late HttpClientAdapter previous;

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
    adapter = _CareerAdapter();
    previous = ApiService.instance.dio.httpClientAdapter;
    ApiService.instance.dio.httpClientAdapter = adapter;
  });
  tearDown(() => ApiService.instance.dio.httpClientAdapter = previous);

  Future<void> open(WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
        theme: AppTheme.light(), home: const CareerDocumentsScreen()));
    await tester.pumpAndSettle();
  }

  testWidgets('creates a resume with a skill and refreshes the list',
      (tester) async {
    await open(tester);
    await tester.tap(find.text('Create resume'));
    await tester.pumpAndSettle();
    await tester.enterText(
        find.byType(TextFormField).first, 'My mobile resume');
    await tester.enterText(
        find.byType(TextFormField).at(1), 'Digital professional');
    final addSkill = find.descendant(
        of: find
            .ancestor(of: find.text('Skills'), matching: find.byType(Row))
            .first,
        matching: find.text('Add'));
    await tester.ensureVisible(addSkill);
    await tester.tap(addSkill);
    await tester.pumpAndSettle();
    await tester.enterText(
        find
            .descendant(
                of: find.byType(AlertDialog),
                matching: find.byType(TextFormField))
            .first,
        'Dart');
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    await tester.drag(find.byType(ListView).last, const Offset(0, -600));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Save document'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Save document'));
    await tester.pumpAndSettle();
    expect(find.text('My mobile resume'), findsOneWidget);
    final request = adapter.requests.singleWhere((r) => r.method == 'POST');
    expect(request.path, '/career/resumes');
    expect(request.data['skills'][0]['skill'], 'Dart');
    expect(request.data['professional_summary'], 'Digital professional');
    expect(tester.takeException(), isNull);
  });

  testWidgets('edits a saved cover letter without losing its employer',
      (tester) async {
    await open(tester);
    await tester.tap(find.text('Cover letters'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Edit').last);
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextFormField).first, 'Updated letter');
    final body = find.byType(TextFormField).last;
    await tester.ensureVisible(body);
    await tester.enterText(body, 'Revised application');
    await tester.ensureVisible(find.text('Save document'));
    await tester.tap(find.text('Save document'));
    await tester.pumpAndSettle();
    final request = adapter.requests.singleWhere((r) => r.method == 'PUT');
    expect(request.path, '/career/cover-letters/7');
    expect(request.data['employer_name'], 'Example');
    expect(request.data['body'], 'Revised application');
    expect(find.text('Updated letter'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  Future<void> tapVisible(WidgetTester tester, String text) async {
    await tester.ensureVisible(find.text(text));
    await tester.pumpAndSettle();
    await tester.tap(find.text(text));
    await tester.pumpAndSettle();
  }

  testWidgets('ready uploads can be reviewed and imported before AI editing',
      (tester) async {
    adapter.resumeUploads.add({
      'id': 4,
      'original_name': 'existing.pdf',
      'status': 'ready',
      'document_id': null,
      'extracted_text': 'Existing career facts',
      'download_path': '/career/uploads/resume/4/original',
      'draft': {
        'title': 'Imported CV',
        'template': 'classic',
        'professional_summary': 'Existing career facts',
        'experiences': [],
        'education': [],
        'skills': []
      },
    });
    await open(tester);
    expect(find.text('Upload existing resume'), findsOneWidget);
    await tapVisible(tester, 'Review upload');
    expect(find.text('Existing career facts'), findsOneWidget);
    await tapVisible(tester, 'Review & import editable draft');
    await tester.drag(find.byType(ListView).last, const Offset(0, -600));
    await tester.pumpAndSettle();
    await tapVisible(tester, 'Import document');
    final imported = adapter.requests
        .singleWhere((request) => request.path.endsWith('/import'));
    expect(
        imported.data['data']['professional_summary'], 'Existing career facts');
    await tester.drag(find.byType(ListView).last, const Offset(0, 600));
    await tester.pumpAndSettle();
    expect(find.text('AI Assistant'), findsOneWidget);
    await tester.pageBack();
    await tester.pumpAndSettle();
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.text('Imported CV'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets(
      'AI drafts are reviewed and applied locally before explicit saving',
      (tester) async {
    await open(tester);
    await tester.tap(find.text('Cover letters'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Edit'));
    await tester.pumpAndSettle();
    final body = find.widgetWithText(TextFormField, 'Cover letter');
    await tester.ensureVisible(body);
    await tester.enterText(body, 'Unsaved changes to review');
    await tester.drag(find.byType(ListView).last, const Offset(0, 1000));
    await tester.pumpAndSettle();
    await tapVisible(tester, 'AI Assistant');
    await tapVisible(tester, 'Generate draft');
    expect(find.text('Improved cover letter'), findsOneWidget);
    expect(
        adapter.requests.where((request) => request.method == 'PUT'), isEmpty);
    final ai =
        adapter.requests.singleWhere((request) => request.path.endsWith('/ai'));
    expect(ai.data['data']['body'], 'Unsaved changes to review');
    await tapVisible(tester, 'Apply draft');
    await tester.pump(const Duration(seconds: 4));
    await tester.pumpAndSettle();
    await tester.drag(find.byType(ListView).last, const Offset(0, -600));
    await tester.pumpAndSettle();
    await tapVisible(tester, 'Save document');
    final save =
        adapter.requests.singleWhere((request) => request.method == 'PUT');
    expect(save.data['body'], 'Improved cover letter');
    expect(save.data['employer_name'], 'Example');
    expect(tester.takeException(), isNull);
  });

  testWidgets('AI unavailability leaves the editable cover letter intact',
      (tester) async {
    adapter.aiUnavailable = true;
    await open(tester);
    await tester.tap(find.text('Cover letters'));
    await tester.pumpAndSettle();
    await tapVisible(tester, 'AI Assistant');
    await tapVisible(tester, 'Generate draft');
    expect(find.text('Apply draft'), findsNothing);
    expect(
        adapter.requests.where((request) => request.method == 'PUT'), isEmpty);
    await tapVisible(tester, 'Close');
    final body = find.widgetWithText(TextFormField, 'Cover letter');
    await tester.ensureVisible(body);
    expect(
        tester.widget<TextFormField>(body).controller!.text, 'Original body');
  });

  testWidgets('resume editor saves project links and referee details',
      (tester) async {
    await open(tester);
    await tester.tap(find.text('Create resume'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextFormField).first, 'Complete CV');

    Future<void> addSection(String name, Map<int, String> values) async {
      await tester.scrollUntilVisible(find.text(name), 250,
          scrollable: find.byType(Scrollable).last);
      await tester.pumpAndSettle();
      final row =
          find.ancestor(of: find.text(name), matching: find.byType(Row)).first;
      final add = find.descendant(of: row, matching: find.text('Add'));
      await tester.ensureVisible(add);
      await tester.pumpAndSettle();
      await tester.tap(add);
      await tester.pumpAndSettle();
      final fields = find.descendant(
          of: find.byType(AlertDialog), matching: find.byType(TextFormField));
      for (final value in values.entries) {
        await tester.ensureVisible(fields.at(value.key));
        await tester.enterText(fields.at(value.key), value.value);
      }
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();
    }

    await addSection(
        'Projects', {0: 'Learning App', 2: 'https://example.test/project'});
    await addSection('Referees',
        {0: 'Mary Example', 3: 'mary@example.test', 4: '0700000001'});
    await tester.scrollUntilVisible(find.text('Save document'), 250,
        scrollable: find.byType(Scrollable).last);
    await tester.pumpAndSettle();
    await tapVisible(tester, 'Save document');
    final request =
        adapter.requests.singleWhere((request) => request.method == 'POST');
    expect(request.data['projects'][0]['url'], 'https://example.test/project');
    expect(request.data['referees'][0]['email'], 'mary@example.test');
    expect(request.data['referees'][0]['phone'], '0700000001');
    expect(tester.takeException(), isNull);
  });
}
