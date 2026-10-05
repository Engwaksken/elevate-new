import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/it_support_ticket_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';
import '../widgets/state_views.dart';
import 'it_support_screen.dart';

/// Full detail of a single IT support request, loaded from
/// GET /api/v1/it-support/tickets/{id}.
class ItSupportTicketDetailScreen extends StatefulWidget {
  const ItSupportTicketDetailScreen({super.key, required this.ticketId});

  final int ticketId;

  @override
  State<ItSupportTicketDetailScreen> createState() =>
      _ItSupportTicketDetailScreenState();
}

class _ItSupportTicketDetailScreenState
    extends State<ItSupportTicketDetailScreen> {
  ItSupportTicketInfo? _ticket;
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
    final online = await SyncService.instance.isOnline();
    if (!online) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = AppException.offline;
        });
      }
      return;
    }
    try {
      final data = await ApiService.instance.itSupportTicket(widget.ticketId);
      if (!mounted) return;
      setState(() {
        _ticket = _parse(data);
        _loading = false;
        _error = null;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error;
      });
    }
  }

  ItSupportTicketInfo? _parse(Map<String, dynamic> data) {
    final raw = data['data'];
    if (raw is Map) return ItSupportTicketInfo(Map<String, dynamic>.from(raw));
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Request detail')),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _ticket == null) return const LoadingSkeleton(itemHeight: 96);
    if (_ticket == null) {
      return ErrorState(
        error: _error ?? AppException.offline,
        title: "This request couldn't be loaded",
        onRetry: _load,
      );
    }

    final ticket = _ticket!;
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final (statusTone, statusIcon) = ticketStatusTone(ticket.status);
    final (priorityTone, priorityIcon) = ticketPriorityTone(ticket.priority);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(ticket.subject, style: theme.textTheme.titleLarge),
                  const SizedBox(height: AppSpacing.sm),
                  Wrap(
                    spacing: AppSpacing.xs,
                    runSpacing: AppSpacing.xs,
                    children: [
                      StatusPill(label: ticket.statusLabel, tone: statusTone, icon: statusIcon),
                      StatusPill(label: ticket.priorityLabel, tone: priorityTone, icon: priorityIcon),
                      InfoChip(label: ticket.categoryLabel, icon: Icons.category_outlined),
                    ],
                  ),
                  const Divider(height: AppSpacing.xl),
                  if (ticket.description != null) ...[
                    Text(ticket.description!, style: theme.textTheme.bodyLarge),
                    const SizedBox(height: AppSpacing.lg),
                  ],
                  _MetaRow(label: 'Submitted', value: formatDateTime(ticket.raw['created_at'])),
                  if (ticket.raw['status_updated_at'] != null)
                    _MetaRow(
                      label: 'Status updated',
                      value: formatDateTime(ticket.raw['status_updated_at']),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          NoticeCard(
            icon: Icons.info_outline,
            tone: ticket.isResolved ? PillTone.success : PillTone.accent,
            message: ticket.isResolved
                ? 'This request has been resolved. If you still need help, please submit a new request.'
                : 'Our IT team will review your request. You will be notified when there is an update.',
          ),
          const SizedBox(height: AppSpacing.xl),
          Text(
            'Need help? Email support@elevateher360.org',
            style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: AppSpacing.xxl),
        ],
      ),
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
            width: 120,
            child: Text(
              label,
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
          ),
          Expanded(
            child: Text(value ?? '—', style: theme.textTheme.bodyMedium),
          ),
        ],
      ),
    );
  }
}
