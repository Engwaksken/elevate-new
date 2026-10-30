import 'package:elevateher360_participant/screens/library_reader_screen.dart';
import 'package:elevateher360_participant/screens/library_screen.dart';
import 'package:elevateher360_participant/widgets/app_drawer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Widget wrap(Widget child) => MaterialApp(home: Scaffold(body: ListView(children: [child])));

  testWidgets('a book in progress shows its page and progress bar', (tester) async {
    await tester.pumpWidget(wrap(BookTile(
      book: const {
        'id': 1,
        'title': 'Interview Skills',
        'author': 'Jane Doe',
        'category': {'id': 2, 'name': 'Careers'},
        'bookmarked': true,
        'progress': {'page': 12, 'total_pages': 40, 'percent': 30, 'completed': false},
      },
      onTap: () {},
    )));

    expect(find.text('Interview Skills'), findsOneWidget);
    expect(find.text('Jane Doe · Careers'), findsOneWidget);
    expect(find.text('Page 12 of 40'), findsOneWidget);
    expect(find.byType(LinearProgressIndicator), findsOneWidget);
    expect(find.byIcon(Icons.bookmark), findsOneWidget);
  });

  testWidgets('a finished book says so instead of showing a page', (tester) async {
    await tester.pumpWidget(wrap(BookTile(
      book: const {
        'id': 1,
        'title': 'Done Book',
        'progress': {'page': 40, 'total_pages': 40, 'percent': 100, 'completed': true},
      },
      onTap: () {},
    )));

    expect(find.text('Finished'), findsOneWidget);
    expect(find.textContaining('Page'), findsNothing);
  });

  testWidgets('the reader offers a retry when the book cannot be loaded', (tester) async {
    var attempts = 0;
    await tester.pumpWidget(MaterialApp(
      home: LibraryReaderScreen(
        bookId: 1,
        title: 'Career Guide',
        loadPdf: () async {
          attempts++;
          throw Exception('network down');
        },
        saveProgress: (_, __) async {},
      ),
    ));
    await tester.pumpAndSettle();

    expect(attempts, 1);
    await tester.tap(find.text('Retry'));
    await tester.pumpAndSettle();
    expect(attempts, 2);
  });

  test('Library is in the side menu', () {
    expect(AppDestination.values.map((d) => d.label), contains('Library'));
  });
}
