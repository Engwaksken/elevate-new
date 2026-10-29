import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../services/ai_mentor_client.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// Signature for the assistant call, injectable so widget tests can avoid
/// the network.
typedef AssistantSender = Future<Map<String, dynamic>> Function(
  String message,
  List<Map<String, String>> history,
);

class _ChatMessage {
  _ChatMessage({this.id, required this.role, required this.text, this.saved = false, List<Map<String, dynamic>>? actions})
      : actions = actions ?? [];

  factory _ChatMessage.fromJson(Map<String, dynamic> json) => _ChatMessage(
        id: (json['id'] as num?)?.toInt(),
        role: json['role'] == 'user' ? 'user' : 'assistant',
        text: (json['content'] ?? json['message'] ?? '').toString(),
        saved: json['saved'] == true,
        actions: [
          for (final action in (json['actions'] as List? ?? const []))
            if (action is Map) Map<String, dynamic>.from(action),
        ],
      );

  final int? id;
  final String role; // 'user' | 'assistant'
  final String text;
  bool saved;
  final List<Map<String, dynamic>> actions;
}

/// AI career mentor: chat grounded in her goals, saved answers, and suggested
/// steps she can approve onto her calendar ("My plan"). Mirrors the website.
class MentorshipAiScreen extends StatefulWidget {
  const MentorshipAiScreen({super.key, this.sendMessage, this.client, this.initialQuestion});

  /// Asked automatically once the chat has loaded (from the dashboard card).
  final String? initialQuestion;

  /// Test hook for the send call only (history, plan and saved stay empty).
  final AssistantSender? sendMessage;

  /// Full server client; defaults to the API.
  final AiMentorClient? client;

  @override
  State<MentorshipAiScreen> createState() => _MentorshipAiScreenState();
}

class _MentorshipAiScreenState extends State<MentorshipAiScreen> {
  late final AiMentorClient _client =
      widget.client ?? (widget.sendMessage != null ? _SendOnlyClient(widget.sendMessage!) : const ApiAiMentorClient());

  /// Bumped whenever steps change so "My plan" reloads.
  final _planVersion = ValueNotifier<int>(0);
  final _savedVersion = ValueNotifier<int>(0);

  @override
  void dispose() {
    _planVersion.dispose();
    _savedVersion.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('AI career mentor'),
          bottom: const TabBar(tabs: [
            Tab(icon: Icon(Icons.chat_bubble_outline), text: 'Chat'),
            Tab(icon: Icon(Icons.checklist), text: 'My plan'),
            Tab(icon: Icon(Icons.bookmark_outline), text: 'Saved'),
          ]),
        ),
        body: SafeArea(
          top: false,
          child: TabBarView(children: [
            _ChatTab(
              client: _client,
              skipHistory: widget.client == null && widget.sendMessage != null,
              initialQuestion: widget.initialQuestion,
              onStepsChanged: () => _planVersion.value++,
              onSavedChanged: () => _savedVersion.value++,
            ),
            _PlanTab(client: _client, version: _planVersion),
            _SavedTab(client: _client, version: _savedVersion),
          ]),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Chat
// ---------------------------------------------------------------------------

class _ChatTab extends StatefulWidget {
  const _ChatTab({required this.client, required this.skipHistory, required this.onStepsChanged, required this.onSavedChanged, this.initialQuestion});

  final AiMentorClient client;
  final bool skipHistory;
  final String? initialQuestion;
  final VoidCallback onStepsChanged;
  final VoidCallback onSavedChanged;

  @override
  State<_ChatTab> createState() => _ChatTabState();
}

class _ChatTabState extends State<_ChatTab> with AutomaticKeepAliveClientMixin {
  static const _suggestions = <String>[
    'How do I choose a career path?',
    'How should I prepare for an interview?',
    'What skills should I build next?',
    'How can I make the most of my mentor?',
  ];

  final _messages = <_ChatMessage>[];
  final _controller = TextEditingController();
  final _scroll = ScrollController();
  /// Which question/answer pairs are open; older ones stay collapsed to keep the chat short.
  final _openTurns = <int>{};
  bool _sending = false;
  bool _loading = true;
  bool _enabled = true;
  Object? _loadError;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    if (widget.skipHistory) {
      _loading = false;
      WidgetsBinding.instance.addPostFrameCallback((_) => _askInitialQuestion());
    } else {
      _loadHistory();
    }
  }

  bool _initialAsked = false;

  /// Sends the question typed on the dashboard card, once.
  void _askInitialQuestion() {
    final question = widget.initialQuestion?.trim();
    if (_initialAsked || question == null || question.isEmpty || !mounted) return;
    _initialAsked = true;
    _ask(question);
  }

  @override
  void dispose() {
    _controller.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _loadHistory() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final data = await widget.client.history();
      if (!mounted) return;
      setState(() {
        _enabled = data['enabled'] != false;
        _messages
          ..clear()
          ..addAll([
            for (final m in (data['messages'] as List? ?? const []))
              if (m is Map) _ChatMessage.fromJson(Map<String, dynamic>.from(m)),
          ]);
        _openOnlyLatest();
        _loading = false;
      });
      _scrollToEnd(jump: true);
      _askInitialQuestion();
    } catch (error) {
      if (mounted) {
        setState(() {
        _loading = false;
        _loadError = error;
      });
      }
    }
  }

