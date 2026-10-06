import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/appointment_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/appointments_api.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'appointments_screen.dart';

/// Full detail of one appointment, with Join / Accept / Decline / Cancel.
class AppointmentDetailScreen extends StatefulWidget {
  const AppointmentDetailScreen({super.key, required this.appointmentId, this.initial});

  final int appointmentId;

  /// Optional list row shown while the fresh copy loads.
  final AppointmentInfo? initial;

  @override
  State<AppointmentDetailScreen> createState() => _AppointmentDetailScreenState();
}

class _AppointmentDetailScreenState extends State<AppointmentDetailScreen> {
  AppointmentInfo? _appointment;
  bool _loading = true;
  bool _busy = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _appointment = widget.initial;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    if (!await SyncService.instance.isOnline()) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = AppException.offline;
      });
      return;
    }
    try {
      final fresh = await AppointmentsApi.instance.show(widget.appointmentId);
      if (!mounted) return;
      setState(() {
        _appointment = fresh;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error;
      });
      if (_appointment != null) showErrorSnackBar(context, error, onRetry: _load);
    }
  }

  Future<void> _run(Future<AppointmentInfo> Function() action, String success) async {
    if (_busy) return;
    if (!await SyncService.instance.isOnline()) {
      if (mounted) showAppSnackBar(context, "You're offline. Connect to the internet and try again.");
      return;
    }
    setState(() => _busy = true);
    try {
      final updated = await action();
      if (!mounted) return;
      setState(() {
        _appointment = updated;
        _busy = false;
      });
      showAppSnackBar(context, success);
    } catch (error) {
      if (!mounted) return;
      setState(() => _busy = false);
      showErrorSnackBar(context, error);
      // The appointment may have changed underneath us (e.g. instructor
      // already responded); refresh so the buttons match the server.
      if (AppException.from(error).kind == AppErrorKind.validation) _load();
    }
  }

  Future<void> _accept() async {
    final a = _appointment!;
    final ok = await confirmDialog(
      context,
      title: 'Accept the new time?',
      message: formatAppointmentRange(a.proposedStartsAt, a.proposedEndsAt),
      confirmLabel: 'Accept',
    );
    if (!ok || !mounted) return;
    await _run(
      () => AppointmentsApi.instance.acceptProposal(a.id!),
      'You accepted the new time. Your appointment is confirmed.',
    );
  }

  Future<void> _decline() async {
    final ok = await confirmDialog(
      context,
      title: 'Decline the proposed time?',
      message: 'The request will be closed. You can book a different time afterwards.',
      confirmLabel: 'Decline',
      destructive: true,
    );
    if (!ok || !mounted) return;
    await _run(
      () => AppointmentsApi.instance.declineProposal(_appointment!.id!),
      'You declined the proposed time.',
    );
  }

  Future<void> _cancel() async {
    final reason = await showDialog<String>(
      context: context,
      builder: (_) => const _CancelDialog(),
    );
    if (reason == null || !mounted) return;
    await _run(
      () => AppointmentsApi.instance.cancel(_appointment!.id!, reason: reason),
      'Appointment cancelled.',
    );
  }

  Future<void> _join() async {
    final uri = Uri.tryParse(_appointment?.meetingUrl ?? '');
    if (uri == null) return;
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) showAppSnackBar(context, "Couldn't open the meeting link.", error: true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Appointment')),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _appointment == null) return const LoadingSkeleton(itemHeight: 96);
    if (_appointment == null) {
      return ErrorState(
        error: _error ?? AppException.offline,
        title: "This appointment couldn't be loaded",
        onRetry: _load,
      );
    }

    final a = _appointment!;
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final (tone, icon) = appointmentStatusTone(a.status);
    final hint = backendTimeHint(a.raw['starts_at']?.toString(), timezone: a.timezone);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: AppSpacing.listPadding,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(a.topic, style: theme.textTheme.titleLarge),
                  const SizedBox(height: AppSpacing.sm),
                  Wrap(
                    spacing: AppSpacing.xs,
                    runSpacing: AppSpacing.xs,
                    children: [
                      StatusPill(label: a.statusLabel, tone: tone, icon: icon),
                      InfoChip(
                        label: a.modeLabel,
                        icon: a.isOnline ? Icons.videocam_outlined : Icons.place_outlined,
                      ),
                    ],
                  ),
                  const Divider(height: AppSpacing.xl),
                  _MetaRow(label: 'When', value: formatAppointmentRange(a.startsAt, a.endsAt)),
                  if (hint != null) _MetaRow(label: '', value: '($hint)'),
                  _MetaRow(
                    label: 'Duration',
                    value: a.durationMinutes > 0 ? '${a.durationMinutes} minutes' : null,
                  ),
                  _MetaRow(label: 'Instructor', value: a.instructor?.name),
                  _MetaRow(label: 'Course', value: a.course?.title),
                  if (a.location != null) _MetaRow(label: 'Location', value: a.location),
                  if (a.meetingUrl != null) _MetaRow(label: 'Meeting link', value: a.meetingUrl),
                  if (a.details != null) ...[
                    const SizedBox(height: AppSpacing.md),
                    Text('Details', style: theme.textTheme.titleSmall),
                    const SizedBox(height: AppSpacing.xs),
                    SelectableText(a.details!, style: theme.textTheme.bodyLarge),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          ..._statusSection(context, a),
          if (_busy) ...[
            const SizedBox(height: AppSpacing.md),
            const LinearProgressIndicator(),
          ],
          const SizedBox(height: AppSpacing.xl),
          Text(
            'Times are shown in your device\'s time zone.',
            style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: AppSpacing.xxl),
        ],
      ),
    );
  }

  List<Widget> _statusSection(BuildContext context, AppointmentInfo a) {
    final widgets = <Widget>[];

    if (a.isProposal) {
      final canAccept = a.canAcceptProposal();
      widgets.add(NoticeCard(
        icon: Icons.schedule_rounded,
        tone: PillTone.warning,
        title: 'New time proposed',
        message: [
          formatAppointmentRange(a.proposedStartsAt, a.proposedEndsAt),
          if (a.proposalNote != null) '"${a.proposalNote}"',
          if (!canAccept) 'This proposed time has passed. You can decline it and book again.',
        ].join('\n'),
        actions: [
          if (a.canRespondToProposal && canAccept)
            FilledButton.icon(
              onPressed: _busy ? null : _accept,
              icon: const Icon(Icons.check_rounded),
              label: const Text('Accept new time'),
            ),
          if (a.canRespondToProposal)
            OutlinedButton(
              onPressed: _busy ? null : _decline,
              child: const Text('Decline'),
            ),
        ],
      ));
    } else if (a.status == AppointmentStatus.pending) {
      widgets.add(const NoticeCard(
        icon: Icons.hourglass_top_rounded,
        message: 'Waiting for the instructor to respond. You will be notified when they do.',
      ));
    } else if (a.isApproved) {
      widgets.add(NoticeCard(
        icon: Icons.check_circle_outline,
        tone: PillTone.success,
        message: a.isOnline
            ? (a.meetingUrl != null
                ? 'Confirmed. Join the meeting at the scheduled time.'
                : 'Confirmed. The instructor will share the meeting link.')
            : 'Confirmed. Meet your instructor${a.location != null ? ' at ${a.location}' : ''}.',
        actions: [
          if (a.canJoin)
            FilledButton.icon(
              onPressed: _join,
              icon: const Icon(Icons.videocam_rounded),
              label: const Text('Join meeting'),
            ),
        ],
      ));
    } else if (a.decisionReason != null &&
        (a.status == AppointmentStatus.declined || a.status == AppointmentStatus.cancelled)) {
      widgets.add(NoticeCard(
        icon: Icons.info_outline,
        tone: PillTone.neutral,
        title: a.status == AppointmentStatus.declined
            ? 'Declined'
            : (a.cancelledByMe ? 'You cancelled this appointment' : 'Cancelled'),
        message: a.decisionReason!,
      ));
    }

    if (a.canCancel) {
      widgets.add(const SizedBox(height: AppSpacing.md));
      widgets.add(Align(
        alignment: Alignment.centerLeft,
        child: TextButton.icon(
          style: TextButton.styleFrom(foregroundColor: Theme.of(context).colorScheme.error),
          onPressed: _busy ? null : _cancel,
          icon: const Icon(Icons.event_busy_outlined),
          label: Text(a.isApproved ? 'Cancel appointment' : 'Cancel request'),
        ),
      ));
    } else if (a.isApproved) {
      widgets.add(const SizedBox(height: AppSpacing.sm));
      widgets.add(Text(
        'Approved appointments can only be cancelled up to 2 hours before they start.',
        style: Theme.of(context).textTheme.bodySmall,
      ));
    }

    return widgets;
  }
}

