import 'package:local_auth/local_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Device-local biometric preference and authentication for the participant app.
class BiometricAuthService {
  BiometricAuthService._();

  static final BiometricAuthService instance = BiometricAuthService._();
  static const _enabledKey = 'participant_biometric_enabled';

  final LocalAuthentication _auth = LocalAuthentication();

  Future<bool> get isEnabled async =>
      (await SharedPreferences.getInstance()).getBool(_enabledKey) ?? false;

  Future<bool> get canEnable async {
    try {
      return await _auth.isDeviceSupported() &&
          await _auth.canCheckBiometrics &&
          (await _auth.getAvailableBiometrics()).isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  /// Authenticates now, then remembers the opt-in on this device.
  Future<bool> enable() async {
    if (!await canEnable) return false;
    final authenticated = await _authenticate();
    if (!authenticated) return false;
    await (await SharedPreferences.getInstance()).setBool(_enabledKey, true);
    return true;
  }

  Future<void> disable() async {
    await (await SharedPreferences.getInstance()).setBool(_enabledKey, false);
  }

  /// Returns true when no biometric gate is enabled, or after successful auth.
  Future<bool> authenticateIfEnabled() async {
    if (!await isEnabled) return true;
    if (!await canEnable) return false;
    return _authenticate();
  }

  Future<bool> _authenticate() async {
    try {
      return await _auth.authenticate(
        localizedReason: 'Unlock your ElevateHer360 participant account',
        options: const AuthenticationOptions(
          biometricOnly: true,
          stickyAuth: true,
        ),
      );
    } catch (_) {
      return false;
    }
  }
}