  /// Pairs each question with the answer that follows it.
  List<({_ChatMessage? question, _ChatMessage? answer})> get _turns {
    final turns = <({_ChatMessage? question, _ChatMessage? answer})>[];
    for (final message in _messages) {
      if (message.role == 'user') {
        turns.add((question: message, answer: null));
      } else if (turns.isNotEmpty && turns.last.answer == null) {
        turns.last = (question: turns.last.question, answer: message);
      } else {
        turns.add((question: null, answer: message));
      }
    }
    return turns;
  }

  void _openOnlyLatest() => _openTurns
    ..clear()
    ..add(_turns.length - 1);

  List<Map<String, String>> get _history => _messages
      .skip(_messages.length > 8 ? _messages.length - 8 : 0)
      .map((m) => {'role': m.role, 'content': m.text})
      .toList();

  Future<void> _ask(String message) async {
    final text = message.trim();
    if (text.isEmpty || _sending) return;
    FocusScope.of(context).unfocus();

    final history = _history;
    setState(() {
      _messages.add(_ChatMessage(role: 'user', text: text));
      _openOnlyLatest();
      _sending = true;
      _controller.clear();
    });
    _scrollToEnd();

    try {
      final result = await widget.client.send(text, history);
      if (!mounted) return;
      final reply = _ChatMessage.fromJson({...result, 'role': 'assistant'});
      setState(() {
        _messages.add(reply.text.trim().isEmpty
            ? _ChatMessage(role: 'assistant', text: "I couldn't find an answer just now. Please try again.")
            : reply);
        _sending = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _sending = false);
      showErrorSnackBar(context, error);
    } finally {
      _scrollToEnd();
    }
  }

  Future<void> _toggleSaved(_ChatMessage message) async {
    if (message.id == null) return;
    try {
      final result = await widget.client.toggleSaved(message.id!);
      if (!mounted) return;
      setState(() => message.saved = result['saved'] == true);
      widget.onSavedChanged();
      showAppSnackBar(context, message.saved ? 'Saved. Find it in the Saved tab.' : 'Removed from saved answers.');
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  Future<void> _approve(Map<String, dynamic> step) async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _ApproveSheet(step: step, client: widget.client),
    );
    if (result == null || !mounted) return;
    setState(() => step
      ..clear()
      ..addAll(result));
    widget.onStepsChanged();
    showAppSnackBar(context, 'Added to your calendar and your plan.');
  }

  Future<void> _dismiss(Map<String, dynamic> step) async {
    try {
      final result = await widget.client.dismiss((step['id'] as num).toInt());
      if (mounted) {
        setState(() => step
        ..clear()
        ..addAll(result));
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  Future<void> _clear() async {
    final ok = await confirmDialog(
      context,
      title: 'Clear chat?',
      message: 'Your conversation is removed. Saved answers and steps in your plan are kept.',
      confirmLabel: 'Clear chat',
      destructive: true,
    );
    if (!ok || !mounted) return;
    try {
      await widget.client.clear();
      await _loadHistory();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  void _scrollToEnd({bool jump = false}) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      if (jump) {
        _scroll.jumpTo(_scroll.position.maxScrollExtent);
      } else {
        _scroll.animateTo(_scroll.position.maxScrollExtent, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_loadError != null) return ErrorState(error: _loadError!, onRetry: _loadHistory);

    return Column(
      children: [
        Expanded(
          child: ListView(
            controller: _scroll,
            padding: AppSpacing.listPadding,
            children: [
              if (!_enabled)
                const NoticeCard(
                  icon: Icons.cloud_off_outlined,
                  tone: PillTone.warning,
                  title: 'AI help is switched off',
                  message: 'You will get general career tips instead of a personal answer for now.',
                )
              else
                const NoticeCard(
                  icon: Icons.info_outline,
                  tone: PillTone.accent,
                  title: 'Guidance, not a final decision',
                  message: 'This mentor gives AI-generated suggestions. Check '
                      'important choices with your human mentor and the '
                      'programme team.',
                ),
              const SizedBox(height: AppSpacing.md),
              if (_messages.isEmpty) _emptyState(context),
              for (final (index, turn) in _turns.indexed)
                _TurnCard(
                  question: turn.question,
                  answer: turn.answer,
                  expanded: _openTurns.contains(index),
                  thinking: _sending && index == _turns.length - 1 && turn.answer == null,
                  onToggle: () => setState(() => _openTurns.contains(index) ? _openTurns.remove(index) : _openTurns.add(index)),
                  onToggleSaved: _toggleSaved,
                  onApprove: _approve,
                  onDismiss: _dismiss,
                ),
              if (_messages.isNotEmpty && !_sending)
                Center(
                  child: TextButton.icon(
                    onPressed: _clear,
                    icon: const Icon(Icons.delete_sweep_outlined),
                    label: const Text('Clear chat'),
                  ),
                ),
            ],
          ),
        ),
        _composer(context),
      ],
    );
  }

  Widget _emptyState(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Ask me anything about your career', style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Text(
          'Try one of these to get started:',
          style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
        const SizedBox(height: AppSpacing.sm),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final suggestion in _suggestions)
              ActionChip(
                avatar: const Icon(Icons.auto_awesome, size: 18),
                label: Text(suggestion),
                onPressed: _sending ? null : () => _ask(suggestion),
              ),
          ],
        ),
      ],
    );
  }

  Widget _composer(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.md),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              minLines: 1,
              maxLines: 4,
              maxLength: 1500,
              textCapitalization: TextCapitalization.sentences,
              enabled: !_sending,
              decoration: const InputDecoration(hintText: 'Type your career question…', counterText: ''),
              onSubmitted: _sending ? null : _ask,
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          IconButton.filled(
            tooltip: 'Send',
            onPressed: _sending ? null : () => _ask(_controller.text),
            icon: _sending
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.send_rounded),
          ),
        ],
      ),
    );
  }
}

