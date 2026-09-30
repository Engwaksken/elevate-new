import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/session_info.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/notification_service.dart';
import '../services/sync_service.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';

class MentorshipScreen extends StatefulWidget {
  const MentorshipScreen({super.key});

  @override
  State<MentorshipScreen> createState() => _MentorshipScreenState();
}

class _MentorshipScreenState extends State<MentorshipScreen>
    with CachedDataMixin<MentorshipScreen, List<SessionInfo>> {
  final Set<int> _saving = {};

  @override
  Future<List<SessionInfo>> readCache() async {
    final db = LocalDatabase.instance;
    final items = await db.readCollection('mentorship');

    // Answers still waiting in the offline queue override the cache, so a
    // refresh never brings back a question she already answered.
    final queued = <int, bool>{};
    for (final op in await db.pendingOperations()) {
      if (op['type'] != SyncService.attendanceOperation) continue;
      final payload = op['payload'] as Map;
      final id = asInt(payload['session_id']);
      if (id != null) queued[id] = payload['attended'] == true;
    }
    for (final item in items) {
      final id = asInt(item['id']);
      if (id != null && queued.containsKey(id)) item['mentee_attended'] = queued[id];
    }
    return items.map(SessionInfo.new).toList();
  }

  Future<void> _join(String link) async {
    final uri = Uri.tryParse(link);
    final ok = uri != null && await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      showAppSnackBar(context, "The meeting link couldn't be opened.", error: true);
    }
  }

  Future<void> _remind(SessionInfo session) async {
    final start = session.scheduledAt;
    if (start == null) return;
    try {
      final ok = await NotificationService.instance.scheduleSessionReminder(
        sessionId: session.id ?? session.title,
        title: session.title,
        scheduledAt: start,
      );
      if (!mounted) return;
      showAppSnackBar(
        context,
        ok
            ? "Reminder set. We'll nudge you about an hour before."
            : 'This session starts within the hour, so it is too late for a reminder.',
      );
    } catch (_) {
      if (mounted) {
        showAppSnackBar(context, "We couldn't set a reminder on this device.", error: true);
      }
    }
  }

  Future<void> _saveLocal(SessionInfo session, bool? attended, {Map<String, dynamic>? server}) async {
    final id = session.id;
    if (id == null) return;
    final item = server ?? {...session.raw, 'mentee_attended': attended};
    await LocalDatabase.instance.cacheItem(
      collection: 'mentorship',
      itemId: id.toString(),
      payload: item,
    );
    SyncService.instance.notifyLocalChange();
  }

  static String _messageFor(AppException e) => switch (e.code) {
        'session_not_started' => "This session hasn't started yet, so attendance can't be confirmed.",
        'attendance_window_closed' => 'Attendance can only be confirmed within 14 days of the session.',
        'session_cancelled' => 'This session was cancelled.',
        _ => e.message,
      };

  Future<void> _answer(SessionInfo session, bool attended) async {
    final id = session.id;
    if (id == null || _saving.contains(id)) return;
    final previous = session.menteeAttended;
    setState(() => _saving.add(id));
    await _saveLocal(session, attended);

    Future<void> queue() async {
      await SyncService.instance.queueAttendance(sessionId: id, attended: attended);
      if (mounted) {
        showAppSnackBar(context, "Thank you! We'll send your answer when you're back online.");
      }
    }

    try {
      if (!await SyncService.instance.isOnline()) {
        await queue();
        return;
      }
      final response = await ApiService.instance.recordAttendance(
        sessionId: id,
        attended: attended,
      );
      final server = response['session'];
      if (server is Map) {
        await _saveLocal(session, attended, server: Map<String, dynamic>.from(server));
      }
      if (mounted) {
        showAppSnackBar(
          context,
          attended ? 'Wonderful, thanks for letting us know!' : 'Thanks for letting us know.',
        );
      }
    } catch (error) {
      final mapped = AppException.from(error);
      if (mapped.isRetryable) {
        await queue();
      } else {
        await _saveLocal(session, previous);
        if (mounted) showAppSnackBar(context, _messageFor(mapped), error: true);
      }
    } finally {
      if (mounted) setState(() => _saving.remove(id));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const LoadingSkeleton();
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final sessions = data ?? const <SessionInfo>[];
    DateTime at(SessionInfo s) => s.scheduledAt ?? DateTime(1970);
    final upcoming = sessions.where((s) => s.isUpcoming).toList()
      ..sort((a, b) => at(a).compareTo(at(b)));
    final past = sessions.where((s) => !s.isUpcoming).toList()
      ..sort((a, b) => at(b).compareTo(at(a)));
    final summary = AttendanceSummary.of(sessions);

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: sessions.isEmpty
          ? const EmptyState(
              icon: Icons.diversity_3_outlined,
              title: 'No mentorship sessions yet',
              message: 'When you are matched with a mentor, your sessions will appear '
                  'here. Great things are coming!',
            )
          : ListView(
              padding: AppSpacing.listPadding,
              children: [
                const SizedBox(height: AppSpacing.sm),
                _AttendanceSummaryCard(summary: summary),
                SectionHeader(title: 'Upcoming (${upcoming.length})'),
                if (upcoming.isEmpty)
                  const Card(
                    child: ListTile(
                      leading: Icon(Icons.event_available_outlined),
                      title: Text('No upcoming sessions'),
                      subtitle: Text('Your next session will show up here once it is scheduled.'),
                    ),
                  ),
                for (final s in upcoming)
                  _SessionCard(
                    session: s,
                    onJoin: s.status == SessionStatus.cancelled ? null : _join,
                    onRemind: s.status == SessionStatus.cancelled ? null : () => _remind(s),
                  ),
                if (past.isNotEmpty) ...[
                  SectionHeader(title: 'Past (${past.length})'),
                  for (final s in past)
                    _SessionCard(
                      session: s,
                      saving: s.id != null && _saving.contains(s.id),
                      onAnswer: (attended) => _answer(s, attended),
                    ),
                ],
              ],
            ),
    );
  }
}

