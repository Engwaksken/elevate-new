import 'dart:async';
import 'dart:io';

import 'package:android_intent_plus/android_intent.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/sync_service.dart';
import 'logger.dart';
import 'theme/app_theme.dart';

/// Shown at the top of the home screen only while the device is offline.
class ConnectivityBanner extends StatefulWidget {
  const ConnectivityBanner({super.key});

  @override
  State<ConnectivityBanner> createState() => _ConnectivityBannerState();
}

class _ConnectivityBannerState extends State<ConnectivityBanner> {
  bool _online = true;
  StreamSubscription<bool>? _sub;

  @override
  void initState() {
    super.initState();
    _check();
    _sub = SyncService.instance.onlineChanges.listen((online) {
      if (mounted) setState(() => _online = online);
    });
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    final results = await Connectivity().checkConnectivity();
    if (mounted) {
      setState(() {
        _online = results.any((item) => item != ConnectivityResult.none);
      });
    }
  }

  Future<void> _openNetworkSettings() async {
    try {
      if (Platform.isAndroid) {
        const intent = AndroidIntent(action: 'android.settings.WIFI_SETTINGS');
        await intent.launch();
        return;
      }
      await launchUrl(Uri.parse('app-settings:'));
    } catch (error) {
      appLog('Could not open network settings', error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final bg = dark
        ? AppColors.warningContainerDark
        : AppColors.warningContainerLight;
    final fg = dark
        ? AppColors.onWarningContainerDark
        : AppColors.onWarningContainerLight;

    return AnimatedSize(
      duration: const Duration(milliseconds: 200),
      child: _online
          ? const SizedBox(width: double.infinity)
          : Semantics(
              liveRegion: true,
              container: true,
              child: Material(
                color: bg,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg,
                    AppSpacing.xs,
                    AppSpacing.xs,
                    AppSpacing.xs,
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.cloud_off_outlined, color: fg, size: 20),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Text(
                          "You're offline. Saved content is still available.",
                          style: Theme.of(context)
                              .textTheme
                              .bodyMedium
                              ?.copyWith(color: fg, fontWeight: FontWeight.w600),
                        ),
                      ),
                      TextButton(
                        style: TextButton.styleFrom(foregroundColor: fg),
                        onPressed: _openNetworkSettings,
                        child: const Text('Settings'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
    );
  }
}
