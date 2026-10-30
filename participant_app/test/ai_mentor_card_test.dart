import 'package:elevateher360_participant/screens/mentorship_ai_screen.dart';
import 'package:elevateher360_participant/widgets/ai_mentor_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('dashboard card invites a question when the AI mentor is on', (tester) async {
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(body: ListView(children: [AiMentorDashboardCard(availability: () async => true)])),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Your AI career mentor'), findsOneWidget);
    expect(find.text('Ask a career question…'), findsOneWidget);
    expect(find.text('How do I choose a career path?'), findsOneWidget);
    expect(find.text('Open my chats and plan'), findsOneWidget);
  });

  testWidgets('dashboard card collapses to one row and remembers it', (tester) async {
    Widget card() => MaterialApp(
          home: Scaffold(body: ListView(children: [AiMentorDashboardCard(availability: () async => true)])),
        );
    await tester.pumpWidget(card());
    await tester.pumpAndSettle();

    await tester.tap(find.byTooltip('Collapse AI mentor'));
    await tester.pumpAndSettle();
    expect(find.text('Your AI career mentor'), findsOneWidget);
    expect(find.text('Ask a career question…'), findsNothing);
    expect(find.text('Ask'), findsOneWidget);
    expect((await SharedPreferences.getInstance()).getBool('ai_mentor_card_expanded'), isFalse);

    // A fresh card starts collapsed, and expands again on tap.
    await tester.pumpWidget(const SizedBox());
    await tester.pumpWidget(card());
    await tester.pumpAndSettle();
    expect(find.text('Ask a career question…'), findsNothing);
    await tester.tap(find.byTooltip('Expand AI mentor'));
    await tester.pumpAndSettle();
    expect(find.text('How do I choose a career path?'), findsOneWidget);
  });

  testWidgets('dashboard card hides when the AI mentor is switched off', (tester) async {
    await tester.pumpWidget(MaterialApp(
      home: Scaffold(body: ListView(children: [AiMentorDashboardCard(availability: () async => false)])),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Your AI career mentor'), findsNothing);
  });

  testWidgets('a question from the dashboard is sent as soon as the chat opens', (tester) async {
    final sent = <String>[];
    await tester.pumpWidget(MaterialApp(
      home: MentorshipAiScreen(
        initialQuestion: 'How do I write a CV?',
        sendMessage: (message, history) async {
          sent.add(message);
          return {'message': 'Start with a short summary.', 'source': 'ai'};
        },
      ),
    ));
    await tester.pumpAndSettle();

    expect(sent, ['How do I write a CV?']);
    expect(find.text('How do I write a CV?'), findsOneWidget);
    expect(find.text('Start with a short summary.'), findsOneWidget);
  });
}
