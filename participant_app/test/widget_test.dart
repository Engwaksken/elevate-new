import 'package:elevateher360_participant/main.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('participant login screen loads', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ElevateHer360App(signedIn: false),
    );

    expect(find.text('Participant Login'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
