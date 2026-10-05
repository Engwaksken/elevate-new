import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/it_support_ticket_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';
import 'it_support_ticket_detail_screen.dart';

/// Status -> (label is on the model, here only the visual tone + icon).
(PillTone, IconData) ticketStatusTone(String status) => switch (status) {
      'in_progress' => (PillTone.brand, Icons.build_outlined),
      'awaiting_requester' => (PillTone.warning, Icons.reply_outlined),
      'resolved' => (PillTone.success, Icons.check_circle_outline),
      _ => (PillTone.accent, Icons.fiber_new_outlined),
    };

(PillTone, IconData) ticketPriorityTone(String priority) => switch (priority) {
      'low' => (PillTone.neutral, Icons.arrow_downward_rounded),
      'high' => (PillTone.warning, Icons.arrow_upward_rounded),
      'urgent' => (PillTone.danger, Icons.priority_high_rounded),
      _ => (PillTone.accent, Icons.remove_rounded),
    };

/// IT support requests: list of the participant's tickets, with a
/// "New request" action and per-ticket detail.
class ItSupportScreen extends StatefulWidget {
  const ItSupportScreen({super.key});

  @override
  State<ItSupportScreen> createState() => _ItSupportScreenState();
}

class _ItSupportScreenState extends State<ItSupportScreen> {
  final List<ItSupportTicketInfo> _tickets = [];
  bool _loading = true;
  Object? _error;
  int _page = 1;
  bool _hasMore = false;
  bool _loadingMore = false;

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
      final data = await ApiService.instance.itSupportTickets(page: 1);
      if (!mounted) return;
      setState(() {
        _tickets
          ..clear()
          ..addAll(_parse(data));
        _page = 1;
        _hasMore = _nextPage(data) != null;
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

  Future<void> _loadMore() async {
    final next = _nextPageOf();
    if (next == null || _loadingMore) return;
    setState(() => _loadingMore = true);
    try {
      final data = await ApiService.instance.itSupportTickets(page: next);
      if (!mounted) return;
      setState(() {
        _tickets.addAll(_parse(data));
        _page = next;
        _hasMore = _nextPage(data) != null;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
      showAppSnackBar(context, "Couldn't load more requests.");
    }
  }

  int? _nextPageOf() {
    if (!_hasMore) return null;
    return _page + 1;
  }

  List<ItSupportTicketInfo> _parse(Map<String, dynamic> data) {
    final raw = data['data'];
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((item) => ItSupportTicketInfo(Map<String, dynamic>.from(item)))
        .toList();
  }

  /// The next page number from a paginated response, or null when done.
  int? _nextPage(Map<String, dynamic> data) {
    final meta = data['meta'];
    if (meta is! Map) return null;
    final current = asInt(meta['current_page']);
    final last = asInt(meta['last_page']);
    if (current == null || last == null) return null;
    return current < last ? current + 1 : null;
  }

  Future<void> _submit() async {
    if (!mounted) return;
    final online = await _requireOnline();
    if (!mounted || !online) return;

    final created = await showModalBottomSheet<ItSupportTicketInfo>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _SubmitTicketSheet(),
    );
    if (created == null || !mounted) return;
    showAppSnackBar(context, 'Request submitted. We will be in touch.');
    await _load();
  }

  Future<bool> _requireOnline() async {
    final online = await SyncService.instance.isOnline();
    if (!online && mounted) {
      showAppSnackBar(context, "You're offline. Connect to the internet to send a request.");
    }
    return online;
  }

  void _openDetail(ItSupportTicketInfo ticket) {
    final id = ticket.id;
    if (id == null) return;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ItSupportTicketDetailScreen(ticketId: id),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('IT support')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _submit,
        icon: const Icon(Icons.add),
        label: const Text('New request'),
      ),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _tickets.isEmpty) return const LoadingSkeleton(itemHeight: 88);
    if (_tickets.isEmpty && _error != null) {
      return ErrorState(
        error: _error!,
        title: "Your requests couldn't be loaded",
        onRetry: _load,
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: _tickets.isEmpty
          ? _empty(context)
          : ListView.separated(
              padding: AppSpacing.listPadding,
              itemCount: _tickets.length + (_hasMore || _loadingMore ? 1 : 0),
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
              itemBuilder: (context, index) {
                if (index >= _tickets.length) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
                    child: Center(
                      child: _loadingMore
                          ? const SizedBox.square(
                              dimension: 22,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : OutlinedButton(
                              onPressed: _loadMore,
                              child: const Text('Load more'),
                            ),
                    ),
                  );
                }
                return _TicketCard(
                  ticket: _tickets[index],
                  onTap: () => _openDetail(_tickets[index]),
                );
              },
            ),
    );
  }

  Widget _empty(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: ConstrainedBox(
          constraints: BoxConstraints(
            minHeight: constraints.maxHeight.isFinite
                ? constraints.maxHeight - AppSpacing.xl * 2
                : 0,
          ),
          child: const EmptyState(
            icon: Icons.support_agent_outlined,
            title: 'No support requests yet',
            message: 'Submit a request and track its progress here.',
          ),
        ),
      ),
    );
  }
}

