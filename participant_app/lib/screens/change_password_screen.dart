import 'package:flutter/material.dart';

import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/state_views.dart';

/// PUT /profile/password. Pops with `true` on success (204). This device
/// stays signed in; the server signs out her other devices.
class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();

  bool _obscure = true;
  bool _saving = false;
  Map<String, String> _errors = {};
  String? _general;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _errors = {};
      _general = null;
    });
    if (!_formKey.currentState!.validate()) return;

    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        setState(() => _general =
            "You're offline. Connect to the internet to change your password.");
      }
      return;
    }

    setState(() => _saving = true);
    try {
      await ParticipantDataService.instance.changePassword(
        currentPassword: _current.text,
        password: _password.text,
        confirmation: _confirm.text,
      );
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      final mapped = AppException.from(error);
      if (!mounted) return;
      setState(() {
        _errors = Map.of(mapped.fieldErrors);
        _general = _errors.isEmpty ? mapped.message : null;
      });
      WidgetsBinding.instance.addPostFrameCallback((_) => _formKey.currentState?.validate());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Widget _field({
    required TextEditingController controller,
    required String label,
    required String errorKey,
    required String? Function(String) validate,
    List<String> autofill = const [],
    TextInputAction action = TextInputAction.next,
  }) {
    return TextFormField(
      controller: controller,
      enabled: !_saving,
      obscureText: _obscure,
      autocorrect: false,
      enableSuggestions: false,
      keyboardType: TextInputType.visiblePassword,
      textInputAction: action,
      autofillHints: autofill,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.lock_outline),
        suffixIcon: IconButton(
          tooltip: _obscure ? 'Show passwords' : 'Hide passwords',
          onPressed: () => setState(() => _obscure = !_obscure),
          icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
        ),
      ),
      onFieldSubmitted: action == TextInputAction.done ? (_) => _submit() : null,
      validator: (value) => _errors[errorKey] ?? validate(value ?? ''),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Change password')),
      body: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: AutofillGroup(
            child: ListView(
              padding: AppSpacing.listPadding,
              children: [
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Choose a strong password of at least 8 characters that you '
                  "don't use anywhere else.",
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                if (_general != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Semantics(
                    liveRegion: true,
                    child: NoticeCard(
                      icon: Icons.error_outline,
                      tone: PillTone.danger,
                      message: _general!,
                    ),
                  ),
                ],
                const SizedBox(height: AppSpacing.lg),
                _field(
                  controller: _current,
                  label: 'Current password',
                  errorKey: 'current_password',
                  autofill: const [AutofillHints.password],
                  validate: (v) => v.isEmpty ? 'Enter your current password' : null,
                ),
                const SizedBox(height: AppSpacing.lg),
                _field(
                  controller: _password,
                  label: 'New password',
                  errorKey: 'password',
                  autofill: const [AutofillHints.newPassword],
                  validate: (v) {
                    if (v.length < 8) return 'Use at least 8 characters';
                    if (v == _current.text) return 'Choose a password different from your current one';
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                _field(
                  controller: _confirm,
                  label: 'Confirm new password',
                  errorKey: 'password_confirmation',
                  autofill: const [AutofillHints.newPassword],
                  action: TextInputAction.done,
                  validate: (v) => v != _password.text ? "The passwords don't match" : null,
                ),
                const SizedBox(height: AppSpacing.md),
                const NoticeCard(
                  icon: Icons.devices_outlined,
                  tone: PillTone.neutral,
                  message: 'You will stay signed in on this phone. Any other devices '
                      'will be signed out for your safety.',
                ),
                const SizedBox(height: AppSpacing.xl),
                FilledButton.icon(
                  onPressed: _saving ? null : _submit,
                  icon: _saving
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.check_rounded),
                  label: Text(_saving ? 'Saving…' : 'Change password'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
