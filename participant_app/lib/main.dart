import 'package:flutter/material.dart';

import 'core/app_config.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await NotificationService.instance.initialise();

  final signedIn = await ApiService.instance.hasToken();

  if (signedIn) {
    await NotificationService.instance.registerCurrentDevice();
  }

  runApp(ElevateHer360App(signedIn: signedIn));
}

class ElevateHer360App extends StatelessWidget {
  const ElevateHer360App({
    super.key,
    required this.signedIn,
  });

  final bool signedIn;

  @override
  Widget build(BuildContext context) {
    const maroon = Color(0xFF800000);
    const gold = Color(0xFFD4AF37);

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: AppConfig.appName,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: maroon,
          primary: maroon,
          secondary: gold,
        ),
        inputDecorationTheme: const InputDecorationTheme(
          border: OutlineInputBorder(),
        ),
      ),
      home: signedIn ? const HomeScreen() : const LoginScreen(),
    );
  }
}
