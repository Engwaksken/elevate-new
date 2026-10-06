import 'package:flutter/material.dart';

import '../core/appointment_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/appointments_api.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'appointment_booking_screen.dart';
import 'appointment_detail_screen.dart';

/// Status -> visual tone + icon (the label comes from the API).
(PillTone, IconData) appointmentStatusTone(String status) => switch (status) {
      AppointmentStatus.approved => (PillTone.success, Icons.check_circle_outline),
      AppointmentStatus.proposed => (PillTone.warning, Icons.schedule_rounded),
      AppointmentStatus.declined => (PillTone.danger, Icons.block_rounded),
      AppointmentStatus.cancelled => (PillTone.neutral, Icons.cancel_outlined),
      AppointmentStatus.completed => (PillTone.brand, Icons.task_alt_rounded),
      _ => (PillTone.accent, Icons.hourglass_top_rounded),
    };

/// Instructor appointments: upcoming & awaiting / past, with booking.
/// Online-only (appointments are not cached for offline use).
class AppointmentsScreen extends StatefulWidget {
  const AppointmentsScreen({super.key});

  @override
  State<AppointmentsScreen> createState() => _AppointmentsScreenState();
}

class _AppointmentsScreenState extends State<AppointmentsScreen> {
  AppointmentList? _data;
  bool _loading = true;
  Object? _error;

  @override
  void initState() {
    super.initState();
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
      if (_data != null) {
        showAppSnackBar(context, "You're offline. Showing the last loaded appointments.");
      }
      return;
    }
    try {
      final data = await AppointmentsApi.instance.list();
      if (!mounted) return;
      setState(() {
        _data = data;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error;
      });
      if (_data != null) showErrorSnackBar(context, error, onRetry: _load);
    }
  }

  Future<void> _book() async {
    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        showAppSnackBar(context, "You're offline. Connect to the internet to book an appointment.");
      }
      return;
    }
    if (!mounted) return;
    final created = await Navigator.of(context).push<AppointmentInfo>(
      MaterialPageRoute(
        builder: (_) => AppointmentBookingScreen(existing: _data?.upcoming ?? const []),
      ),
    );
    if (created == null || !mounted) return;
    showAppSnackBar(context, 'Request sent. You will be notified when the instructor responds.');
    await _load();
  }

  Future<void> _open(AppointmentInfo appointment) async {
    final id = appointment.id;
    if (id == null) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => AppointmentDetailScreen(appointmentId: id, initial: appointment),
      ),
    );
    // The detail screen may have accepted, declined or cancelled it.
    if (mounted) await _load();
  }

  @override
  Widget build(BuildContext context) {
    final proposals = _data?.proposals ?? 0;
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Appointments'),
          bottom: TabBar(
            tabs: [
              Tab(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Flexible(
                      child: Text('Upcoming & awaiting', overflow: TextOverflow.ellipsis),
                    ),
                    if (proposals > 0) ...[
                      const SizedBox(width: AppSpacing.xs),
                      Badge(label: Text('$proposals')),
                    ],
                  ],
                ),
              ),
              const Tab(text: 'Past'),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _book,
          icon: const Icon(Icons.add),
          label: const Text('Book'),
          tooltip: 'Book an appointment',
        ),
        body: SafeArea(
          top: false,
          child: TabBarView(
            children: [
              _tab(
                _data?.upcoming,
                emptyIcon: Icons.event_available_outlined,
                emptyTitle: 'No upcoming appointments',
                emptyMessage: 'Book time with one of your course instructors. '
                    'Requests waiting for a reply show here too.',
                emptyActionLabel: 'Book an appointment',
                onEmptyAction: _book,
              ),
              _tab(
                _data?.past,
                emptyIcon: Icons.history_rounded,
                emptyTitle: 'No past appointments',
                emptyMessage: 'Completed, declined and cancelled appointments appear here.',
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _tab(
    List<AppointmentInfo>? items, {
    required IconData emptyIcon,
    required String emptyTitle,
    required String emptyMessage,
    String? emptyActionLabel,
    VoidCallback? onEmptyAction,
  }) {
    if (_loading && _data == null) return const LoadingSkeleton(itemHeight: 96);
    if (_data == null) {
      return ErrorState(
        error: _error ?? AppException.offline,
        title: "Your appointments couldn't be loaded",
        onRetry: _load,
      );
    }

    final list = items ?? const <AppointmentInfo>[];
    return RefreshIndicator(
      onRefresh: _load,
      child: list.isEmpty
          ? LayoutBuilder(
              builder: (context, constraints) => SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppSpacing.xl),
                child: ConstrainedBox(
                  constraints: BoxConstraints(
                    minHeight: constraints.maxHeight.isFinite
                        ? constraints.maxHeight - AppSpacing.xl * 2
                        : 0,
                  ),
                  child: EmptyState(
                    icon: emptyIcon,
                    title: emptyTitle,
                    message: emptyMessage,
                    actionLabel: emptyActionLabel,
                    onAction: onEmptyAction,
                  ),
                ),
              ),
            )
          : ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: AppSpacing.listPadding.copyWith(bottom: 96),
              itemCount: list.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, index) => AppointmentCard(
                appointment: list[index],
                onTap: () => _open(list[index]),
              ),
            ),
    );
  }
}

class AppointmentCard extends StatelessWidget {
  const AppointmentCard({super.key, required this.appointment, required this.onTap});

  final AppointmentInfo appointment;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final a = appointment;
    final (tone, icon) = appointmentStatusTone(a.status);
    final who = [a.instructor?.name, a.course?.title].whereType<String>().join(' · ');

    return Card(
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          AppSpacing.md,
          AppSpacing.md,
          AppSpacing.md,
        ),
        title: Text(a.topic, maxLines: 2, overflow: TextOverflow.ellipsis),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xs),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                formatAppointmentRange(a.startsAt, a.endsAt),
                style: theme.textTheme.bodyMedium,
              ),
              if (who.isNotEmpty)
                Text(
                  who,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
              const SizedBox(height: AppSpacing.xs),
              Wrap(
                spacing: AppSpacing.xs,
                runSpacing: AppSpacing.xs,
                children: [
                  StatusPill(label: a.statusLabel, tone: tone, icon: icon),
                  InfoChip(
                    label: a.modeLabel,
                    icon: a.isOnline ? Icons.videocam_outlined : Icons.place_outlined,
                  ),
                  if (a.durationMinutes > 0)
                    InfoChip(label: '${a.durationMinutes} min', icon: Icons.timer_outlined),
                ],
              ),
              if (a.isProposal && a.proposedStartsAt != null) ...[
                const SizedBox(height: AppSpacing.xs),
                Text(
                  'Proposed: ${formatAppointmentRange(a.proposedStartsAt, a.proposedEndsAt)}',
                  style: theme.textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w500),
                ),
              ],
            ],
          ),
        ),
        trailing: const Icon(Icons.chevron_right_rounded),
      ),
    );
  }
}
