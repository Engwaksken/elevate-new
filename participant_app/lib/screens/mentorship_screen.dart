import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/local_database.dart';

class MentorshipScreen extends StatefulWidget {
  const MentorshipScreen({super.key});

  @override
  State<MentorshipScreen> createState() => _MentorshipScreenState();
}

class _MentorshipScreenState extends State<MentorshipScreen> {
  Future<List<Map<String, dynamic>>> _load() =>
      LocalDatabase.instance.readCollection('mentorship');

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
            child: Text('No synced mentorship sessions available.'),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(12),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (context, index) {
            final item = items[index];
            final meetingLink = item['meeting_link']?.toString();

            return Card(
              child: ExpansionTile(
                leading: const CircleAvatar(
                  child: Icon(Icons.diversity_3_outlined),
                ),
                title: Text(item['title']?.toString() ?? 'Mentorship Session'),
                subtitle: Text(
                  [
                    item['mentor_name'],
                    item['scheduled_at'],
                    item['status'],
                  ]
                      .where(
                        (value) => value != null && value.toString().isNotEmpty,
                      )
                      .join(' · '),
                ),
                childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                children: [
                  if ((item['agenda']?.toString() ?? '').isNotEmpty)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(item['agenda'].toString()),
                    ),
                  if ((item['venue']?.toString() ?? '').isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.location_on_outlined),
                      title: Text(item['venue'].toString()),
                    ),
                  if (meetingLink != null && meetingLink.isNotEmpty)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: FilledButton.icon(
                        onPressed: () async {
                          final uri = Uri.tryParse(meetingLink);
                          if (uri != null) {
                            await launchUrl(
                              uri,
                              mode: LaunchMode.externalApplication,
                            );
                          }
                        },
                        icon: const Icon(Icons.video_call_outlined),
                        label: const Text('Join Session'),
                      ),
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
