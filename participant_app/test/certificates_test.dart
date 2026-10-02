import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/screens/certificates_screen.dart';
import 'package:elevateher360_participant/services/api_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

class _CertificateAdapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<Uint8List>? stream,
      Future<void>? cancelled) async {
    requests.add(options);
    return ResponseBody.fromString(
        jsonEncode({
          'data': [
            {
              'id': 1,
              'type': 'course',
              'title': 'Digital Marketing',
              'number': 'COURSE-1',
              'issued_at': '2026-10-02T00:00:00Z',
              'download_path': '/certificates/course/1/download'
            },
          ],
          'last_page': 1
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
  testWidgets(
      'certificate cards expose preview, download and PDF/link sharing actions',
      (tester) async {
    final actions = <String>[];
    await tester.pumpWidget(MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
            body: CertificateCard(item: const {
          'title': 'Digital Marketing',
          'type': 'course',
          'number': 'CERT-1',
          'issued_at': '2026-10-02'
        }, onAction: actions.add))));
    for (final label in [
      'Preview',
      'Download PDF',
      'Share PDF',
      'Share link'
    ]) {
      await tester.tap(find.text(label));
    }
    expect(actions, ['preview', 'download', 'share', 'link']);
    expect(find.text('CERT-1'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets(
      'course certificate page requests only the selected course and displays issued certificates',
      (tester) async {
    FlutterSecureStorage.setMockInitialValues({});
    final adapter = _CertificateAdapter();
    final previous = ApiService.instance.dio.httpClientAdapter;
    ApiService.instance.dio.httpClientAdapter = adapter;
    addTearDown(() => ApiService.instance.dio.httpClientAdapter = previous);
    await tester.pumpWidget(MaterialApp(
        theme: AppTheme.light(), home: const CertificatesScreen(courseId: 7)));
    await tester.pumpAndSettle();
    expect(find.text('Digital Marketing'), findsOneWidget);
    expect(find.text('Preview'), findsOneWidget);
    expect(adapter.requests.single.queryParameters['course_id'], 7);
    expect(tester.takeException(), isNull);
  });
}