/// Suggested steps under an answer: approve onto the calendar or dismiss.
class _StepsCard extends StatelessWidget {
  const _StepsCard({required this.steps, required this.onApprove, required this.onDismiss});

  final List<Map<String, dynamic>> steps;
  final ValueChanged<Map<String, dynamic>> onApprove;
  final ValueChanged<Map<String, dynamic>> onDismiss;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant);

    return Card(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Suggested steps', style: theme.textTheme.titleSmall),
            for (final step in steps) ...[
              const Divider(height: AppSpacing.lg),
              Text(step['title']?.toString() ?? '', style: theme.textTheme.bodyMedium),
              const SizedBox(height: AppSpacing.xxs),
              switch (step['status']) {
                'approved' => Text('On your calendar for ${step['due_label'] ?? ''}', style: muted),
                'completed' => Text('Done', style: muted),
                'dismissed' => Text('Dismissed', style: muted),
                _ => Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Suggested for ${step['due_label'] ?? ''}', style: muted),
                      const SizedBox(height: AppSpacing.xs),
                      Wrap(spacing: AppSpacing.sm, children: [
                        FilledButton.tonalIcon(
                          onPressed: () => onApprove(step),
                          icon: const Icon(Icons.event_available, size: 18),
                          label: const Text('Add to calendar'),
                        ),
                        TextButton(onPressed: () => onDismiss(step), child: const Text('Dismiss')),
                      ]),
                    ],
                  ),
              },
            ],
          ],
        ),
      ),
    );
  }
}

