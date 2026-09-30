class AppConfig {
  static const String appName = 'ElevateHer360';

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://site.elevateher360.org/api/v1/participant',
  );

  /// Public website, used by the About screen.
  static const String siteUrl = String.fromEnvironment(
    'SITE_URL',
    defaultValue: 'https://site.elevateher360.org',
  );

  /// Google Play and the App Store both require a reachable privacy policy.
  /// Override with --dart-define=PRIVACY_POLICY_URL=... if it lives elsewhere.
  static const String privacyPolicyUrl = String.fromEnvironment(
    'PRIVACY_POLICY_URL',
    defaultValue: 'https://site.elevateher360.org/privacy-policy',
  );

  static const String termsUrl = String.fromEnvironment(
    'TERMS_URL',
    defaultValue: 'https://site.elevateher360.org/terms',
  );

  static const String supportEmail = String.fromEnvironment(
    'SUPPORT_EMAIL',
    defaultValue: 'support@elevateher360.org',
  );

  /// Keep in step with `version:` in pubspec.yaml.
  static const String appVersion = '1.0.0';
}
