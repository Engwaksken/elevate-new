import 'package:elevateher360_participant/screens/mentorship_ai_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
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

Future<Map<String, dynamic>> _fakeSender(
  String message,
  List<Map<String, String>> history,
) async =>
    {'message': 'ok', 'source': 'ai'};
