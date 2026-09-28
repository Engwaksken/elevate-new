import 'package:flutter/material.dart';

import '../services/local_database.dart';

class CachedListScreen extends StatefulWidget {
  const CachedListScreen({
    super.key,
    required this.collection,
    required this.title,
    required this.icon,
  });

  final String collection;
  final String title;
  final IconData icon;

  @override
  State<CachedListScreen> createState() => _CachedListScreenState();
}

class _CachedListScreenState extends State<CachedListScreen> {
  String _search = '';

  Future<List<Map<String, dynamic>>> _load() {
    return LocalDatabase.instance.readCollection(widget.collection);
  }

  String _title(Map<String, dynamic> item) {
    return (item['title'] ??
            item['name'] ??
            item['mentor_name'] ??
            item['company_name'] ??
            item['message'] ??
            widget.title)
        .toString();
  }

  String _subtitle(Map<String, dynamic> item) {
    final values = [
      item['company_name'],
      item['status'],
      item['scheduled_at'],
      item['starts_at'],
      item['due_at'],
      item['location'],
      item['message'],
    ].where((value) => value != null && value.toString().isNotEmpty).take(3);
    return values.join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _load(),
      builder: (context, snapshot) {
        var items = snapshot.data ?? [];
        if (_search.isNotEmpty) {
          final term = _search.toLowerCase();
          items = items
              .where((item) => item.toString().toLowerCase().contains(term))
              .toList();
        }

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: TextField(
                onChanged: (value) => setState(() => _search = value.trim()),
                decoration: InputDecoration(
                  hintText: 'Search ${widget.title.toLowerCase()}',
                  prefixIcon: const Icon(Icons.search),
                  border: const OutlineInputBorder(),
                  isDense: true,
                ),
              ),
            ),
            Expanded(
              child: snapshot.connectionState == ConnectionState.waiting
                  ? const Center(child: CircularProgressIndicator())
                  : items.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(widget.icon, size: 54, color: Colors.grey),
                              const SizedBox(height: 12),
                              Text(
                                  'No ${widget.title.toLowerCase()} available offline yet.'),
                            ],
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: () async => setState(() {}),
                          child: ListView.separated(
                            padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
                            itemCount: items.length,
                            separatorBuilder: (_, __) =>
                                const SizedBox(height: 8),
                            itemBuilder: (context, index) {
                              final item = items[index];
                              return Card(
                                child: ListTile(
                                  leading:
                                      CircleAvatar(child: Icon(widget.icon)),
                                  title: Text(_title(item)),
                                  subtitle: Text(_subtitle(item)),
                                  trailing: const Icon(Icons.chevron_right),
                                ),
                              );
                            },
                          ),
                        ),
            ),
          ],
        );
      },
    );
  }
}
