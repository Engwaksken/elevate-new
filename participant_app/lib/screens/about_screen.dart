import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/theme/app_theme.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// About, legal and support links. A privacy policy link is required by
/// both Google Play and the App Store.
class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  Future<void> _open(BuildContext context, Uri uri) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      showAppSnackBar(context, "This link couldn't be opened.", error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('About')),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: AppSpacing.listPadding,
          children: [
            const SizedBox(height: AppSpacing.lg),
            Center(
              child: ExcludeSemantics(
                child: ClipOval(
                  child: Image.asset('assets/logo.png', width: 72, height: 72),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              AppConfig.appName,
              style: theme.textTheme.headlineSmall,
              textAlign: TextAlign.center,
            ),
            Text(
              'Version ${AppConfig.appVersion}',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              'The ElevateHer360 participant app gives you your courses, '
              'assignments, mentorship sessions and opportunities, and keeps '
              'working offline.',
              style: theme.textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
            const SectionHeader(title: 'Legal'),
            Card(
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.privacy_tip_outlined),
                    title: const Text('Privacy policy'),
                    trailing: const Icon(Icons.open_in_new),
                    onTap: () =>
                        _open(context, Uri.parse(AppConfig.privacyPolicyUrl)),
                  ),
                  const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                  ListTile(
                    leading: const Icon(Icons.gavel_outlined),
                    title: const Text('Terms of use'),
                    trailing: const Icon(Icons.open_in_new),
                    onTap: () => _open(context, Uri.parse(AppConfig.termsUrl)),
                  ),
                  const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                  ListTile(
                    leading: const Icon(Icons.description_outlined),
                    title: const Text('Open-source licences'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => showLicensePage(
                      context: context,
                      applicationName: AppConfig.appName,
                      applicationVersion: AppConfig.appVersion,
                    ),
                  ),
                ],
              ),
            ),
            const SectionHeader(title: 'Help'),
            Card(
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.language),
                    title: const Text('Website'),
                    subtitle: Text(Uri.parse(AppConfig.siteUrl).host),
                    trailing: const Icon(Icons.open_in_new),
                    onTap: () => _open(context, Uri.parse(AppConfig.siteUrl)),
                  ),
                  const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                  ListTile(
                    leading: const Icon(Icons.mail_outline),
                    title: const Text('Contact support'),
                    subtitle: const Text(AppConfig.supportEmail),
                    onTap: () => _open(
                      context,
                      Uri(
                        scheme: 'mailto',
                        path: AppConfig.supportEmail,
                        queryParameters: {'subject': 'ElevateHer360 app support'},
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
