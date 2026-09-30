import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
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
  String? _lastSync;

  @override
  void initState() {
    super.initState();
    SyncService.instance.dataVersion.addListener(_load);
    _load();
  }

  @override
  void dispose() {
    SyncService.instance.dataVersion.removeListener(_load);
    super.dispose();
  }

  Future<void> _load() async {
    final wifiOnly = await DownloadService.instance.wifiOnly();
    final pending = await LocalDatabase.instance.pendingOperationCount();
    final downloads = (await LocalDatabase.instance.downloads()).length;
    final lastSync = await LocalDatabase.instance.getMeta('last_synced_at');

    if (!mounted) return;
    setState(() {
      _wifiOnly = wifiOnly;
      _pending = pending;
      _downloads = downloads;
      _lastSync = lastSync;
    });
  }

  Future<void> _syncNow() async {
    if (!await SyncService.instance.isOnline()) {
      if (mounted) showAppSnackBar(context, "You're offline. Changes will sync when you reconnect.");
      return;
    }
    try {
      await SyncService.instance.syncNow();
      if (mounted) showAppSnackBar(context, 'Everything is up to date.');
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error, onRetry: _syncNow);
    }
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Data & offline')),
      body: SafeArea(
        top: false,
        child: RefreshIndicator(
          onRefresh: _load,
          child: ListView(
            padding: AppSpacing.listPadding,
            children: [
              const SectionHeader(title: 'Downloads'),
              Card(
                child: Column(
                  children: [
                    SwitchListTile(
                      secondary: const Icon(Icons.wifi),
                      title: const Text('Wi-Fi only downloads'),
                      subtitle: const Text('Save mobile data by downloading lesson files only on Wi-Fi.'),
                      value: _wifiOnly,
                      onChanged: (value) async {
                        await DownloadService.instance.setWifiOnly(value);
                        setState(() => _wifiOnly = value);
                      },
                    ),
                    const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                    ListTile(
                      leading: const Icon(Icons.download_done_outlined),
                      title: const Text('Offline downloads'),
                      subtitle: Text(_downloads == 1 ? '1 file on this device' : '$_downloads files on this device'),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const DownloadsScreen()),
                        );
                        await _load();
                      },
                    ),
                  ],
                ),
              ),
              const SectionHeader(title: 'Sync'),
              Card(
                child: Column(
                  children: [
                    ListTile(
                      leading: const Icon(Icons.schedule),
                      title: const Text('Last updated'),
                      subtitle: Text(_lastSync == null
                          ? 'Not synced yet'
                          : formatDateTime(_lastSync) ?? 'Unknown'),
                    ),
                    const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                    ListTile(
                      leading: const Icon(Icons.cloud_upload_outlined),
                      title: const Text('Changes waiting to sync'),
                      subtitle: Text(_pending == 0
                          ? 'Nothing waiting'
                          : 'These send automatically when you reconnect.'),
                      trailing: Text('$_pending', style: Theme.of(context).textTheme.titleMedium),
                    ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(
                          AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.lg),
                      child: SizedBox(
                        width: double.infinity,
                        child: ValueListenableBuilder<bool>(
                          valueListenable: SyncService.instance.syncing,
                          builder: (context, syncing, _) => FilledButton.tonalIcon(
                            onPressed: syncing ? null : _syncNow,
                            icon: const Icon(Icons.sync),
                            label: Text(syncing ? 'Syncing…' : 'Sync now'),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
