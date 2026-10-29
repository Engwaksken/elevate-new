import 'package:elevateher360_participant/screens/mentorship_ai_screen.dart';
import 'package:elevateher360_participant/services/ai_mentor_client.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  _mentorTabsTests();

  testWidgets('shows suggestion chips and a notice when empty', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: MentorshipAiScreen(sendMessage: _fakeSender)),
    );

    expect(find.text('AI career mentor'), findsOneWidget);
    expect(find.textContaining('How do I choose a career path?'), findsOneWidget);
    expect(find.textContaining('How should I prepare for an interview?'), findsOneWidget);
    expect(find.textContaining('How can I make the most of my mentor?'), findsOneWidget);
  });

  testWidgets('tapping a chip sends the question and shows the reply', (tester) async {
    final sent = <String>[];

    await tester.pumpWidget(
      MaterialApp(
        home: MentorshipAiScreen(
          sendMessage: (message, history) async {
            sent.add(message);
            await Future<void>.delayed(const Duration(milliseconds: 50));
            return {'message': 'Great question. Start by listing your strengths.', 'source': 'ai'};
          },
        ),
      ),
    );

    await tester.tap(find.text('What skills should I build next?'));
    await tester.pump();

    // The user's question is echoed while the reply is in flight.
    expect(find.text('What skills should I build next?'), findsOneWidget);
    expect(find.text('Thinking…'), findsOneWidget);

    await tester.pump(const Duration(milliseconds: 100));
    await tester.pumpAndSettle();

    expect(sent, ['What skills should I build next?']);
    expect(find.text('Great question. Start by listing your strengths.'), findsOneWidget);
  });

  testWidgets('reports an error when the assistant call fails', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: MentorshipAiScreen(
          sendMessage: (message, history) async => throw Exception('down'),
        ),
      ),
    );

    await tester.tap(find.text('What skills should I build next?'));
    await tester.pumpAndSettle();

    expect(find.byType(SnackBar), findsOneWidget);
  });
}

/// In-memory server for the history, save, steps and plan features.
class _FakeMentorClient implements AiMentorClient {
  _FakeMentorClient({this.enabled = true});

  final bool enabled;
  final calls = <String>[];
  final List<Map<String, dynamic>> _steps = [
    {'id': 7, 'title': 'Send two applications', 'due_on': '2099-11-02', 'due_label': '02 Nov 2099', 'status': 'suggested', 'goal': null},
  ];
  bool _saved = false;

  @override
  Future<Map<String, dynamic>> history() async => {
        'enabled': enabled,
        'messages': [
          {'id': 1, 'role': 'user', 'content': 'Help me find work', 'saved': false, 'actions': []},
          {'id': 2, 'role': 'assistant', 'content': 'Apply to two firms this week.', 'saved': _saved, 'actions': _steps},
        ],
      };

  @override
  Future<Map<String, dynamic>> send(String message, List<Map<String, String>> history) async =>
      {'id': 3, 'role': 'assistant', 'content': 'Reply', 'message': 'Reply', 'saved': false, 'actions': []};

  @override
  Future<Map<String, dynamic>> toggleSaved(int messageId) async {
    calls.add('save:$messageId');
    _saved = !_saved;
    return {'id': messageId, 'saved': _saved};
  }

  @override
  Future<void> clear() async => calls.add('clear');

  @override
  Future<Map<String, dynamic>> approve(int stepId, {required String title, required String dueOn}) async {
    calls.add('approve:$stepId:$title:$dueOn');
    _steps.first..['status'] = 'approved'..['title'] = title;
    return Map.of(_steps.first);
  }

  @override
  Future<Map<String, dynamic>> dismiss(int stepId) async {
    calls.add('dismiss:$stepId');
    return {..._steps.first, 'status': 'dismissed'};
  }

  @override
  Future<Map<String, dynamic>> toggleDone(int stepId) async {
    calls.add('done:$stepId');
    _steps.first['status'] = _steps.first['status'] == 'completed' ? 'approved' : 'completed';
    return Map.of(_steps.first);
  }

  @override
  Future<Map<String, dynamic>> plan({String? status, int page = 1}) async => {
        'data': _steps.where((s) => s['status'] == 'approved' || s['status'] == 'completed').map(Map.of).toList(),
        'meta': {'current_page': 1, 'last_page': 1, 'total': 1},
      };

  @override
  Future<Map<String, dynamic>> saved({int page = 1}) async => {
        'data': _saved ? [{'id': 2, 'content': 'Apply to two firms this week.', 'saved_at': '2026-10-29T10:00:00Z'}] : [],
        'meta': {'current_page': 1, 'last_page': 1, 'total': _saved ? 1 : 0},
      };
}

void _mentorTabsTests() {
  testWidgets('loads the conversation, saves an answer and approves a step', (tester) async {
    final client = _FakeMentorClient();
    await tester.pumpWidget(MaterialApp(home: MentorshipAiScreen(client: client)));
    await tester.pumpAndSettle();

    expect(find.text('Help me find work'), findsOneWidget);
    expect(find.text('Apply to two firms this week.'), findsOneWidget);
    expect(find.text('Send two applications'), findsOneWidget);

    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(client.calls, contains('save:2'));
    expect(find.text('Saved'), findsWidgets);

    await tester.tap(find.text('Add to calendar'));
    await tester.pumpAndSettle();
    expect(find.text('Add to my calendar'), findsOneWidget);
    await tester.tap(find.widgetWithText(FilledButton, 'Add to calendar').last);
    await tester.pumpAndSettle();
    expect(client.calls.any((c) => c.startsWith('approve:7:Send two applications:2099-11-02')), isTrue);
    expect(find.textContaining('On your calendar for'), findsOneWidget);
  });

  testWidgets('My plan lists approved steps and marks them done; Saved lists answers', (tester) async {
    final client = _FakeMentorClient();
    await client.approve(7, title: 'Send two applications', dueOn: '2099-11-02');
    await client.toggleSaved(2);

    await tester.pumpWidget(MaterialApp(home: MentorshipAiScreen(client: client)));
    await tester.pumpAndSettle();

    await tester.tap(find.text('My plan'));
    await tester.pumpAndSettle();
    expect(find.text('Send two applications'), findsOneWidget);
    await tester.tap(find.byType(Checkbox));
    await tester.pumpAndSettle();
    expect(client.calls, contains('done:7'));

    await tester.tap(find.text('Saved').last);
    await tester.pumpAndSettle();
    expect(find.text('Apply to two firms this week.'), findsOneWidget);
  });

  testWidgets('shows a notice when the AI mentor is switched off', (tester) async {
    await tester.pumpWidget(MaterialApp(home: MentorshipAiScreen(client: _FakeMentorClient(enabled: false))));
    await tester.pumpAndSettle();
    expect(find.text('AI help is switched off'), findsOneWidget);
  });
}

Future<Map<String, dynamic>> _fakeSender(
  String message,
  List<Map<String, String>> history,
) async =>
    {'message': 'ok', 'source': 'ai'};
