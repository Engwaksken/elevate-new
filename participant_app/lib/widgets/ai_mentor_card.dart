import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

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

/// AI career mentor card for the top of the dashboard: ask a question right
/// here and land in the mentor chat with it already sent.
///
/// Collapses to a single slim row so it doesn't push the rest of the
/// dashboard down; the choice is remembered between sessions.
class AiMentorDashboardCard extends StatefulWidget {
  const AiMentorDashboardCard({super.key, this.availability});

  /// Injectable for tests; defaults to [AiMentorAvailability.enabled].
  final Future<bool> Function()? availability;

  @override
  State<AiMentorDashboardCard> createState() => _AiMentorDashboardCardState();
}

class _AiMentorDashboardCardState extends State<AiMentorDashboardCard> {
  static const _expandedKey = 'ai_mentor_card_expanded';
  static const _suggestions = [
    'How do I choose a career path?',
    'How should I prepare for an interview?',
    'What skills should I build next?',
  ];

  final _controller = TextEditingController();
  bool _visible = true;
  bool _expanded = true;

  @override
  void initState() {
    super.initState();
    (widget.availability ?? AiMentorAvailability.enabled)().then((enabled) {
      if (mounted && !enabled) setState(() => _visible = false);
    });
    _restoreExpanded();
  }

  Future<void> _restoreExpanded() async {
    try {
      final saved = (await SharedPreferences.getInstance()).getBool(_expandedKey);
      if (mounted && saved != null) setState(() => _expanded = saved);
    } catch (_) {
      // No stored preference: stay expanded.
    }
  }

  void _toggle() {
    setState(() => _expanded = !_expanded);
    if (!_expanded) FocusScope.of(context).unfocus();
    SharedPreferences.getInstance()
        .then((prefs) => prefs.setBool(_expandedKey, _expanded))
        .catchError((_) => false);
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
    final brand = BrandColors.of(context);
    // The header gradient keeps [onHeader] text above AA in light and dark
    // themes; scheme.primary is a light peach in dark mode, so white text on
    // it was unreadable.
    final onBrand = brand.onHeader;

    return Semantics(
      container: true,
      label: 'AI career mentor',
      child: Material(
        type: MaterialType.transparency,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            gradient: LinearGradient(
              colors: brand.headerGradient,
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            boxShadow: [BoxShadow(color: brand.headerGradient.first.withValues(alpha: 0.25), blurRadius: 14, offset: const Offset(0, 6))],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _header(theme, scheme, onBrand),
              AnimatedSize(
                duration: const Duration(milliseconds: 200),
                curve: Curves.easeOutCubic,
                alignment: Alignment.topCenter,
                child: _expanded ? _body(theme, scheme, onBrand) : const SizedBox(width: double.infinity),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _header(ThemeData theme, ColorScheme scheme, Color onBrand) {
    return InkWell(
      borderRadius: BorderRadius.circular(AppRadius.lg),
      onTap: _toggle,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.sm, AppSpacing.xs, AppSpacing.sm),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.xs + 2),
              decoration: BoxDecoration(color: scheme.secondary, borderRadius: BorderRadius.circular(AppRadius.sm)),
              child: Icon(Icons.auto_awesome, size: 20, color: scheme.onSecondary),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Your AI career mentor', style: theme.textTheme.titleMedium?.copyWith(color: onBrand, fontWeight: FontWeight.w700)),
                  Text(
                    'Jobs, CVs, interviews and your next step',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodySmall?.copyWith(color: onBrand.withValues(alpha: 0.85)),
                  ),
                ],
              ),
            ),
            // Collapsed: one tap still gets her into the chat.
            if (!_expanded)
              TextButton(
                style: TextButton.styleFrom(foregroundColor: onBrand),
                onPressed: () => _open(),
                child: const Text('Ask'),
              ),
            IconButton(
              tooltip: _expanded ? 'Collapse AI mentor' : 'Expand AI mentor',
              color: onBrand,
              onPressed: _toggle,
              icon: AnimatedRotation(
                turns: _expanded ? 0.5 : 0,
                duration: const Duration(milliseconds: 200),
                child: const Icon(Icons.expand_more),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _body(ThemeData theme, ColorScheme scheme, Color onBrand) {
    // Solid white chips with black text read the same in light and dark
    // themes (Material 3 chips paint their own surface over backgroundColor).
    const chipFill = WidgetStatePropertyAll<Color>(Colors.white);
    const chipText = TextStyle(color: Colors.black87);

    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.md, 0, AppSpacing.md, AppSpacing.sm),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: _controller,
            textInputAction: TextInputAction.send,
            textCapitalization: TextCapitalization.sentences,
            maxLength: 1500,
            onSubmitted: _open,
            style: TextStyle(color: scheme.onSurface),
            decoration: InputDecoration(
              hintText: 'Ask a career question…',
              counterText: '',
              isDense: true,
              filled: true,
              fillColor: scheme.surface,
              contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.md),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
              suffixIcon: IconButton(
                tooltip: 'Ask my mentor',
                icon: Icon(Icons.send_rounded, color: scheme.primary),
                onPressed: () => _open(_controller.text),
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text('Try asking', style: theme.textTheme.labelSmall?.copyWith(color: onBrand.withValues(alpha: 0.85))),
          const SizedBox(height: AppSpacing.xs),
          // One scrolling row instead of a wrap keeps the card short; the
          // last chip peeks at the edge to hint there's more.
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final question in _suggestions)
                  Padding(
                    padding: const EdgeInsets.only(right: AppSpacing.sm),
                    child: ActionChip(
                      label: Text(question, style: chipText),
                      color: chipFill,
                      side: BorderSide.none,
                      visualDensity: VisualDensity.compact,
                      onPressed: () => _open(question),
                    ),
                  ),
              ],
            ),
          ),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              style: TextButton.styleFrom(foregroundColor: onBrand, padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xs)),
              icon: const Icon(Icons.chat_bubble_outline, size: 18),
              label: const Text('Open my chats and plan'),
              onPressed: () => _open(),
            ),
          ),
        ],
      ),
    );
  }
}
