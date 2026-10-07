import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

class DownloadsScreen extends StatefulWidget {
  const DownloadsScreen({super.key});

  @override
  State<DownloadsScreen> createState() => _DownloadsScreenState();
}

class _DownloadsScreenState extends State<DownloadsScreen> {
  List<Map<String, dynamic>>? _items;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      // Course files that are view-only now never stay on the device.
      await DownloadService.instance.purgeViewOnlyCopies();
      final rows = (await LocalDatabase.instance.downloads())
          .where((row) => !DownloadService.isAutomaticCacheKey(
              row['download_key']?.toString() ?? ''))
          .toList();
      if (mounted) {
        setState(() {
          _items = rows.map(Map<String, dynamic>.from).toList();
          _error = null;
        });
      }
    } catch (error) {
      if (mounted) setState(() => _error = error);
    }
  }

  Future<void> _open(Map<String, dynamic> item) async {
    try {
      await DownloadService.instance.open(item['download_key'].toString());
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
      await _load();
    }
  }

  Future<void> _remove(Map<String, dynamic> item) async {
    final ok = await confirmDialog(
      context,
      title: 'Remove offline copy?',
      message: '"${item['file_name']}" will be deleted from this device.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok) return;
    await DownloadService.instance.remove(item['download_key'].toString());
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Offline downloads')),
      body: SafeArea(top: false, child: _content()),
    );
  }

  Widget _content() {
    if (_error != null) return ErrorState(error: _error!, onRetry: _load);
    final items = _items;
    if (items == null) return const LoadingSkeleton();

    return RefreshIndicator(
      onRefresh: _load,
      child: items.isEmpty
          ? const EmptyState(
              icon: Icons.download_outlined,
              title: 'No downloads yet',
              message: 'Spreadsheets, CSV and ZIP files from your lessons can be saved here '
                  'for use without an internet connection. Other course files open in the app.',
            )
          : ListView.separated(
              padding: AppSpacing.listPadding,
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, index) {
                final item = items[index];
                final name = item['file_name']?.toString() ?? 'Offline file';
                return Card(
                  child: ListTile(
                    leading: const CircleAvatar(child: Icon(Icons.description_outlined)),
                    title: Text(name),
                    subtitle: Text('Downloaded ${relativeTime(item['downloaded_at'])}'),
                    onTap: () => _open(item),
                    trailing: IconButton(
                      tooltip: 'Remove $name',
                      icon: const Icon(Icons.delete_outline),
                      onPressed: () => _remove(item),
                    ),
                  ),
                );
              },
            ),
    );
  }
}
