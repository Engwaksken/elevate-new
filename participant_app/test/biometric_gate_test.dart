import 'package:elevateher360_participant/widgets/biometric_gate.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('leaves the app unlocked when biometric unlock is disabled',
      (tester) async {
    SharedPreferences.setMockInitialValues({});
    await tester.pumpWidget(const MaterialApp(
      home: BiometricGate(
        child: Text('Participant home'),
        loginBuilder: _loginBuilder,
      ),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Participant home'), findsOneWidget);
  });
}

Widget _loginBuilder(BuildContext context) => const SizedBox.shrink();
