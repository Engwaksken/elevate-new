import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';

class JobsScreen extends StatefulWidget {
  const JobsScreen({super.key});

  @override
  State<JobsScreen> createState() => _JobsScreenState();
}

class _JobsScreenState extends State<JobsScreen> {
  String _search = '';

  Future<List<Map<String, dynamic>>> _load() =>
      LocalDatabase.instance.readCollection('jobs');

  Future<void> _toggle(Map<String, dynamic> job) async {
    final id = int.tryParse(job['id']?.toString() ?? '');
    if (id == null) return;

    final saved = job['is_saved'] == 1 ||
        job['is_saved'] == true ||
        job['is_saved']?.toString() == '1';

    final online = await SyncService.instance.isOnline();

    if (online) {
      if (saved) {
        await ApiService.instance.unsaveJob(id);
      } else {
        await ApiService.instance.saveJob(id);
      }
    } else {
      await SyncService.instance.queueAction(
        saved ? 'unsave_job' : 'save_job',
        {'job_id': id},
      );
    }

    job['is_saved'] = saved ? 0 : 1;
    await LocalDatabase.instance.cacheItem(
      collection: 'jobs',
      itemId: id.toString(),
      payload: job,
    );

    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _load(),
      builder: (context, snapshot) {
        var jobs = snapshot.data ?? [];

        if (_search.isNotEmpty) {
          final term = _search.toLowerCase();
          jobs = jobs
              .where((job) => job.toString().toLowerCase().contains(term))
              .toList();
        }

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: TextField(
                onChanged: (value) => setState(() => _search = value.trim()),
                decoration: const InputDecoration(
                  hintText: 'Search jobs',
                  prefixIcon: Icon(Icons.search),
                  border: OutlineInputBorder(),
                ),
              ),
            ),
            Expanded(
              child: jobs.isEmpty
                  ? const Center(child: Text('No synced jobs available.'))
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
                      itemCount: jobs.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (context, index) {
                        final job = jobs[index];
                        final saved = job['is_saved'] == 1 ||
                            job['is_saved'] == true ||
                            job['is_saved']?.toString() == '1';

                        return Card(
                          child: ListTile(
                            title: Text(job['title']?.toString() ?? 'Job'),
                            subtitle: Text(
                              [
                                job['company_name'],
                                job['location'],
                                job['employment_type'],
                              ]
                                  .where(
                                    (value) =>
                                        value != null &&
                                        value.toString().isNotEmpty,
                                  )
                                  .join(' · '),
                            ),
                            trailing: IconButton(
                              tooltip: saved ? 'Remove saved job' : 'Save job',
                              onPressed: () => _toggle(job),
                              icon: Icon(
                                saved ? Icons.bookmark : Icons.bookmark_border,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        );
      },
    );
  }
}
