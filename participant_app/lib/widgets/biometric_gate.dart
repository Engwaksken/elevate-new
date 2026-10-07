import 'package:flutter/material.dart';

import '../services/auth_flow.dart';
import '../services/biometric_auth_service.dart';

/// Prompts for biometrics on app entry and whenever a protected session
/// returns from the background.
class BiometricGate extends StatefulWidget {
  const BiometricGate({
    super.key,
    required this.child,
    required this.loginBuilder,
  });

  final Widget child;
  final WidgetBuilder loginBuilder;

  @override
  State<BiometricGate> createState() => _BiometricGateState();
}

class _BiometricGateState extends State<BiometricGate>
    with WidgetsBindingObserver {
  bool _locked = true;
  bool _busy = false;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _unlock();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused && !_busy) {
      setState(() => _locked = true);
    } else if (state == AppLifecycleState.resumed && _locked && !_busy) {
      _unlock();
    }
  }

  Future<void> _unlock() async {
    if (_busy) return;
    setState(() {
      _busy = true;
      _failed = false;
    });
    final unlocked =
        await BiometricAuthService.instance.authenticateIfEnabled();
    if (!mounted) return;
    setState(() {
      _locked = !unlocked;
      _failed = !unlocked;
      _busy = false;
    });
  }

  Future<void> _usePassword() async {
    await BiometricAuthService.instance.disable();
    await AuthFlow.signOut();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute<void>(builder: widget.loginBuilder),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!_locked) return widget.child;

    final theme = Theme.of(context);
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 380),
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.fingerprint,
                      size: 72, color: theme.colorScheme.primary),
                  const SizedBox(height: 20),
                  Text('Unlock ElevateHer360',
                      style: theme.textTheme.headlineSmall,
                      textAlign: TextAlign.center),
                  const SizedBox(height: 8),
                  Text(
                    _failed
                        ? 'Biometric verification was not completed. Try again or sign in with your password.'
                        : 'Verify your identity to continue.',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  FilledButton.icon(
                    onPressed: _busy ? null : _unlock,
                    icon: _busy
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.fingerprint),
                    label: Text(_busy ? 'Waiting for verification…' : 'Unlock'),
                  ),
                  TextButton(
                    onPressed: _busy ? null : _usePassword,
                    child: const Text('Sign out and use password'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
