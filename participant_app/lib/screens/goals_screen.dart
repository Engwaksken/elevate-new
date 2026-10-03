import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/goal_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';

/// Loader signature so tests can inject a fake without touching the network.
typedef GoalsLoader = Future<Map<String, dynamic>> Function();

/// Personal goals with progress tracking (GET/POST/PUT/DELETE /goals).
class GoalsScreen extends StatefulWidget {
  const GoalsScreen({super.key, this.loadGoals});

  final GoalsLoader? loadGoals;

  @override
  State<GoalsScreen> createState() => _GoalsScreenState();
}

class _GoalsScreenState extends State<GoalsScreen> {
  List<GoalInfo>? _goals;
  GoalSummary _summary = const GoalSummary(total: 0, inProgress: 0, completed: 0, averageProgress: 0);
  bool _loading = true;
  bool _online = true;
  Object? _error;

  Future<Map<String, dynamic>> _fetch() =>
      (widget.loadGoals ?? ApiService.instance.goals)();

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
    if (mounted) setState(() => _online = online);
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
      final data = await _fetch();
      if (!mounted) return;
      setState(() {
        _goals = _parseGoals(data['goals']);
        _summary = GoalSummary.fromJson(
          data['summary'] is Map ? Map<String, dynamic>.from(data['summary'] as Map) : null,
        );
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

  List<GoalInfo> _parseGoals(dynamic raw) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((item) => GoalInfo(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<bool> _requireOnline() async {
    final online = await SyncService.instance.isOnline();
    if (mounted) setState(() => _online = online);
    if (!online && mounted) {
      showAppSnackBar(context, "You're offline. Connect to the internet to update your goals.");
    }
    return online;
  }

  Future<void> _addGoal() async {
    if (!await _requireOnline() || !mounted) return;
    final fields = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _GoalFormSheet(),
    );
    if (fields == null || !mounted) return;
    try {
      await ApiService.instance.createGoal(fields);
      if (mounted) showAppSnackBar(context, 'Goal added. Keep going!');
      await _load();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  Future<void> _updateProgress(GoalInfo goal) async {
    if (!await _requireOnline() || !mounted || goal.id == null) return;
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _ProgressSheet(goal: goal),
    );
    if (result == null || !mounted) return;
    try {
      await ApiService.instance.updateGoalProgress(goal.id!, result);
      if (mounted) showAppSnackBar(context, 'Progress saved.');
      await _load();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  Future<void> _delete(GoalInfo goal) async {
    if (!await _requireOnline() || !mounted || goal.id == null) return;
    final ok = await confirmDialog(
      context,
      title: 'Remove this goal?',
      message: '“${goal.title}” and its progress will be removed.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok || !mounted) return;
    try {
      await ApiService.instance.deleteGoal(goal.id!);
      if (mounted) showAppSnackBar(context, 'Goal removed.');
      await _load();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My goals')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addGoal,
        icon: const Icon(Icons.add),
        label: const Text('Add goal'),
      ),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _goals == null) return const LoadingSkeleton(itemHeight: 96);
    if (_goals == null) {
      return ErrorState(
        error: _error ?? AppException.offline,
        title: "Your goals couldn't be loaded",
        onRetry: _load,
      );
    }

    final goals = _goals!;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          const SizedBox(height: AppSpacing.sm),
          if (!_online) ...[
            const NoticeCard(
              icon: Icons.cloud_off_outlined,
              tone: PillTone.warning,
              message: "You're offline. Reconnect to add goals or update progress.",
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          _SummaryCard(summary: _summary),
          if (goals.isEmpty) ...[
            const SizedBox(height: AppSpacing.lg),
            const Card(
              child: Padding(
                padding: EdgeInsets.all(AppSpacing.lg),
                child: Column(
                  children: [
                    Icon(Icons.flag_outlined, size: 42),
                    SizedBox(height: AppSpacing.md),
                    Text('No goals yet', style: TextStyle(fontWeight: FontWeight.w600)),
                    SizedBox(height: AppSpacing.xs),
                    Text(
                      'Set your first goal to start tracking your progress.',
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ),
          ] else ...[
            const SectionHeader(title: 'Your goals'),
            for (final goal in goals)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.md),
                child: _GoalCard(
                  goal: goal,
                  onProgress: () => _updateProgress(goal),
                  onDelete: () => _delete(goal),
                ),
              ),
          ],
          const SizedBox(height: AppSpacing.xxl),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.summary});

  final GoalSummary summary;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ExcludeSemantics(
                  child: ProgressRing(
                    value: summary.averageProgress / 100,
                    size: 64,
                    center: Text(
                      '${summary.averageProgress.round()}%',
                      style: theme.textTheme.labelLarge,
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Goal progress', style: theme.textTheme.titleMedium),
                      Text(
                        summary.total == 0
                            ? 'Add a goal to begin.'
                            : '${summary.completed} of ${summary.total} completed',
                        style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (summary.total > 0) ...[
              const SizedBox(height: AppSpacing.md),
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.xs,
                children: [
                  StatusPill(
                    label: '${summary.inProgress} in progress',
                    tone: PillTone.accent,
                    icon: Icons.directions_run_outlined,
                  ),
                  StatusPill(
                    label: '${summary.completed} completed',
                    tone: PillTone.success,
                    icon: Icons.check_circle_outline,
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _GoalCard extends StatelessWidget {
  const _GoalCard({required this.goal, required this.onProgress, required this.onDelete});

  final GoalInfo goal;
  final VoidCallback onProgress;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final tone = goal.isCompleted
        ? PillTone.success
        : goal.status == 'cancelled'
            ? PillTone.neutral
            : PillTone.brand;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: Text(goal.title, style: theme.textTheme.titleMedium)),
                StatusPill(label: goal.statusLabel, tone: tone),
              ],
            ),
            if (goal.description != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                goal.description!,
                style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ],
            if (goal.mentorComment != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: scheme.primaryContainer,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.comment_outlined, size: 18, color: scheme.onPrimaryContainer),
                        const SizedBox(width: AppSpacing.xs),
                        Text(
                          'Mentor feedback',
                          style: theme.textTheme.labelLarge?.copyWith(color: scheme.onPrimaryContainer),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      goal.mentorComment!,
                      style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onPrimaryContainer),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.xs,
              runSpacing: AppSpacing.xs,
              children: [
                InfoChip(label: goal.categoryLabel, icon: Icons.category_outlined),
                if (goal.priority == GoalPriority.high)
                  InfoChip(label: goal.priorityLabel, icon: Icons.priority_high),
                if (goal.valueLabel != null)
                  InfoChip(label: goal.valueLabel!, icon: Icons.speed_outlined),
                if (goal.dueLabel != null)
                  InfoChip(label: goal.dueLabel!, icon: Icons.event_outlined),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            ProgressBar(
              value: goal.progressFraction,
              semanticsLabel: '${goal.progressPercent.round()} percent complete',
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              '${goal.progressPercent.round()}% complete',
              style: theme.textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                FilledButton.tonalIcon(
                  onPressed: onProgress,
                  icon: const Icon(Icons.trending_up),
                  label: const Text('Update progress'),
                ),
                const Spacer(),
                IconButton(
                  tooltip: 'Remove goal',
                  onPressed: onDelete,
                  icon: Icon(Icons.delete_outline, color: scheme.error),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Bottom sheet to update progress: current value, percentage or status.
class _ProgressSheet extends StatefulWidget {
  const _ProgressSheet({required this.goal});

  final GoalInfo goal;

  @override
  State<_ProgressSheet> createState() => _ProgressSheetState();
}

class _ProgressSheetState extends State<_ProgressSheet> {
  late final TextEditingController _value = TextEditingController(
    text: widget.goal.currentValue == null ? '' : formatNumber(widget.goal.currentValue),
  );
  late final TextEditingController _percent = TextEditingController(
    text: widget.goal.progressPercent.round().toString(),
  );
  late String _status = widget.goal.status;

  @override
  void dispose() {
    _value.dispose();
    _percent.dispose();
    super.dispose();
  }

  void _submit() {
    final data = <String, dynamic>{'status': _status};
    final value = asDouble(_value.text);
    if (value != null) data['current_value'] = value;
    final percent = asDouble(_percent.text);
    if (percent != null) data['progress_percent'] = percent.clamp(0, 100);
    Navigator.pop(context, data);
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
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Update progress', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.xs),
          Text(widget.goal.title, style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: AppSpacing.lg),
          if (widget.goal.targetValue != null)
            TextField(
              controller: _value,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(
                labelText: 'Current value${widget.goal.unit == null ? '' : ' (${widget.goal.unit})'}',
              ),
            ),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: _percent,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(labelText: 'Progress %', suffixText: '%'),
          ),
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<String>(
            initialValue: _status,
            decoration: const InputDecoration(labelText: 'Status'),
            items: const [
              DropdownMenuItem(value: 'not_started', child: Text('Not started')),
              DropdownMenuItem(value: 'in_progress', child: Text('In progress')),
              DropdownMenuItem(value: 'completed', child: Text('Completed')),
              DropdownMenuItem(value: 'cancelled', child: Text('Cancelled')),
            ],
            onChanged: (value) => setState(() => _status = value ?? _status),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancel'),
              ),
              const SizedBox(width: AppSpacing.sm),
              FilledButton(onPressed: _submit, child: const Text('Save progress')),
            ],
          ),
        ],
      ),
    );
  }
}

/// Bottom sheet to create a new goal.
class _GoalFormSheet extends StatefulWidget {
  const _GoalFormSheet();

  @override
  State<_GoalFormSheet> createState() => _GoalFormSheetState();
}

class _GoalFormSheetState extends State<_GoalFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _unit = TextEditingController();
  final _baseline = TextEditingController();
  final _target = TextEditingController();
  final _current = TextEditingController();
  String _category = 'career';
  String _priority = 'medium';
  DateTime? _startDate;
  DateTime? _targetDate;

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _unit.dispose();
    _baseline.dispose();
    _target.dispose();
    _current.dispose();
    super.dispose();
  }

  String? _dateText(DateTime? date) =>
      date == null ? null : formatDateTime(date.toIso8601String(), withTime: false);

  Future<void> _pickDate({required bool start}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: (start ? _startDate : _targetDate) ?? now,
      firstDate: DateTime(now.year - 2),
      lastDate: DateTime(now.year + 10),
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _startDate = picked;
      } else {
        _targetDate = picked;
      }
    });
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    final data = <String, dynamic>{
      'title': _title.text.trim(),
      'category': _category,
      'priority': _priority,
    };
    if (_description.text.trim().isNotEmpty) data['description'] = _description.text.trim();
    if (_unit.text.trim().isNotEmpty) data['unit'] = _unit.text.trim();
    final baseline = asDouble(_baseline.text);
    if (baseline != null) data['baseline_value'] = baseline;
    final target = asDouble(_target.text);
    if (target != null) data['target_value'] = target;
    final current = asDouble(_current.text);
    if (current != null) data['current_value'] = current;
    if (_startDate != null) {
      data['start_date'] = _startDate!.toIso8601String().substring(0, 10);
    }
    if (_targetDate != null) {
      data['target_date'] = _targetDate!.toIso8601String().substring(0, 10);
    }
    Navigator.pop(context, data);
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
              Text('Add a goal', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: AppSpacing.lg),
              TextFormField(
                controller: _title,
                textCapitalization: TextCapitalization.sentences,
                maxLength: 190,
                decoration: const InputDecoration(labelText: 'Goal title *', counterText: ''),
                validator: (value) =>
                    (value?.trim().isEmpty ?? true) ? 'Give your goal a title' : null,
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _description,
                maxLines: 3,
                maxLength: 4000,
                decoration: const InputDecoration(labelText: 'Description', counterText: ''),
              ),
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<String>(
                initialValue: _category,
                decoration: const InputDecoration(labelText: 'Category'),
                items: const [
                  DropdownMenuItem(value: 'career', child: Text('Career')),
                  DropdownMenuItem(value: 'learning', child: Text('Learning')),
                  DropdownMenuItem(value: 'personal', child: Text('Personal')),
                  DropdownMenuItem(value: 'mentorship', child: Text('Mentorship')),
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
                  DropdownMenuItem(value: 'medium', child: Text('Medium')),
                  DropdownMenuItem(value: 'high', child: Text('High')),
                ],
                onChanged: (value) => setState(() => _priority = value ?? _priority),
              ),
              const SizedBox(height: AppSpacing.md),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _baseline,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(labelText: 'Baseline'),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: TextFormField(
                      controller: _target,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(labelText: 'Target'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _current,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(labelText: 'Current'),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: TextFormField(
                      controller: _unit,
                      maxLength: 40,
                      decoration: const InputDecoration(labelText: 'Unit', counterText: ''),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pickDate(start: true),
                      icon: const Icon(Icons.event_outlined),
                      label: Text(_dateText(_startDate) ?? 'Start date'),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pickDate(start: false),
                      icon: const Icon(Icons.flag_outlined),
                      label: Text(_dateText(_targetDate) ?? 'Target date'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  FilledButton(onPressed: _submit, child: const Text('Add goal')),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
