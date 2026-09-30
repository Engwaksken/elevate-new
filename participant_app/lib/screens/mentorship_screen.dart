import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/local_database.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

class MentorshipScreen extends StatefulWidget {
  const MentorshipScreen({super.key});

  @override
  State<MentorshipScreen> createState() => _MentorshipScreenState();
}

class _MentorshipScreenState extends State<MentorshipScreen>
    with CachedDataMixin<MentorshipScreen, List<Map<String, dynamic>>> {
  @override
  Future<List<Map<String, dynamic>>> readCache() async {
    final items = await LocalDatabase.instance.readCollection('mentorship');
    items.sort((a, b) => (b['scheduled_at']?.toString() ?? '')
        .compareTo(a['scheduled_at']?.toString() ?? ''));
    return items;
  }

  Future<void> _join(String link) async {
    final uri = Uri.tryParse(link);
    final ok = uri != null && await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      showAppSnackBar(context, "The meeting link couldn't be opened.", error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const LoadingSkeleton();
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final items = data ?? const [];
    final now = DateTime.now();
    bool upcoming(Map<String, dynamic> s) =>
        DateTime.tryParse(s['scheduled_at']?.toString() ?? '')?.isAfter(now) ?? false;

    final next = items.where(upcoming).toList().reversed.toList();
    final past = items.where((s) => !upcoming(s)).toList();

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: items.isEmpty
          ? const EmptyState(
              icon: Icons.diversity_3_outlined,
              title: 'No mentorship sessions yet',
              message: 'When you are matched with a mentor, your sessions will appear here.',
            )
          : ListView(
              padding: AppSpacing.listPadding,
              children: [
                if (next.isNotEmpty) ...[
                  const SectionHeader(title: 'Upcoming'),
                  for (final s in next) _SessionCard(session: s, onJoin: _join),
                ],
                if (past.isNotEmpty) ...[
                  const SectionHeader(title: 'Past sessions'),
                  for (final s in past) _SessionCard(session: s, onJoin: null),
                ],
              ],
            ),
    );
  }
}

class _SessionCard extends StatelessWidget {
  const _SessionCard({required this.session, required this.onJoin});

  final Map<String, dynamic> session;
  final ValueChanged<String>? onJoin;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final link = session['meeting_link']?.toString() ?? '';
    final agenda = plainParagraphs(session['agenda']?.toString()).join('\n\n');
    final notes = plainParagraphs(session['session_notes']?.toString()).join('\n\n');
    final actions = plainParagraphs(session['agreed_actions']?.toString()).join('\n\n');
    final venue = session['venue']?.toString() ?? '';

    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Card(
        child: Theme(
          data: theme.copyWith(dividerColor: Colors.transparent),
          child: ExpansionTile(
            leading: const CircleAvatar(child: Icon(Icons.diversity_3_outlined)),
            title: Text(tidyTitle(session['title']?.toString(), fallback: 'Mentorship session')),
            subtitle: Text([
              session['mentor_name'],
              formatDateTime(session['scheduled_at']),
              humanise(session['status']),
            ].where((v) => v != null && v.toString().isNotEmpty).join(' · ')),
            childrenPadding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.lg),
            expandedCrossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (agenda.isNotEmpty) ...[
                Text('Agenda', style: theme.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(agenda, style: theme.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.md),
              ],
              if (notes.isNotEmpty) ...[
                Text('Session notes', style: theme.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(notes, style: theme.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.md),
              ],
              if (actions.isNotEmpty) ...[
                Text('Agreed actions', style: theme.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(actions, style: theme.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.md),
              ],
              if (venue.isNotEmpty)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.location_on_outlined),
                  title: Text(venue),
                ),
              if (onJoin != null && link.isNotEmpty)
                FilledButton.icon(
                  onPressed: () => onJoin!(link),
                  icon: const Icon(Icons.video_call_outlined),
                  label: const Text('Join session'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
