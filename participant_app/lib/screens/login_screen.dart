import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../services/sync_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  String? _error;

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ApiService.instance.login(
        email: _email.text,
        password: _password.text,
      );

      await NotificationService.instance.registerCurrentDevice();

      try {
        await SyncService.instance.syncNow();
      } catch (_) {}

      if (!mounted) return;

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } catch (_) {
      if (mounted) {
        setState(
          () =>
              _error = 'Unable to sign in. Check your details and connection.',
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const maroon = Color(0xFF800000);
    const gold = Color(0xFFD4AF37);

    return Scaffold(
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 720;

            final brandPanel = Container(
              constraints: const BoxConstraints(minHeight: 220),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [maroon, Color(0xFF4F0000)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: const Center(
                child: Padding(
                  padding: EdgeInsets.all(28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.school_outlined, color: gold, size: 72),
                      SizedBox(height: 18),
                      Text(
                        'ElevateHer360',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 30,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      SizedBox(height: 10),
                      Text(
                        'Learn, connect and keep moving forward — even with limited data.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white70, height: 1.5),
                      ),
                    ],
                  ),
                ),
              ),
            );

            final loginPanel = SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text(
                      'Participant Login',
                      style: TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        color: maroon,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Use the same account you use on ElevateHer360.',
                    ),
                    const SizedBox(height: 28),
                    TextFormField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email address',
                        prefixIcon: Icon(Icons.email_outlined),
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) =>
                          value == null || !value.contains('@')
                              ? 'Enter a valid email address'
                              : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _password,
                      obscureText: _obscure,
                      decoration: InputDecoration(
                        labelText: 'Password',
                        prefixIcon: const Icon(Icons.lock_outline),
                        border: const OutlineInputBorder(),
                        suffixIcon: IconButton(
                          onPressed: () => setState(() => _obscure = !_obscure),
                          icon: Icon(
                            _obscure ? Icons.visibility : Icons.visibility_off,
                          ),
                        ),
                      ),
                      validator: (value) => value == null || value.isEmpty
                          ? 'Enter your password'
                          : null,
                    ),
                    if (_error != null) ...[
                      const SizedBox(height: 12),
                      Text(
                        _error!,
                        style: const TextStyle(color: Colors.red),
                      ),
                    ],
                    const SizedBox(height: 20),
                    FilledButton.icon(
                      style: FilledButton.styleFrom(
                        backgroundColor: maroon,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _loading ? null : _login,
                      icon: _loading
                          ? const SizedBox.square(
                              dimension: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.login),
                      label: const Text('Sign In'),
                    ),
                  ],
                ),
              ),
            );

            if (compact) {
              return ListView(
                children: [
                  brandPanel,
                  loginPanel,
                ],
              );
            }

            return Row(
              children: [
                Expanded(child: brandPanel),
                Expanded(child: loginPanel),
              ],
            );
          },
        ),
      ),
    );
  }
}
