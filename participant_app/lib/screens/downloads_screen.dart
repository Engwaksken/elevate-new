import 'package:flutter/material.dart';

import '../services/download_service.dart';
import '../services/local_database.dart';

class DownloadsScreen extends StatefulWidget {
  const DownloadsScreen({super.key});

  @override
  State<DownloadsScreen> createState() => _DownloadsScreenState();
}

class _DownloadsScreenState extends State<DownloadsScreen> {
  Future<List<Map<String, dynamic>>> _load() async {
    final rows = await LocalDatabase.instance.downloads();
    return rows.map(Map<String, dynamic>.from).toList();
  }

  Future<void> _open(Map<String, dynamic> item) async {
    await DownloadService.instance.open(item['download_key'].toString());
  }

  Future<void> _remove(Map<String, dynamic> item) async {
    await DownloadService.instance.remove(item['download_key'].toString());
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _load(),
      builder: (context, snapshot) {
        final items = snapshot.data ?? [];

        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (items.isEmpty) {
          return const Center(
            child: Text('No offline files have been downloaded yet.'),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(12),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (context, index) {
            final item = items[index];

            return Card(
              child: ListTile(
                leading: const CircleAvatar(
                  child: Icon(Icons.download_done_outlined),
                ),
                title: Text(item['file_name']?.toString() ?? 'Offline file'),
                subtitle: Text(
                  item['downloaded_at']?.toString() ?? '',
                ),
                onTap: () => _open(item),
                trailing: PopupMenuButton<String>(
                  onSelected: (value) async {
                    if (value == 'open') {
                      await _open(item);
                    } else if (value == 'remove') {
                      await _remove(item);
                    }
                  },
                  itemBuilder: (_) => const [
                    PopupMenuItem(
                      value: 'open',
                      child: Text('Open'),
                    ),
                    PopupMenuItem(
                      value: 'remove',
                      child: Text('Remove offline copy'),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }
}
