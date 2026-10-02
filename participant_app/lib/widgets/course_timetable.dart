import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'feedback.dart';

/// Uses the course payload, so the timetable remains readable from the existing
/// course-details cache when offline. Times retain the trainer's explicit zone.
class CourseTimetable extends StatelessWidget {
  const CourseTimetable({super.key, required this.slots});

  final List<Map<String, dynamic>> slots;

  Future<void> _openMeeting(BuildContext context, String link) async {
    final uri = Uri.tryParse(link);
    if (uri == null || !['http', 'https'].contains(uri.scheme)) return;
    try {
      final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!opened && context.mounted) {
        showAppSnackBar(context, 'Unable to open the meeting link.');
      }
    } catch (error) {
      if (context.mounted) showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      child: ExpansionTile(
        leading: const Icon(Icons.calendar_month_outlined),
        title: const Text('Course timetable'),
        subtitle: Text(slots.isEmpty
            ? 'No sessions scheduled yet'
            : '${slots.length} session(s) · Tap to view'),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        expandedCrossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
              'Times are shown in the listed timezone. Pull down on the course to refresh.'),
          if (slots.isEmpty)
            const Padding(
                padding: EdgeInsets.only(top: 12),
                child:
                    Text('Your instructor has not added any time slots yet.')),
          for (final slot in slots)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Divider(),
                    Text(slot['title']?.toString() ?? 'Course session',
                        style: theme.textTheme.titleMedium),
                    const SizedBox(height: 4),
                    Text(
                        '${slot['date_label'] ?? ''}\n${slot['time_label'] ?? ''} · ${slot['timezone'] ?? ''}'),
                    Text(
                        slot['status'] == 'cancelled'
                            ? 'Cancelled'
                            : slot['is_past'] == true
                                ? 'Past session'
                                : 'Scheduled',
                        style: TextStyle(
                            color: slot['status'] == 'cancelled'
                                ? theme.colorScheme.error
                                : theme.colorScheme.primary,
                            fontWeight: FontWeight.w600)),
                    if ((slot['venue']?.toString() ?? '').isNotEmpty)
                      Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text('Venue: ${slot['venue']}')),
                    if ((slot['notes']?.toString() ?? '').isNotEmpty)
                      Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(slot['notes'].toString())),
                    if (slot['status'] != 'cancelled' &&
                        (slot['meeting_link']?.toString() ?? '').isNotEmpty)
                      TextButton.icon(
                          onPressed: () => _openMeeting(
                              context, slot['meeting_link'].toString()),
                          icon: const Icon(Icons.videocam_outlined),
                          label: const Text('Online meeting')),
                  ]),
            ),
        ],
      ),
    );
  }
}
