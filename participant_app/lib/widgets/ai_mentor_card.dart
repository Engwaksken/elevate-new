import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../screens/mentorship_ai_screen.dart';
import '../services/api_service.dart';
import '../services/sync_service.dart';

/// Whether the AI career mentor is switched on, checked once per app session.
/// Offline or failed checks count as "on" so the entry points stay visible.
class AiMentorAvailability {
  AiMentorAvailability._();

  static Future<bool>? _check;

  static Future<bool> enabled() => _check ??= () async {
        try {
          if (!await SyncService.instance.isOnline()) return true;
          final data = await ApiService.instance.mentorshipAssistantHistory();
          return data['enabled'] != false;
        } catch (_) {
          _check = null; // try again next time
          return true;
        }
      }();

  /// Lets tests or a settings change force a fresh check.
  static void reset() => _check = null;
}

/// Prominent AI career mentor card for the top of the dashboard: ask a
/// question right here and land in the mentor chat with it already sent.
class AiMentorDashboardCard extends StatefulWidget {
  const AiMentorDashboardCard({super.key, this.availability});

  /// Injectable for tests; defaults to [AiMentorAvailability.enabled].
  final Future<bool> Function()? availability;

  @override
  State<AiMentorDashboardCard> createState() => _AiMentorDashboardCardState();
}

class _AiMentorDashboardCardState extends State<AiMentorDashboardCard> {
  static const _suggestions = [
    'How do I choose a career path?',
    'How should I prepare for an interview?',
    'What skills should I build next?',
  ];

  final _controller = TextEditingController();
  bool _visible = true;

  @override
  void initState() {
    super.initState();
    (widget.availability ?? AiMentorAvailability.enabled)().then((enabled) {
      if (mounted && !enabled) setState(() => _visible = false);
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _open([String? question]) {
    final text = question?.trim();
    FocusScope.of(context).unfocus();
    _controller.clear();
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => MentorshipAiScreen(initialQuestion: text == null || text.isEmpty ? null : text),
    ));
  }

  @override
  Widget build(BuildContext context) {
    if (!_visible) return const SizedBox.shrink();
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    const onBrand = Colors.white;

    return Semantics(
      container: true,
      label: 'AI career mentor',
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          gradient: LinearGradient(
            colors: [scheme.primary, Color.lerp(scheme.primary, Colors.black, 0.25)!],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          boxShadow: [BoxShadow(color: scheme.primary.withValues(alpha: 0.25), blurRadius: 18, offset: const Offset(0, 8))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(color: scheme.secondary, borderRadius: BorderRadius.circular(AppRadius.md)),
                  child: Icon(Icons.auto_awesome, color: scheme.onSecondary),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Text('Your AI career mentor', style: theme.textTheme.titleLarge?.copyWith(color: onBrand, fontWeight: FontWeight.w700)),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              'Ask anything about jobs, CVs, interviews or your next step. Answers are based on your goals.',
              style: theme.textTheme.bodyMedium?.copyWith(color: onBrand.withValues(alpha: 0.9)),
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              controller: _controller,
              textInputAction: TextInputAction.send,
              textCapitalization: TextCapitalization.sentences,
              maxLength: 1500,
              onSubmitted: _open,
              decoration: InputDecoration(
                hintText: 'Ask a career question…',
                counterText: '',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
                suffixIcon: IconButton(
                  tooltip: 'Ask my mentor',
                  icon: Icon(Icons.send_rounded, color: scheme.primary),
                  onPressed: () => _open(_controller.text),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.xs,
              children: [
                for (final question in _suggestions)
                  ActionChip(
                    label: Text(question, style: const TextStyle(color: onBrand)),
                    backgroundColor: Colors.white.withValues(alpha: 0.12),
                    side: BorderSide(color: Colors.white.withValues(alpha: 0.45)),
                    onPressed: () => _open(question),
                  ),
                ActionChip(
                  avatar: const Icon(Icons.chat_bubble_outline, size: 18, color: onBrand),
                  label: const Text('Open my chats and plan', style: TextStyle(color: onBrand)),
                  backgroundColor: Colors.white.withValues(alpha: 0.12),
                  side: BorderSide(color: Colors.white.withValues(alpha: 0.45)),
                  onPressed: () => _open(),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
