import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/network/app_exception.dart';
import '../core/session_events.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/auth_flow.dart';
import '../widgets/decorations.dart';
import 'about_screen.dart';
import 'home_screen.dart';
import 'verify_email_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, this.notice});

  /// Optional message shown above the form (e.g. "session expired").
  final String? notice;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _passwordFocus = FocusNode();

  bool _loading = false;
  bool _obscure = true;
  String? _error;

  static final _emailPattern = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');

  @override
  void initState() {
    super.initState();
    SessionEvents.instance.reset();
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _passwordFocus.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final data = await ApiService.instance.login(
        email: _email.text,
        password: _password.text,
      );

      final user = data['user'];
      final verified = user is Map && user['email_verified'] != false;

      if (verified) {
        await AuthFlow.afterSignIn();
      }

      if (!mounted) return;

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) =>
              verified ? const HomeScreen() : const VerifyEmailScreen(),
        ),
      );
    } catch (error) {
      final mapped = AppException.from(error);
      if (mounted) {
        setState(() {
          _error = mapped.kind == AppErrorKind.notFound
              ? "We couldn't reach the sign-in service. Please try again later."
              : mapped.message;
        });
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _forgotPassword() async {
    await launchUrl(
      Uri.parse('${AppConfig.siteUrl}/forgot-password'),
      mode: LaunchMode.externalApplication,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    final brand = BrandColors.of(context);

    Widget headerContent({required bool wide}) => Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ExcludeSemantics(
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.18),
                      blurRadius: 18,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: ClipOval(
                  child: Image.asset('assets/logo.png', width: 84, height: 84),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Semantics(
              header: true,
              child: Text(
                AppConfig.appName,
                textAlign: TextAlign.center,
                style: theme.textTheme.headlineMedium
                    ?.copyWith(color: brand.onHeader),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              'Learn, grow and shine. Your courses, mentors and opportunities, '
              'even with limited data.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                // Off-white on the brown gradient stays above 6.8:1.
                color: brand.onHeader.withValues(alpha: 0.92),
              ),
            ),
          ],
        );

    Widget gradientPanel({required Widget child, BorderRadius? radius}) =>
        ClipRRect(
          borderRadius: radius ?? BorderRadius.zero,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: brand.headerGradient,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Stack(
              children: [
                Positioned.fill(
                  child: ExcludeSemantics(
                    child: CustomPaint(
                      painter: SoftBlobsPainter(
                        colors: [brand.blobA, brand.blobB, brand.blobA],
                        opacity: 0.22,
                      ),
                    ),
                  ),
                ),
                child,
              ],
            ),
          ),
        );

    final header = gradientPanel(
      radius: const BorderRadius.vertical(
          bottom: Radius.circular(AppRadius.xl + 8)),
      child: SizedBox(
        width: double.infinity,
        child: SafeArea(
          bottom: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.xl,
              AppSpacing.xl,
              AppSpacing.xl,
              AppSpacing.xxl,
            ),
            child: headerContent(wide: false),
          ),
        ),
      ),
    );

    final formFields = AutofillGroup(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Semantics(
              header: true,
              child: Text('Participant sign in',
                  style: theme.textTheme.headlineSmall),
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Welcome back! We are so glad to see you.',
              style:
                  theme.textTheme.bodyLarge?.copyWith(color: scheme.secondary),
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Use the same account you use on the ElevateHer360 website.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: scheme.onSurfaceVariant,
              ),
            ),
            if (widget.notice != null) ...[
              const SizedBox(height: AppSpacing.lg),
              _Banner(
                icon: Icons.info_outline,
                message: widget.notice!,
                background: scheme.secondaryContainer,
                foreground: scheme.onSecondaryContainer,
              ),
            ],
            const SizedBox(height: AppSpacing.xl),
            TextFormField(
              controller: _email,
              enabled: !_loading,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              autocorrect: false,
              enableSuggestions: false,
              autofillHints: const [
                AutofillHints.email,
                AutofillHints.username
              ],
              decoration: const InputDecoration(
                labelText: 'Email address',
                prefixIcon: Icon(Icons.email_outlined),
              ),
              onFieldSubmitted: (_) => _passwordFocus.requestFocus(),
              validator: (value) {
                final text = value?.trim() ?? '';
                if (text.isEmpty) return 'Enter your email address';
                if (!_emailPattern.hasMatch(text)) {
                  return 'Enter a valid email address';
                }
                return null;
              },
            ),
            const SizedBox(height: AppSpacing.lg),
            TextFormField(
              controller: _password,
              focusNode: _passwordFocus,
              enabled: !_loading,
              obscureText: _obscure,
              keyboardType: TextInputType.visiblePassword,
              textInputAction: TextInputAction.done,
              autocorrect: false,
              enableSuggestions: false,
              autofillHints: const [AutofillHints.password],
              decoration: InputDecoration(
                labelText: 'Password',
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  tooltip: _obscure ? 'Show password' : 'Hide password',
                  onPressed: () => setState(() => _obscure = !_obscure),
                  icon: Icon(
                    _obscure
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                  ),
                ),
              ),
              onFieldSubmitted: (_) => _loading ? null : _login(),
              validator: (value) =>
                  (value ?? '').isEmpty ? 'Enter your password' : null,
            ),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: _loading ? null : _forgotPassword,
                child: const Text('Forgot password?'),
              ),
            ),
            if (_error != null) ...[
              _Banner(
                icon: Icons.error_outline,
                message: _error!,
                background: scheme.errorContainer,
                foreground: scheme.onErrorContainer,
              ),
              const SizedBox(height: AppSpacing.lg),
            ],
            FilledButton.icon(
              onPressed: _loading ? null : _login,
              icon: _loading
                  ? SizedBox.square(
                      dimension: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: scheme.onSurface,
                        semanticsLabel: 'Signing in',
                      ),
                    )
                  : const Icon(Icons.login),
              label: Text(_loading ? 'Signing in…' : 'Sign in'),
            ),
            const SizedBox(height: AppSpacing.xl),
            Center(
              child: TextButton(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const AboutScreen()),
                ),
                child: const Text('About & privacy policy'),
              ),
            ),
          ],
        ),
      ),
    );

    final form = Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: formFields,
      ),
    );

    return Scaffold(
      body: GestureDetector(
        onTap: () => FocusScope.of(context).unfocus(),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final wide = constraints.maxWidth >= 720;

            final formPanel = SafeArea(
              top: wide,
              child: Center(
                child: SingleChildScrollView(
                  keyboardDismissBehavior:
                      ScrollViewKeyboardDismissBehavior.onDrag,
                  padding: const EdgeInsets.all(AppSpacing.xl),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 440),
                    child: form,
                  ),
                ),
              ),
            );

            if (wide) {
              return Row(
                children: [
                  Expanded(
                    child: gradientPanel(
                      child: SizedBox.expand(
                        child: Center(
                          child: SingleChildScrollView(
                            padding: const EdgeInsets.all(AppSpacing.xl),
                            child: SafeArea(child: headerContent(wide: true)),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Expanded(child: SoftBackground(seed: 1, child: formPanel)),
                ],
              );
            }

            return SoftBackground(
              seed: 1,
              child: SingleChildScrollView(
                keyboardDismissBehavior:
                    ScrollViewKeyboardDismissBehavior.onDrag,
                child: Column(
                  children: [
                    header,
                    SafeArea(
                      top: false,
                      child: Transform.translate(
                        // The form card overlaps the curved header a little.
                        offset: const Offset(0, -AppSpacing.xl),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: AppSpacing.lg),
                          child: Center(
                            child: ConstrainedBox(
                              constraints: const BoxConstraints(maxWidth: 480),
                              child: form,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({
    required this.icon,
    required this.message,
    required this.background,
    required this.foreground,
  });

  final IconData icon;
  final String message;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      liveRegion: true,
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: foreground, size: 20),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: Text(message, style: TextStyle(color: foreground)),
            ),
          ],
        ),
      ),
    );
  }
}
