import 'package:flutter/material.dart';

import '../services/download_service.dart';
import '../services/local_database.dart';
import 'downloads_screen.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _wifiOnly = false;
  int _pending = 0;
  int _downloads = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final wifiOnly = await DownloadService.instance.wifiOnly();
    final pending = await LocalDatabase.instance.pendingOperationCount();
    final downloads = (await LocalDatabase.instance.downloads()).length;

    if (!mounted) return;

    setState(() {
      _wifiOnly = wifiOnly;
      _pending = pending;
      _downloads = downloads;
    });
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(12),
        children: [
          Card(
            child: SwitchListTile(
              title: const Text('Wi-Fi only downloads'),
              subtitle: const Text(
                'Prevent lesson and assessment files from downloading on mobile data.',
              ),
              value: _wifiOnly,
              onChanged: (value) async {
                await DownloadService.instance.setWifiOnly(value);
                setState(() => _wifiOnly = value);
              },
            ),
          ),
          Card(
            child: ListTile(
              leading: const Icon(Icons.sync_problem_outlined),
              title: const Text('Queued offline actions'),
              subtitle: const Text(
                'Actions will retry automatically when your connection returns.',
              ),
              trailing: Text('$_pending'),
            ),
          ),
          Card(
            child: ListTile(
              leading: const Icon(Icons.download_done_outlined),
              title: const Text('Offline downloads'),
              subtitle: const Text(
                'Open or remove files saved on this device.',
              ),
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('$_downloads'),
                  const SizedBox(width: 4),
                  const Icon(Icons.chevron_right),
                ],
              ),
              onTap: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => Scaffold(
                      appBar: AppBar(
                        title: const Text('Offline Downloads'),
                      ),
                      body: const DownloadsScreen(),
                    ),
                  ),
                );
                await _load();
              },
            ),
          ),
        ],
      ),
    );
  }
}
