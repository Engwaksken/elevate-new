class AppConfig {
  static const String appName = 'ElevateHer360';
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://site.elevateher360.org/api/v1/participant',
  );
}
