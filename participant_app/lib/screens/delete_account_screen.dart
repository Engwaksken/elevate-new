import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/state_views.dart';

/// Ask for the account and its personal data to be deleted. The request is
/// reviewed by an admin and completed within the period the server reports
/// (30 days); she can keep using the app until then. Required by Google Play.
class DeleteAccountScreen extends StatefulWidget {
  const DeleteAccountScreen({super.key, this.existingRequest});

  /// A request already on file (shown instead of the form while pending).
  final Map<String, dynamic>? existingRequest;

  @override
  State<DeleteAccountScreen> createState() => _DeleteAccountScreenState();
}

class _DeleteAccountScreenState extends State<DeleteAccountScreen> {
  final _formKey = GlobalKey<FormState>();
  final _password = TextEditingController();
  final _reason = TextEditingController();

  bool _obscure = true;
  bool _understood = false;
  bool _saving = false;
  Map<String, String> _errors = {};
  String? _general;
  Map<String, dynamic>? _request;

  @override
  void initState() {
    super.initState();
    _request = widget.existingRequest;
  }

  @override
  void dispose() {
    _password.dispose();
    _reason.dispose();
    super.dispose();
  }

  bool get _pending => _request?['status'] == 'pending';

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _errors = {};
      _general = null;
    });
    if (!_formKey.currentState!.validate()) return;
    if (!_understood) {
      setState(() => _general = 'Tick the box to confirm you understand your account will be deleted.');
      return;
    }
    if (!await SyncService.instance.isOnline()) {
      if (mounted) setState(() => _general = "You're offline. Connect to the internet to send your request.");
      return;
    }

    setState(() => _saving = true);
    try {
      final result = await ParticipantDataService.instance.requestAccountDeletion(
        password: _password.text,
        reason: _reason.text,
      );
      final request = result['request'];
      if (!mounted) return;
      setState(() => _request = request is Map ? Map<String, dynamic>.from(request) : {'status': 'pending'});
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

  String _date(String? iso, {int addDays = 0}) {
    final parsed = iso == null ? null : DateTime.tryParse(iso)?.toLocal();
    if (parsed == null) return '';
    final date = parsed.add(Duration(days: addDays));
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant);
    final days = (_request?['days_to_process'] as num?)?.toInt() ?? 30;

    return Scaffold(
      appBar: AppBar(title: const Text('Delete my account')),
      body: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: AppSpacing.listPadding,
            children: [
              const SizedBox(height: AppSpacing.sm),
              if (_pending) ...[
                NoticeCard(
                  icon: Icons.hourglass_top,
                  tone: PillTone.warning,
                  message: 'We received your request on ${_date(_request?['requested_at'] as String?)}. '
                      'Your account will be deleted by ${_date(_request?['requested_at'] as String?, addDays: days)}, '
                      'and you will be signed out. You can keep using the app until then.',
                ),
                const SizedBox(height: AppSpacing.lg),
                Text('Changed your mind? Contact ${AppConfig.supportEmail} before then.', style: muted),
              ] else ...[
                if (_request?['status'] == 'rejected' && _request?['admin_note'] != null) ...[
                  NoticeCard(
                    icon: Icons.info_outline,
                    tone: PillTone.neutral,
                    message: 'Your last request was not completed: ${_request?['admin_note']}',
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],
                Text(
                  'You can ask us to delete your ElevateHer360 account. Our team will complete it within $days days.',
                  style: theme.textTheme.bodyLarge,
                ),
                const SizedBox(height: AppSpacing.md),
                Text('What is deleted', style: theme.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  'Your name, contact details and password, your profile and photo, resumes and cover letters, '
                  'goals, AI mentor conversations, reading history, notifications and app sign-ins.',
                  style: muted,
                ),
                const SizedBox(height: AppSpacing.md),
                Text('What is kept without your name', style: theme.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  'Course, certificate, attendance and feedback records, for programme reports. '
                  'Download any certificates you want to keep first.',
                  style: muted,
                ),
                TextButton.icon(
                  onPressed: () => launchUrl(Uri.parse('${AppConfig.siteUrl}/account-deletion'), mode: LaunchMode.externalApplication),
                  icon: const Icon(Icons.open_in_new, size: 18),
                  label: const Text('Read the full details'),
                ),
                if (_general != null) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Semantics(
                    liveRegion: true,
                    child: NoticeCard(icon: Icons.error_outline, tone: PillTone.danger, message: _general!),
                  ),
                ],
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _password,
                  enabled: !_saving,
                  obscureText: _obscure,
                  autocorrect: false,
                  enableSuggestions: false,
                  keyboardType: TextInputType.visiblePassword,
                  autofillHints: const [AutofillHints.password],
                  decoration: InputDecoration(
                    labelText: 'Your password',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      tooltip: _obscure ? 'Show password' : 'Hide password',
                      onPressed: () => setState(() => _obscure = !_obscure),
                      icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                    ),
                  ),
                  validator: (value) => _errors['password'] ?? ((value ?? '').isEmpty ? 'Enter your password.' : null),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _reason,
                  enabled: !_saving,
                  maxLines: 3,
                  maxLength: 1000,
                  decoration: const InputDecoration(labelText: 'Why are you leaving? (optional)'),
                ),
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  controlAffinity: ListTileControlAffinity.leading,
                  value: _understood,
                  onChanged: _saving ? null : (value) => setState(() => _understood = value ?? false),
                  title: const Text('I understand my account and personal data will be permanently deleted.'),
                ),
                const SizedBox(height: AppSpacing.md),
                FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: theme.colorScheme.error,
                    foregroundColor: theme.colorScheme.onError,
                  ),
                  onPressed: _saving ? null : _submit,
                  icon: _saving
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Icon(Icons.person_remove_outlined),
                  label: Text(_saving ? 'Sending…' : 'Ask to delete my account'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
