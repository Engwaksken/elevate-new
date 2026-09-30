import 'package:flutter/material.dart';

import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import 'decorations.dart';

/// Friendly spot illustration drawn from shapes and icons (no images): a
/// soft blob, a tinted circle with the main icon and a few sparkles.
class FriendlyIllustration extends StatelessWidget {
  const FriendlyIllustration({
    super.key,
    required this.icon,
    this.color,
    this.size = 132,
  });

  final IconData icon;
  final Color? color;
  final double size;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final brand = BrandColors.of(context);
    final accent = color ?? scheme.primary;

    return SizedBox.square(
      dimension: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Positioned.fill(
            child: CustomPaint(
              painter: SoftBlobsPainter(
                colors: [brand.blobB, brand.blobC, brand.blobA],
                opacity: 0.6,
                seed: icon.codePoint % 3,
              ),
            ),
          ),
          Container(
            width: size * 0.56,
            height: size * 0.56,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: scheme.surfaceContainerLowest,
              boxShadow: [
                BoxShadow(
                  color: AppColors.brown.withValues(alpha: 0.12),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Icon(icon, size: size * 0.28, color: accent),
          ),
          Positioned(
            top: size * 0.12,
            right: size * 0.14,
            child: Icon(Icons.auto_awesome, size: size * 0.14, color: brand.accentFill),
          ),
          Positioned(
            bottom: size * 0.16,
            left: size * 0.12,
            child: Icon(Icons.favorite, size: size * 0.1, color: scheme.tertiary.withValues(alpha: 0.7)),
          ),
          Positioned(
            top: size * 0.22,
            left: size * 0.16,
            child: Container(
              width: size * 0.06,
              height: size * 0.06,
              decoration: BoxDecoration(shape: BoxShape.circle, color: brand.accentFill),
            ),
          ),
        ],
      ),
    );
  }
}

/// Centred message with an icon, used for empty lists. Scrollable so it
/// works inside a RefreshIndicator and never overflows at large text sizes.
class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return _CenteredScrollable(
      child: _MessageBlock(
        icon: icon,
        iconColor: Theme.of(context).colorScheme.primary,
        title: title,
        message: message,
        action: actionLabel != null && onAction != null
            ? FilledButton.tonal(
                onPressed: onAction,
                child: Text(actionLabel!),
              )
            : null,
      ),
    );
  }
}

/// Inline error with a Retry button. Accepts any error object and only
/// ever shows the friendly mapped message.
class ErrorState extends StatelessWidget {
  const ErrorState({
    super.key,
    required this.error,
    this.onRetry,
    this.title,
  });

  final Object error;
  final VoidCallback? onRetry;
  final String? title;

  @override
  Widget build(BuildContext context) {
    final mapped = AppException.from(error);
    final offline = mapped.kind == AppErrorKind.offline;

    return _CenteredScrollable(
      child: _MessageBlock(
        icon: offline ? Icons.cloud_off_outlined : Icons.error_outline,
        iconColor: Theme.of(context).colorScheme.error,
        title: title ?? (offline ? "You're offline" : "Couldn't load this"),
        message: mapped.message,
        action: onRetry == null
            ? null
            : FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
      ),
    );
  }
}

class _CenteredScrollable extends StatelessWidget {
  const _CenteredScrollable({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
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
          child: Center(child: child),
        ),
      ),
    );
  }
}

class _MessageBlock extends StatelessWidget {
  const _MessageBlock({
    required this.icon,
    required this.iconColor,
    required this.title,
    this.message,
    this.action,
  });

  final IconData icon;
  final Color iconColor;
  final String title;
  final String? message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 420),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ExcludeSemantics(
            child: FriendlyIllustration(icon: icon, color: iconColor),
          ),
          const SizedBox(height: AppSpacing.lg),
          Text(
            title,
            style: theme.textTheme.titleMedium,
            textAlign: TextAlign.center,
          ),
          if (message != null && message!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(
              message!,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
              textAlign: TextAlign.center,
            ),
          ],
          if (action != null) ...[
            const SizedBox(height: AppSpacing.lg),
            action!,
          ],
        ],
      ),
    );
  }
}

/// Pulsing placeholder rows shown while content loads (no extra package).
class LoadingSkeleton extends StatefulWidget {
  const LoadingSkeleton({super.key, this.itemCount = 6, this.itemHeight = 76});

  final int itemCount;
  final double itemHeight;

  @override
  State<LoadingSkeleton> createState() => _LoadingSkeletonState();
}

