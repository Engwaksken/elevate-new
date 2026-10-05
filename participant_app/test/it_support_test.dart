import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:elevateher360_participant/core/it_support_ticket_info.dart';
import 'package:elevateher360_participant/services/api_service.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

class _ItSupportAdapter implements HttpClientAdapter {
  final requests = <RequestOptions>[];

  final ticket = {
    'id': 1,
    'subject': 'Can\'t open a lesson',
    'description': 'The video lesson does not load on my phone.',
    'category': 'learning',
    'priority': 'high',
    'status': 'open',
    'requester_id': 9,
    'assignee_id': null,
    'status_updated_at': null,
    'created_at': '2026-10-05T09:00:00.000000Z',
    'updated_at': '2026-10-05T09:00:00.000000Z',
  };

  @override
  Future<ResponseBody> fetch(RequestOptions options,
      Stream<Uint8List>? requestStream, Future<void>? cancelFuture) async {
    requests.add(options);
    ResponseBody json(Map<String, dynamic> data, [int status = 200]) =>
        ResponseBody.fromString(jsonEncode(data), status, headers: {
          Headers.contentTypeHeader: ['application/json'],
        });

    if (options.method == 'POST') {
      return json({'data': {...ticket, ...Map<String, dynamic>.from(options.data as Map)}}, 201);
    }

    // GET /tickets/{id}
    if (RegExp(r'/tickets/\d+$').hasMatch(options.uri.path)) {
      return json({'data': ticket});
    }

    return json({
      'data': [ticket],
      'links': {'first': null, 'last': null, 'prev': null, 'next': null},
      'meta': {'current_page': 1, 'last_page': 1, 'per_page': 15, 'total': 1},
    });
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  late _ItSupportAdapter adapter;
  late HttpClientAdapter previous;

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
    adapter = _ItSupportAdapter();
    previous = ApiService.instance.dio.httpClientAdapter;
    ApiService.instance.dio.httpClientAdapter = adapter;
  });

  tearDown(() => ApiService.instance.dio.httpClientAdapter = previous);

  test('list/show/create hit the sibling /api/v1/it-support prefix', () async {
    final list = await ApiService.instance.itSupportTickets();
    expect(adapter.requests.last.uri.path, '/api/v1/it-support/tickets');
    expect(list['data'], isA<List>());

    final detail = await ApiService.instance.itSupportTicket(1);
    expect(adapter.requests.last.uri.path, '/api/v1/it-support/tickets/1');
    expect(detail['data'], isA<Map>());

    await ApiService.instance.createItSupportTicket(
      subject: 'Subject',
      description: 'Description',
      category: 'general',
      priority: 'normal',
    );
    final created = adapter.requests.last;
    expect(created.uri.path, '/api/v1/it-support/tickets');
    expect(created.method, 'POST');
    expect(created.data['subject'], 'Subject');
    expect(created.data['description'], 'Description');
    expect(created.data['category'], 'general');
    expect(created.data['priority'], 'normal');
  });

  test('ItSupportTicketInfo maps status, priority and category labels', () {
    final info = ItSupportTicketInfo({
      'id': 1,
      'subject': 'app login',
      'status': 'awaiting_requester',
      'priority': 'urgent',
      'category': 'access',
      'description': 'Can\'t sign in.',
    });

    expect(info.id, 1);
    expect(info.subject, 'App login');
    expect(info.statusLabel, 'Awaiting your reply');
    expect(info.priorityLabel, 'Urgent');
    expect(info.categoryLabel, 'Access');
    expect(info.isResolved, isFalse);

    expect(ItSupportTicketInfo({'status': 'resolved'}).isResolved, isTrue);
    expect(ItSupportTicketInfo({}).status, 'open');
    expect(ItSupportTicketInfo({}).priority, 'normal');
    expect(ItSupportTicketInfo({}).category, 'general');
  });
}