class _AttendanceSummaryCard extends StatelessWidget {
  const _AttendanceSummaryCard({required this.summary});

  final AttendanceSummary summary;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final rate = summary.ratePercent;
    final label = rate == null
        ? 'No past sessions yet'
        : 'Attended ${summary.attended} of ${summary.total} sessions, $rate percent';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Semantics(
          container: true,
          label: '$label. ${summary.missed} missed. ${summary.upcoming} upcoming.',
          excludeSemantics: true,
          child: Row(
            children: [
              ProgressRing(
                value: rate == null ? 0 : rate / 100,
                size: 72,
                color: scheme.tertiary,
                center: Text(rate == null ? '–' : '$rate%', style: theme.textTheme.titleMedium),
              ),
              const SizedBox(width: AppSpacing.lg),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Your attendance', style: theme.textTheme.titleMedium),
                    Text(
                      rate == null
                          ? 'Your first session is on its way.'
                          : '${summary.attended} of ${summary.total} sessions attended',
                      style: theme.textTheme.bodyMedium,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Wrap(
                      spacing: AppSpacing.sm,
                      runSpacing: AppSpacing.xs,
                      children: [
                        StatusPill(
                          label: '${summary.missed} missed',
                          tone: summary.missed > 0 ? PillTone.danger : PillTone.neutral,
                          icon: Icons.event_busy_outlined,
                        ),
                        StatusPill(
                          label: '${summary.upcoming} upcoming',
                          tone: PillTone.accent,
                          icon: Icons.upcoming_outlined,
                        ),
                      ],
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

class _SessionCard extends StatefulWidget {
  const _SessionCard({
    required this.session,
    this.onJoin,
    this.onRemind,
    this.onAnswer,
    this.saving = false,
  });

  final SessionInfo session;
  final ValueChanged<String>? onJoin;
  final VoidCallback? onRemind;
  final ValueChanged<bool>? onAnswer;
  final bool saving;

  @override
  State<_SessionCard> createState() => _SessionCardState();
}

class _SessionCardState extends State<_SessionCard> {
  bool _expanded = false;
  bool _changing = false;

  static (PillTone, IconData) _statusStyle(SessionStatus status) => switch (status) {
        SessionStatus.scheduled => (PillTone.accent, Icons.schedule_rounded),
        SessionStatus.completed => (PillTone.success, Icons.check_circle_outline),
        SessionStatus.missed => (PillTone.danger, Icons.event_busy_outlined),
        SessionStatus.cancelled => (PillTone.neutral, Icons.block_outlined),
      };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final s = widget.session;
    final (tone, icon) = _statusStyle(s.status);
    final agenda = plainParagraphs(s.raw['agenda']?.toString()).join('\n\n');
    final notes = plainParagraphs(s.raw['session_notes']?.toString()).join('\n\n');
    final actions = plainParagraphs(s.raw['agreed_actions']?.toString()).join('\n\n');
    final hasDetails = agenda.isNotEmpty || notes.isNotEmpty || actions.isNotEmpty;
    final upcoming = s.isUpcoming;

    final when = [
      formatDateTime(s.raw['scheduled_at']),
      '${s.durationMinutes} min',
    ].whereType<String>().join(' · ');

    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.xs,
                children: [
                  StatusPill(label: s.statusLabel, tone: tone, icon: icon),
                  if (!upcoming && s.menteeAttended == true)
                    const StatusPill(
                      label: 'You attended',
                      tone: PillTone.success,
                      icon: Icons.how_to_reg_outlined,
                    ),
                  if (!upcoming && s.menteeAttended == false)
                    const StatusPill(
                      label: "You didn't attend",
                      tone: PillTone.warning,
                      icon: Icons.person_off_outlined,
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(s.title, style: theme.textTheme.titleMedium),
              const SizedBox(height: AppSpacing.xxs),
              _Meta(icon: Icons.event_outlined, text: when),
              if (s.mentorName != null)
                _Meta(icon: Icons.person_outline_rounded, text: 'With ${s.mentorName}'),
              if (s.venue.isNotEmpty) _Meta(icon: Icons.place_outlined, text: s.venue),
              if (upcoming && s.meetingLink.isNotEmpty)
                const _Meta(icon: Icons.videocam_outlined, text: 'Online meeting'),
              if (upcoming && (widget.onJoin != null || widget.onRemind != null)) ...[
                const SizedBox(height: AppSpacing.md),
                Wrap(
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.sm,
                  children: [
                    if (widget.onJoin != null && s.meetingLink.isNotEmpty)
                      FilledButton.icon(
                        onPressed: () => widget.onJoin!(s.meetingLink),
                        icon: const Icon(Icons.video_call_outlined),
                        label: const Text('Join'),
                      ),
                    if (widget.onRemind != null)
                      OutlinedButton.icon(
                        onPressed: widget.onRemind,
                        icon: const Icon(Icons.alarm_add_outlined),
                        label: const Text('Add reminder'),
                      ),
                  ],
                ),
              ],
              if (widget.onAnswer != null && (s.needsAttendanceAnswer || _changing)) ...[
                const SizedBox(height: AppSpacing.md),
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: scheme.tertiaryContainer,
                    borderRadius: BorderRadius.circular(AppRadius.md),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Semantics(
                        header: true,
                        child: Text(
                          'Did you attend?',
                          style: theme.textTheme.titleSmall
                              ?.copyWith(color: scheme.onTertiaryContainer),
                        ),
                      ),
                      Text(
                        'Letting us know helps your mentor and the programme team.',
                        style: theme.textTheme.bodySmall
                            ?.copyWith(color: scheme.onTertiaryContainer),
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      Wrap(
                        spacing: AppSpacing.sm,
                        runSpacing: AppSpacing.sm,
                        children: [
                          FilledButton.icon(
                            onPressed: widget.saving
                                ? null
                                : () {
                                    setState(() => _changing = false);
                                    widget.onAnswer!(true);
                                  },
                            icon: const Icon(Icons.check_rounded),
                            label: const Text('Yes, I attended'),
                          ),
                          OutlinedButton.icon(
                            onPressed: widget.saving
                                ? null
                                : () {
                                    setState(() => _changing = false);
                                    widget.onAnswer!(false);
                                  },
                            icon: const Icon(Icons.close_rounded),
                            label: const Text('No'),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ] else if (widget.onAnswer != null && s.canChangeAttendance)
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton(
                    onPressed: () => setState(() => _changing = true),
                    child: const Text('Change my answer'),
                  ),
                ),
              if (hasDetails) ...[
                const SizedBox(height: AppSpacing.xs),
                TextButton.icon(
                  onPressed: () => setState(() => _expanded = !_expanded),
                  icon: Icon(_expanded ? Icons.expand_less : Icons.expand_more),
                  label: Text(_expanded ? 'Hide details' : 'Show agenda and notes'),
                ),
                if (_expanded) ...[
                  if (agenda.isNotEmpty) _DetailBlock(title: 'Agenda', body: agenda),
                  if (notes.isNotEmpty) _DetailBlock(title: 'Session notes', body: notes),
                  if (actions.isNotEmpty) _DetailBlock(title: 'Agreed actions', body: actions),
                ],
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _Meta extends StatelessWidget {
  const _Meta({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = theme.colorScheme.onSurfaceVariant;
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.xs),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ExcludeSemantics(child: Icon(icon, size: 18, color: color)),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(text, style: theme.textTheme.bodyMedium?.copyWith(color: color)),
          ),
        ],
      ),
    );
  }
}

class _DetailBlock extends StatelessWidget {
  const _DetailBlock({required this.title, required this.body});

  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.sm),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: theme.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.xs),
          Text(body, style: theme.textTheme.bodyMedium),
        ],
      ),
    );
  }
}