class _TicketCard extends StatelessWidget {
  const _TicketCard({required this.ticket, required this.onTap});

  final ItSupportTicketInfo ticket;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final (statusTone, statusIcon) = ticketStatusTone(ticket.status);
    final (priorityTone, priorityIcon) = ticketPriorityTone(ticket.priority);
    final created = relativeTime(ticket.raw['created_at']);

    return Card(
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          AppSpacing.md,
          AppSpacing.md,
          AppSpacing.md,
        ),
        title: Text(ticket.subject, maxLines: 2, overflow: TextOverflow.ellipsis),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xs),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Wrap(
                spacing: AppSpacing.xs,
                runSpacing: AppSpacing.xs,
                children: [
                  StatusPill(label: ticket.statusLabel, tone: statusTone, icon: statusIcon),
                  StatusPill(label: ticket.priorityLabel, tone: priorityTone, icon: priorityIcon),
                  InfoChip(label: ticket.categoryLabel, icon: Icons.category_outlined),
                ],
              ),
              if (created.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.xs),
                Text(
                  created,
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
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

/// Bottom sheet to submit a new IT support request.
class _SubmitTicketSheet extends StatefulWidget {
  const _SubmitTicketSheet();

  @override
  State<_SubmitTicketSheet> createState() => _SubmitTicketSheetState();
}

class _SubmitTicketSheetState extends State<_SubmitTicketSheet> {
  final _formKey = GlobalKey<FormState>();
  final _subject = TextEditingController();
  final _description = TextEditingController();
  String _category = 'general';
  String _priority = 'normal';
  bool _submitting = false;

  @override
  void dispose() {
    _subject.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final data = await ApiService.instance.createItSupportTicket(
        subject: _subject.text,
        description: _description.text,
        category: _category,
        priority: _priority,
      );
      if (!mounted) return;
      final ticketData = data['data'];
      final ticket = ticketData is Map
          ? ItSupportTicketInfo(Map<String, dynamic>.from(ticketData))
          : null;
      Navigator.pop(context, ticket);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: AppSpacing.lg,
        right: AppSpacing.lg,
        top: AppSpacing.lg,
        bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('New IT support request', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: AppSpacing.xs),
              Text(
                'Tell us what you need help with.',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: AppSpacing.lg),
              TextFormField(
                controller: _subject,
                textCapitalization: TextCapitalization.sentences,
                maxLength: 255,
                decoration: const InputDecoration(labelText: 'Subject *'),
                validator: (value) =>
                    (value?.trim().isEmpty ?? true) ? 'Give your request a subject' : null,
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _description,
                maxLines: 5,
                maxLength: 4000,
                decoration: const InputDecoration(
                  labelText: 'Description *',
                  alignLabelWithHint: true,
                ),
                validator: (value) =>
                    (value?.trim().isEmpty ?? true) ? 'Describe the issue you are having' : null,
              ),
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<String>(
                initialValue: _category,
                decoration: const InputDecoration(labelText: 'Category'),
                items: const [
                  DropdownMenuItem(value: 'general', child: Text('General')),
                  DropdownMenuItem(value: 'access', child: Text('Access')),
                  DropdownMenuItem(value: 'learning', child: Text('Learning')),
                  DropdownMenuItem(value: 'procurement', child: Text('Procurement')),
                  DropdownMenuItem(value: 'other', child: Text('Other')),
                ],
                onChanged: (value) => setState(() => _category = value ?? _category),
              ),
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<String>(
                initialValue: _priority,
                decoration: const InputDecoration(labelText: 'Priority'),
                items: const [
                  DropdownMenuItem(value: 'low', child: Text('Low')),
                  DropdownMenuItem(value: 'normal', child: Text('Normal')),
                  DropdownMenuItem(value: 'high', child: Text('High')),
                  DropdownMenuItem(value: 'urgent', child: Text('Urgent')),
                ],
                onChanged: (value) => setState(() => _priority = value ?? _priority),
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: _submitting ? null : () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  FilledButton(
                    onPressed: _submitting ? null : _submit,
                    child: _submitting
                        ? const SizedBox.square(
                            dimension: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Submit request'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
