import 'package:flutter/material.dart';

import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/auth_flow.dart';
import '../widgets/feedback.dart';
import 'home_screen.dart';
import 'login_screen.dart';

/// Blocks the app until the participant verifies her email, mirroring the
/// website's verification gate. She can resend the link or sign out.
class VerifyEmailScreen extends StatefulWidget {
  const VerifyEmailScreen({super.key});

  @override
  State<VerifyEmailScreen> createState() => _VerifyEmailScreenState();
}

class _VerifyEmailScreenState extends State<VerifyEmailScreen> {
  bool _busy = false;
  String? _email;

  @override
  void initState() {
    super.initState();
    _loadEmail();
  }

  Future<void> _loadEmail() async {
    final user = await ApiService.instance.currentUser();
    if (mounted) setState(() => _email = user?['email']?.toString());
  }

  Future<void> _resend() async {
    setState(() => _busy = true);
    try {
      final result = await ApiService.instance.resendVerificationEmail();
      if (!mounted) return;
      showAppSnackBar(
        context,
        result['message']?.toString() ?? 'Verification link sent. Please check your inbox.',
      );
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _checkAgain() async {
    setState(() => _busy = true);
    try {
      final data = await ApiService.instance.me();
      final user = data['user'];
      final verified = user is Map && user['email_verified'] == true;
      if (!mounted) return;
      if (verified) {
        await AuthFlow.afterSignIn();
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const HomeScreen()),
        );
      } else {
        showAppSnackBar(
          context,
          "Your email isn't verified yet. Open the link we emailed you, then try again.",
        );
      }
    } catch (error) {
      if (mounted) {
        final mapped = AppException.from(error);
        showAppSnackBar(context, mapped.message, error: true);
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _signOut() async {
    await AuthFlow.signOut();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.xl),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          color: scheme.primaryContainer,
                          borderRadius: BorderRadius.circular(AppRadius.lg),
                        ),
                        child: Icon(Icons.mark_email_unread_outlined,
                            size: 34, color: scheme.onPrimaryContainer),
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      Semantics(
                        header: true,
                        child: Text('Verify your email', style: theme.textTheme.headlineSmall),
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      Text(
                        'Please check your inbox and click the verification link before you sign in.',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(color: scheme.onSurfaceVariant),
                      ),
                      if ((_email ?? '').isNotEmpty) ...[
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          _email!,
                          textAlign: TextAlign.center,
                          style: theme.textTheme.titleSmall,
                        ),
                      ],
                      const SizedBox(height: AppSpacing.xl),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _busy ? null : _resend,
                          icon: const Icon(Icons.send_outlined),
                          label: const Text('Resend verification email'),
                        ),
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.tonalIcon(
                          onPressed: _busy ? null : _checkAgain,
                          icon: const Icon(Icons.refresh),
                          label: const Text("I've verified — continue"),
                        ),
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      SizedBox(
                        width: double.infinity,
                        child: OutlinedButton.icon(
                          onPressed: _busy ? null : _signOut,
                          icon: const Icon(Icons.logout),
                          label: const Text('Sign out'),
                        ),
                      ),
                      const SizedBox(height: AppSpacing.md),
                      Text(
                        'Verification keeps your account and your data safe.',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodySmall
                            ?.copyWith(color: scheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