/// Edit a step's wording and date, then approve it onto the calendar.
class _ApproveSheet extends StatefulWidget {
  const _ApproveSheet({required this.step, required this.client});

  final Map<String, dynamic> step;
  final AiMentorClient client;

  @override
  State<_ApproveSheet> createState() => _ApproveSheetState();
}

class _ApproveSheetState extends State<_ApproveSheet> {
  late final _title = TextEditingController(text: widget.step['title']?.toString() ?? '');
  late DateTime _due = DateTime.tryParse(widget.step['due_on']?.toString() ?? '') ?? DateTime.now().add(const Duration(days: 7));
  bool _saving = false;

  @override
  void dispose() {
    _title.dispose();
    super.dispose();
  }

  String get _dueText => '${_due.year}-${_due.month.toString().padLeft(2, '0')}-${_due.day.toString().padLeft(2, '0')}';

  Future<void> _pickDate() async {
    final today = DateUtils.dateOnly(DateTime.now());
    final picked = await showDatePicker(
      context: context,
      initialDate: _due.isBefore(today) ? today : _due,
      firstDate: today,
      lastDate: today.add(const Duration(days: 730)),
      helpText: 'When will you do this?',
    );
    if (picked != null) setState(() => _due = picked);
  }

  Future<void> _save() async {
    if (_title.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      final result = await widget.client.approve((widget.step['id'] as num).toInt(), title: _title.text.trim(), dueOn: _dueText);
      if (mounted) Navigator.pop(context, result);
    } catch (error) {
      if (mounted) {
        setState(() => _saving = false);
        showErrorSnackBar(context, error);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.lg + MediaQuery.viewInsetsOf(context).bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Add to my calendar', style: theme.textTheme.titleLarge),
          const SizedBox(height: AppSpacing.xs),
          Text('Change the words or the date if you like. You will get a reminder on the day.', style: theme.textTheme.bodyMedium),
          const SizedBox(height: AppSpacing.md),
          TextField(controller: _title, maxLength: 190, decoration: const InputDecoration(labelText: 'Step')),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.event_outlined),
            title: Text(MaterialLocalizations.of(context).formatFullDate(_due)),
            subtitle: const Text('Tap to change the date'),
            onTap: _saving ? null : _pickDate,
          ),
          const SizedBox(height: AppSpacing.sm),
          FilledButton.icon(
            onPressed: _saving ? null : _save,
            icon: const Icon(Icons.check),
            label: Text(_saving ? 'Saving…' : 'Add to calendar'),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// My plan
// ---------------------------------------------------------------------------

class _PlanTab extends StatefulWidget {
  const _PlanTab({required this.client, required this.version});

  final AiMentorClient client;
  final ValueNotifier<int> version;

  @override
  State<_PlanTab> createState() => _PlanTabState();
}

class _PlanTabState extends State<_PlanTab> with AutomaticKeepAliveClientMixin {
  final _steps = <Map<String, dynamic>>[];
  String? _status; // null = all, 'approved', 'completed'
  int _page = 1;
  int _lastPage = 1;
  bool _loading = true;
  Object? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    widget.version.addListener(_reload);
    _reload();
  }

  @override
  void dispose() {
    widget.version.removeListener(_reload);
    super.dispose();
  }

  Future<void> _reload() => _load(reset: true);

  Future<void> _load({bool reset = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final page = reset ? 1 : _page + 1;
      final data = await widget.client.plan(status: _status, page: page);
      if (!mounted) return;
      setState(() {
        if (reset) _steps.clear();
        _steps.addAll([for (final s in (data['data'] as List? ?? const [])) if (s is Map) Map<String, dynamic>.from(s)]);
        _page = ((data['meta'] as Map?)?['current_page'] as num?)?.toInt() ?? page;
        _lastPage = ((data['meta'] as Map?)?['last_page'] as num?)?.toInt() ?? page;
        _loading = false;
      });
    } catch (error) {
      if (mounted) {
        setState(() {
        _loading = false;
        _error = error;
      });
      }
    }
  }

  Future<void> _toggle(Map<String, dynamic> step) async {
    try {
      final result = await widget.client.toggleDone((step['id'] as num).toInt());
      if (mounted) {
        setState(() => step
        ..clear()
        ..addAll(result));
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  bool _overdue(Map<String, dynamic> step) {
    final due = DateTime.tryParse(step['due_on']?.toString() ?? '');
    return step['status'] == 'approved' && due != null && due.isBefore(DateUtils.dateOnly(DateTime.now()));
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final theme = Theme.of(context);

    return RefreshIndicator(
      onRefresh: _reload,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          Wrap(spacing: AppSpacing.sm, children: [
            for (final (value, label) in const [(null, 'All'), ('approved', 'Planned'), ('completed', 'Done')])
              ChoiceChip(
                label: Text(label),
                selected: _status == value,
                onSelected: (_) {
                  setState(() => _status = value);
                  _reload();
                },
              ),
          ]),
          const SizedBox(height: AppSpacing.md),
          if (_error != null && _steps.isEmpty)
            ErrorState(error: _error!, onRetry: _reload)
          else if (_loading && _steps.isEmpty)
            const Padding(padding: EdgeInsets.all(AppSpacing.xl), child: Center(child: CircularProgressIndicator()))
          else if (_steps.isEmpty)
            const EmptyState(
              icon: Icons.checklist,
              title: 'No steps yet',
              message: 'When the mentor suggests steps, tap "Add to calendar" and they appear here.',
            )
          else ...[
            for (final step in _steps)
              Card(
                child: CheckboxListTile(
                  value: step['status'] == 'completed',
                  onChanged: (_) => _toggle(step),
                  title: Text(
                    step['title']?.toString() ?? '',
                    style: step['status'] == 'completed' ? const TextStyle(decoration: TextDecoration.lineThrough) : null,
                  ),
                  subtitle: Text(
                    [
                      if (_overdue(step)) 'Overdue' else 'Due ${step['due_label'] ?? ''}',
                      if (step['goal'] != null) 'Goal: ${step['goal']}',
                    ].join(' · '),
                    style: _overdue(step) ? TextStyle(color: theme.colorScheme.error) : null,
                  ),
                  controlAffinity: ListTileControlAffinity.leading,
                ),
              ),
            if (_page < _lastPage)
              Center(
                child: TextButton(onPressed: _loading ? null : () => _load(), child: Text(_loading ? 'Loading…' : 'Show more')),
              ),
          ],
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Saved answers
// ---------------------------------------------------------------------------

class _SavedTab extends StatefulWidget {
  const _SavedTab({required this.client, required this.version});

  final AiMentorClient client;
  final ValueNotifier<int> version;

  @override
  State<_SavedTab> createState() => _SavedTabState();
}

class _SavedTabState extends State<_SavedTab> with AutomaticKeepAliveClientMixin {
  final _answers = <Map<String, dynamic>>[];
  int _page = 1;
  int _lastPage = 1;
  bool _loading = true;
  Object? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    widget.version.addListener(_reload);
    _reload();
  }

  @override
  void dispose() {
    widget.version.removeListener(_reload);
    super.dispose();
  }

  Future<void> _reload() => _load(reset: true);

  Future<void> _load({bool reset = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final page = reset ? 1 : _page + 1;
      final data = await widget.client.saved(page: page);
      if (!mounted) return;
      setState(() {
        if (reset) _answers.clear();
        _answers.addAll([for (final a in (data['data'] as List? ?? const [])) if (a is Map) Map<String, dynamic>.from(a)]);
        _page = ((data['meta'] as Map?)?['current_page'] as num?)?.toInt() ?? page;
        _lastPage = ((data['meta'] as Map?)?['last_page'] as num?)?.toInt() ?? page;
        _loading = false;
      });
    } catch (error) {
      if (mounted) {
        setState(() {
        _loading = false;
        _error = error;
      });
      }
    }
  }

  Future<void> _remove(Map<String, dynamic> answer) async {
    try {
      await widget.client.toggleSaved((answer['id'] as num).toInt());
      if (mounted) setState(() => _answers.remove(answer));
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _reload,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          if (_error != null && _answers.isEmpty)
            ErrorState(error: _error!, onRetry: _reload)
          else if (_loading && _answers.isEmpty)
            const Padding(padding: EdgeInsets.all(AppSpacing.xl), child: Center(child: CircularProgressIndicator()))
          else if (_answers.isEmpty)
            const EmptyState(
              icon: Icons.bookmark_outline,
              title: 'No saved answers yet',
              message: 'Tap "Save" under any answer in the chat to keep it here.',
            )
          else ...[
            for (final answer in _answers)
              Card(
                child: ExpansionTile(
                  leading: const Icon(Icons.bookmark),
                  title: Text(answer['content']?.toString() ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                  childrenPadding: const EdgeInsets.fromLTRB(AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.md),
                  children: [
                    Align(alignment: Alignment.centerLeft, child: SelectableText(answer['content']?.toString() ?? '')),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton.icon(
                        onPressed: () => _remove(answer),
                        icon: const Icon(Icons.bookmark_remove_outlined),
                        label: const Text('Remove'),
                      ),
                    ),
                  ],
                ),
              ),
            if (_page < _lastPage)
              Center(
                child: TextButton(onPressed: _loading ? null : () => _load(), child: Text(_loading ? 'Loading…' : 'Show more')),
              ),
          ],
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Shared pieces
// ---------------------------------------------------------------------------

/// Used when a test injects only [MentorshipAiScreen.sendMessage].
class _SendOnlyClient implements AiMentorClient {
  _SendOnlyClient(this._send);

  final AssistantSender _send;
  static const _empty = {'data': <dynamic>[], 'meta': {'current_page': 1, 'last_page': 1, 'total': 0}};

  @override
  Future<Map<String, dynamic>> history() async => {'enabled': true, 'messages': <dynamic>[]};
  @override
  Future<Map<String, dynamic>> send(String message, List<Map<String, String>> history) => _send(message, history);
  @override
  Future<Map<String, dynamic>> plan({String? status, int page = 1}) async => Map.of(_empty);
  @override
  Future<Map<String, dynamic>> saved({int page = 1}) async => Map.of(_empty);
  @override
  Future<Map<String, dynamic>> toggleSaved(int messageId) => throw UnsupportedError('Not available in this mode');
  @override
  Future<void> clear() => throw UnsupportedError('Not available in this mode');
  @override
  Future<Map<String, dynamic>> approve(int stepId, {required String title, required String dueOn}) =>
      throw UnsupportedError('Not available in this mode');
  @override
  Future<Map<String, dynamic>> dismiss(int stepId) => throw UnsupportedError('Not available in this mode');
  @override
  Future<Map<String, dynamic>> toggleDone(int stepId) => throw UnsupportedError('Not available in this mode');
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.message});

  final _ChatMessage message;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final isUser = message.role == 'user';

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.all(AppSpacing.md),
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.82),
        decoration: BoxDecoration(
          color: isUser ? scheme.primary : scheme.surfaceContainerHighest,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(AppRadius.lg),
            topRight: const Radius.circular(AppRadius.lg),
            bottomLeft: Radius.circular(isUser ? AppRadius.lg : AppRadius.sm),
            bottomRight: Radius.circular(isUser ? AppRadius.sm : AppRadius.lg),
          ),
        ),
        child: SelectableText(
          message.text,
          style: theme.textTheme.bodyMedium?.copyWith(color: isUser ? scheme.onPrimary : scheme.onSurface),
        ),
      ),
    );
  }
}

/// One question and its answer, collapsible so earlier answers don't fill the chat.
class _TurnCard extends StatelessWidget {
  const _TurnCard({
    required this.question,
    required this.answer,
    required this.expanded,
    required this.thinking,
    required this.onToggle,
    required this.onToggleSaved,
    required this.onApprove,
    required this.onDismiss,
  });

  final _ChatMessage? question;
  final _ChatMessage? answer;
  final bool expanded;
  final bool thinking;
  final VoidCallback onToggle;
  final void Function(_ChatMessage) onToggleSaved;
  final Future<void> Function(Map<String, dynamic>) onApprove;
  final Future<void> Function(Map<String, dynamic>) onDismiss;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final answer = this.answer;
    final steps = answer?.actions.length ?? 0;
    final meta = [
      if (answer?.saved == true) 'Saved',
      if (steps > 0) '$steps ${steps == 1 ? 'step' : 'steps'}',
    ].join(' · ');

    return Card(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InkWell(
            onTap: onToggle,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
              child: Row(
                children: [
                  Icon(expanded ? Icons.expand_less : Icons.expand_more, color: scheme.primary),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      question?.text ?? 'Earlier answer',
                      maxLines: expanded ? null : 1,
                      overflow: expanded ? null : TextOverflow.ellipsis,
                      style: theme.textTheme.titleSmall,
                    ),
                  ),
                  if (meta.isNotEmpty) ...[
                    const SizedBox(width: AppSpacing.sm),
                    if (answer?.saved == true) Icon(Icons.bookmark, size: 16, color: scheme.primary),
                    Text(meta, style: theme.textTheme.labelSmall?.copyWith(color: scheme.primary)),
                  ],
                ],
              ),
            ),
          ),
          if (expanded)
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.md, 0, AppSpacing.md, AppSpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (answer != null) ...[
                    _Bubble(message: answer),
                    if (answer.id != null)
                      Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton.icon(
                          onPressed: () => onToggleSaved(answer),
                          icon: Icon(answer.saved ? Icons.bookmark : Icons.bookmark_outline, size: 18),
                          label: Text(answer.saved ? 'Saved' : 'Save'),
                        ),
                      ),
                    if (answer.actions.isNotEmpty) _StepsCard(steps: answer.actions, onApprove: onApprove, onDismiss: onDismiss),
                  ] else if (thinking)
                    const _ThinkingBubble(),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _ThinkingBubble extends StatelessWidget {
  const _ThinkingBubble();

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
        decoration: BoxDecoration(color: scheme.surfaceContainerHighest, borderRadius: BorderRadius.circular(AppRadius.lg)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
            const SizedBox(width: AppSpacing.sm),
            Text('Thinking…', style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant)),
          ],
        ),
      ),
    );
  }
}