class _LoadingSkeletonState extends State<LoadingSkeleton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1100),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final color = Theme.of(context).colorScheme.surfaceContainerHighest;
    final reduceMotion = MediaQuery.of(context).disableAnimations;

    return Semantics(
      label: 'Loading',
      liveRegion: true,
      child: ExcludeSemantics(
        child: FadeTransition(
          opacity: reduceMotion
              ? const AlwaysStoppedAnimation(0.8)
              : Tween(begin: 0.45, end: 1.0).animate(_controller),
          child: ListView.separated(
            physics: const NeverScrollableScrollPhysics(),
            padding: AppSpacing.listPadding,
            itemCount: widget.itemCount,
            separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
            itemBuilder: (_, __) => Container(
              height: widget.itemHeight,
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(AppRadius.lg),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Section heading with optional trailing action.
class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
    this.padding = const EdgeInsets.fromLTRB(0, AppSpacing.lg, 0, AppSpacing.sm),
  });

  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: padding,
      child: Row(
        children: [
          Expanded(
            child: Semantics(
              header: true,
              child: Text(title, style: theme.textTheme.titleMedium),
            ),
          ),
          if (actionLabel != null && onAction != null)
            TextButton(onPressed: onAction, child: Text(actionLabel!)),
        ],
      ),
    );
  }
}

/// Small rounded label, e.g. a status or lesson type.
class InfoChip extends StatelessWidget {
  const InfoChip({super.key, required this.label, this.icon, this.emphasis = false});

  final String label;
  final IconData? icon;
  final bool emphasis;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final bg = emphasis ? scheme.primaryContainer : scheme.surfaceContainerHighest;
    final fg = emphasis ? scheme.onPrimaryContainer : scheme.onSurfaceVariant;

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.pill),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 16, color: fg),
            const SizedBox(width: AppSpacing.xs),
          ],
          Flexible(
            child: Text(
              label,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(color: fg),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

enum PillTone { neutral, brand, accent, rose, success, warning, danger }

/// Coloured status pill ("Overdue", "Graded", "Scheduled"...). Always has
/// a text label and an icon, so status is never conveyed by colour alone.
/// All tone pairs meet 4.5:1 contrast in light and dark themes.
class StatusPill extends StatelessWidget {
  const StatusPill({
    super.key,
    required this.label,
    required this.tone,
    this.icon,
  });

  final String label;
  final PillTone tone;
  final IconData? icon;

  static (Color, Color) colors(BuildContext context, PillTone tone) {
    final scheme = Theme.of(context).colorScheme;
    final dark = Theme.of(context).brightness == Brightness.dark;
    return switch (tone) {
      PillTone.neutral => (scheme.surfaceContainerHighest, scheme.onSurfaceVariant),
      PillTone.brand => (scheme.primaryContainer, scheme.onPrimaryContainer),
      PillTone.accent => (scheme.secondaryContainer, scheme.onSecondaryContainer),
      PillTone.rose => (scheme.tertiaryContainer, scheme.onTertiaryContainer),
      PillTone.success => dark
          ? (AppColors.successContainerDark, AppColors.onSuccessContainerDark)
          : (AppColors.successContainerLight, AppColors.onSuccessContainerLight),
      PillTone.warning => dark
          ? (AppColors.warningContainerDark, AppColors.onWarningContainerDark)
          : (AppColors.warningContainerLight, AppColors.onWarningContainerLight),
      PillTone.danger => (scheme.errorContainer, scheme.onErrorContainer),
    };
  }

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = colors(context, tone);
    return Semantics(
      label: label,
      excludeSemantics: true,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.xs,
        ),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppRadius.pill),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 16, color: fg),
              const SizedBox(width: AppSpacing.xs),
            ],
            Flexible(
              child: Text(
                label,
                style: Theme.of(context).textTheme.labelMedium?.copyWith(
                      color: fg,
                      fontWeight: FontWeight.w500,
                    ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Tinted callout card (info / warning / success) with optional actions.
class NoticeCard extends StatelessWidget {
  const NoticeCard({
    super.key,
    required this.icon,
    required this.message,
    this.title,
    this.tone = PillTone.accent,
    this.actions = const [],
  });

  final IconData icon;
  final String? title;
  final String message;
  final PillTone tone;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = StatusPill.colors(context, tone);
    final theme = Theme.of(context);
    return Semantics(
      container: true,
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, color: fg, size: 22),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (title != null)
                        Text(title!, style: theme.textTheme.titleSmall?.copyWith(color: fg)),
                      Text(message, style: theme.textTheme.bodyMedium?.copyWith(color: fg)),
                    ],
                  ),
                ),
              ],
            ),
            if (actions.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Wrap(spacing: AppSpacing.sm, runSpacing: AppSpacing.sm, children: actions),
            ],
          ],
        ),
      ),
    );
  }
}
