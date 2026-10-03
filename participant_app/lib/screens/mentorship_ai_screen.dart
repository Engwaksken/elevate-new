import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// Signature for the assistant call, injectable so widget tests can avoid
/// the network.
typedef AssistantSender = Future<Map<String, dynamic>> Function(
  String message,
  List<Map<String, String>> history,
);

class _ChatMessage {
  _ChatMessage(this.role, this.text);

  final String role; // 'user' | 'assistant'
  final String text;
}

/// AI career mentor. Answers career questions and points the participant to
/// concrete next steps, grounded in her goals and mentorship on the server.
class MentorshipAiScreen extends StatefulWidget {
  const MentorshipAiScreen({super.key, this.sendMessage});

  final AssistantSender? sendMessage;

  @override
  State<MentorshipAiScreen> createState() => _MentorshipAiScreenState();
}

class _MentorshipAiScreenState extends State<MentorshipAiScreen> {
  static const _suggestions = <String>[
    'How do I choose a career path?',
    'How should I prepare for an interview?',
    'What skills should I build next?',
    'How can I make the most of my mentor?',
  ];

  final _messages = <_ChatMessage>[];
  final _controller = TextEditingController();
  final _scroll = ScrollController();
  bool _sending = false;

  @override
  void dispose() {
    _controller.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<Map<String, dynamic>> _send(String message, List<Map<String, String>> history) {
    final injected = widget.sendMessage;
    if (injected != null) return injected(message, history);
    return ApiService.instance.mentorshipAssistant(message: message, history: history);
  }

  List<Map<String, String>> get _history => _messages
      .take(8)
      .map((m) => {'role': m.role, 'content': m.text})
      .toList();

  Future<void> _ask(String message) async {
    final text = message.trim();
    if (text.isEmpty || _sending) return;
    FocusScope.of(context).unfocus();

    setState(() {
      _messages.add(_ChatMessage('user', text));
      _sending = true;
      _controller.clear();
    });
    _scrollToEnd();

    try {
      final result = await _send(text, _history);
      if (!mounted) return;
      final reply = result['message']?.toString().trim();
      setState(() {
        _messages.add(_ChatMessage(
          'assistant',
          reply == null || reply.isEmpty
              ? "I couldn't find an answer just now. Please try again."
              : reply,
        ));
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

  void _scrollToEnd() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      _scroll.animateTo(
        _scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('AI career mentor')),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: ListView(
                controller: _scroll,
                padding: AppSpacing.listPadding,
                children: [
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
                  for (final message in _messages) _Bubble(message: message),
                  if (_sending) const _ThinkingBubble(),
                ],
              ),
            ),
            _composer(context),
          ],
        ),
      ),
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
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
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
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.md,
        AppSpacing.sm,
        AppSpacing.md,
        AppSpacing.md,
      ),
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
              decoration: const InputDecoration(
                hintText: 'Type your career question…',
                counterText: '',
              ),
              onSubmitted: _sending ? null : _ask,
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          IconButton.filled(
            tooltip: 'Send',
            onPressed: _sending ? null : () => _ask(_controller.text),
            icon: _sending
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.send_rounded),
          ),
        ],
      ),
    );
  }
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
        constraints: BoxConstraints(
          maxWidth: MediaQuery.sizeOf(context).width * 0.82,
        ),
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
          style: theme.textTheme.bodyMedium?.copyWith(
            color: isUser ? scheme.onPrimary : scheme.onSurface,
          ),
        ),
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
        padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: scheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
            const SizedBox(width: AppSpacing.sm),
            Text(
              'Thinking…',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: scheme.onSurfaceVariant,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
