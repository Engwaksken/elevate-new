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
}