/// Asks for confirmation and an optional reason. Pops the reason ('' when
/// none) or null when dismissed.
class _CancelDialog extends StatefulWidget {
  const _CancelDialog();

  @override
  State<_CancelDialog> createState() => _CancelDialogState();
}

class _CancelDialogState extends State<_CancelDialog> {
  final _reason = TextEditingController();

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return AlertDialog(
      title: const Text('Cancel this appointment?'),
      content: TextField(
        controller: _reason,
        maxLines: 3,
        maxLength: 1000,
        textCapitalization: TextCapitalization.sentences,
        decoration: const InputDecoration(
          labelText: 'Reason (optional)',
          alignLabelWithHint: true,
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Keep it'),
        ),
        FilledButton(
          style: FilledButton.styleFrom(
            backgroundColor: scheme.error,
            foregroundColor: scheme.onError,
          ),
          onPressed: () => Navigator.pop(context, _reason.text.trim()),
          child: const Text('Cancel appointment'),
        ),
      ],
    );
  }
}

class _MetaRow extends StatelessWidget {
  const _MetaRow({required this.label, required this.value});

  final String label;
  final String? value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.xs),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
          ),
          Expanded(child: SelectableText(value ?? '—', style: theme.textTheme.bodyMedium)),
        ],
      ),
    );
  }
}
