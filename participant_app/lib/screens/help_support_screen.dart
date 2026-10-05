import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/decorations.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'it_support_screen.dart';

/// Typed view over GET /support (Admin > Support Settings on the website).
class SupportInfo {
  const SupportInfo(this.raw);

  /// Used when nothing is cached and the server can't be reached, so the
  /// participant always has a way to get help.
  static const fallback = SupportInfo({});

  final Map<String, dynamic> raw;

  String? _text(String key) {
    final value = raw[key]?.toString().trim();
    return value == null || value.isEmpty ? null : value;
  }

  String get introduction =>
      _text('introduction') ??
      'Contact the ElevateHer360 support team if you need assistance.';
  String get email => _text('email') ?? AppConfig.supportEmail;
  String? get alternateEmail => _text('alternate_email');
  String? get phone => _text('phone');
  String? get whatsapp => _text('whatsapp');
  String? get whatsappUrl => _text('whatsapp_url');
  String? get hours => _text('hours');
  String? get branch => _text('branch');
  String? get address => _text('address');
  String? get technical => _text('technical');
  String? get helpPageUrl => _text('help_page_url');
}

class HelpSupportScreen extends StatefulWidget {
  const HelpSupportScreen({super.key});

  @override
  State<HelpSupportScreen> createState() => _HelpSupportScreenState();
}

class _HelpSupportScreenState extends State<HelpSupportScreen> {
  SupportInfo? _info;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final cached = await ParticipantDataService.instance.cachedSupport();
    if (!mounted) return;
    setState(() {
      if (cached != null) _info = SupportInfo(cached);
      _loading = cached == null;
    });
    await _refresh(quiet: true);
  }

  Future<void> _refresh({bool quiet = false}) async {
    try {
      if (!await SyncService.instance.isOnline()) {
        if (!quiet && mounted) {
          showAppSnackBar(context, "You're offline. Showing the support details saved on this device.");
        }
        return;
      }
      final support = await ParticipantDataService.instance.refreshSupport();
      if (mounted) setState(() => _info = SupportInfo(support));
    } catch (error) {
      if (!quiet && mounted) showErrorSnackBar(context, error, onRetry: _refresh);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _launch(Uri uri, String failMessage) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) showAppSnackBar(context, failMessage);
  }

  void _email(String address) => _launch(
        Uri(
          scheme: 'mailto',
          path: address,
          queryParameters: {'subject': 'ElevateHer360 app support'},
        ),
        'Email us at $address and we will be glad to help.',
      );

  void _call(String phone) => _launch(
        Uri(scheme: 'tel', path: phone.replaceAll(RegExp(r'[^\d+]'), '')),
        'Call us on $phone.',
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Help & support')),
      body: SafeArea(
        top: false,
        child: _loading && _info == null
            ? const LoadingSkeleton(itemHeight: 72)
            : RefreshIndicator(onRefresh: _refresh, child: _content(_info ?? SupportInfo.fallback)),
      ),
    );
  }

  Widget _content(SupportInfo info) {
    final theme = Theme.of(context);

    return ListView(
      padding: AppSpacing.listPadding,
      children: [
        GradientHeader(
          child: Row(
            children: [
              const ExcludeSemantics(
                child: Icon(Icons.support_agent_rounded, size: 40, color: Colors.white),
              ),
              const SizedBox(width: AppSpacing.lg),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Semantics(
                      header: true,
                      child: Text(
                        "We're here to help",
                        style: theme.textTheme.titleLarge?.copyWith(color: Colors.white),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      info.introduction,
                      style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SectionHeader(title: 'Contact us'),
        Card(
          child: Column(
            children: [
              _ContactTile(
                icon: Icons.mail_outline_rounded,
                title: 'Email',
                value: info.email,
                onTap: () => _email(info.email),
              ),
              if (info.alternateEmail != null)
                _ContactTile(
                  icon: Icons.alternate_email_rounded,
                  title: 'Alternative email',
                  value: info.alternateEmail!,
                  onTap: () => _email(info.alternateEmail!),
                ),
              if (info.phone != null)
                _ContactTile(
                  icon: Icons.call_outlined,
                  title: 'Phone',
                  value: info.phone!,
                  onTap: () => _call(info.phone!),
                ),
              if (info.whatsappUrl != null)
                _ContactTile(
                  icon: Icons.chat_outlined,
                  title: 'WhatsApp',
                  value: info.whatsapp ?? 'Chat with us',
                  onTap: () => _launch(
                    Uri.parse(info.whatsappUrl!),
                    'Message us on WhatsApp at ${info.whatsapp}.',
                  ),
                ),
            ],
          ),
        ),
        if (info.hours != null || info.branch != null || info.address != null) ...[
          const SectionHeader(title: 'Where and when'),
          Card(
            child: Column(
              children: [
                if (info.hours != null)
                  _ContactTile(icon: Icons.schedule_rounded, title: 'Support hours', value: info.hours!),
                if (info.branch != null)
                  _ContactTile(icon: Icons.apartment_rounded, title: 'Branch', value: info.branch!),
                if (info.address != null)
                  _ContactTile(icon: Icons.place_outlined, title: 'Address', value: info.address!),
              ],
            ),
          ),
        ],
        const SectionHeader(title: 'IT support'),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.build_outlined),
                title: const Text('Submit an IT support request'),
                subtitle: const Text('Report a problem and track your requests'),
                trailing: const Icon(Icons.chevron_right_rounded),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const ItSupportScreen()),
                ),
              ),
            ],
          ),
        ),
        if (info.technical != null) ...[
          const SectionHeader(title: 'Technical help'),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Text(info.technical!, style: theme.textTheme.bodyMedium),
            ),
          ),
        ],
        if (info.helpPageUrl != null) ...[
          const SizedBox(height: AppSpacing.lg),
          OutlinedButton.icon(
            onPressed: () => _launch(
              Uri.parse(info.helpPageUrl!),
              'Open ${info.helpPageUrl} in your browser.',
            ),
            icon: const Icon(Icons.open_in_new_rounded),
            label: const Text('Open help page on the website'),
          ),
        ],
        const SizedBox(height: AppSpacing.xl),
      ],
    );
  }
}

class _ContactTile extends StatelessWidget {
  const _ContactTile({
    required this.icon,
    required this.title,
    required this.value,
    this.onTap,
  });

  final IconData icon;
  final String title;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: scheme.primaryContainer,
        foregroundColor: scheme.onPrimaryContainer,
        child: Icon(icon, size: 20),
      ),
      title: Text(title),
      subtitle: Text(value),
      trailing: onTap == null ? null : const Icon(Icons.chevron_right_rounded),
      onTap: onTap,
    );
  }
}
