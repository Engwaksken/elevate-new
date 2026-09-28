import 'dart:io';
import 'package:android_intent_plus/android_intent.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/sync_service.dart';

class ConnectivityBanner extends StatefulWidget {
  const ConnectivityBanner({super.key});

  @override
  State<ConnectivityBanner> createState() => _ConnectivityBannerState();
}

class _ConnectivityBannerState extends State<ConnectivityBanner> {
  bool _online = true;

  @override
  void initState() {
    super.initState();
    _check();
    SyncService.instance.onlineChanges.listen((online) {
      if (mounted) setState(() => _online = online);
    });
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
    if (Platform.isAndroid) {
      const intent = AndroidIntent(action: 'android.settings.WIFI_SETTINGS');
      await intent.launch();
      return;
    }

    final uri = Uri.parse('app-settings:');
    await launchUrl(uri);
  }

  @override
  Widget build(BuildContext context) {
    if (_online) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        color: Colors.green.shade50,
        child: const Row(
          children: [
            Icon(Icons.cloud_done_outlined, size: 18),
            SizedBox(width: 8),
            Text('Online — updates can sync'),
          ],
        ),
      );
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      color: Colors.amber.shade100,
      child: Row(
        children: [
          const Expanded(
            child: Text(
              'Offline — previously synced data remains available.',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          TextButton(
            onPressed: _openNetworkSettings,
            child: const Text('Open Network Settings'),
          ),
        ],
      ),
    );
  }
}
