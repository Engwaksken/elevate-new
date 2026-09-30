import 'dart:async';

import 'package:flutter/material.dart';

import 'core/app_config.dart';
import 'core/session_events.dart';
import 'core/theme/app_theme.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'services/api_service.dart';
import 'services/auth_flow.dart';
import 'services/notification_service.dart';

final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await NotificationService.instance.initialise();

  final signedIn = await ApiService.instance.hasToken();

  if (signedIn) {
    unawaited(NotificationService.instance.registerCurrentDevice());
  }

  runApp(ElevateHer360App(signedIn: signedIn));
}

class ElevateHer360App extends StatefulWidget {
  const ElevateHer360App({super.key, required this.signedIn});

  final bool signedIn;

  @override
  State<ElevateHer360App> createState() => _ElevateHer360AppState();
}

class _ElevateHer360AppState extends State<ElevateHer360App> {
  StreamSubscription<String>? _sessionSub;

  @override
  void initState() {
    super.initState();
    _sessionSub = SessionEvents.instance.sessionExpired.listen(_onExpired);
  }

  @override
  void dispose() {
    _sessionSub?.cancel();
    super.dispose();
  }

  /// A 401 from the API: the token was revoked or expired. Clear local
  /// data and return to sign-in with an explanation.
  Future<void> _onExpired(String message) async {
    await AuthFlow.signOut(remote: false);

    appNavigatorKey.currentState?.pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => LoginScreen(notice: message)),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: appNavigatorKey,
      debugShowCheckedModeBanner: false,
      title: AppConfig.appName,
      theme: AppTheme.light(),
      darkTheme: AppTheme.dark(),
      themeMode: ThemeMode.system,
      home: widget.signedIn ? const HomeScreen() : const LoginScreen(),
    );
  }
}
