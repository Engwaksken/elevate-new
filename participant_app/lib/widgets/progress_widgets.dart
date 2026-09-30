import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';

/// Circular progress ring (custom painted, no chart library). [value] is
/// 0..1. Numbers are always shown as text too, so colour is never the only
/// signal.
class ProgressRing extends StatelessWidget {
  const ProgressRing({
    super.key,
    required this.value,
    this.size = 64,
    this.strokeWidth = 8,
    this.color,
    this.trackColor,
    this.center,
    this.semanticsLabel,
  });

  final double value;
  final double size;
  final double strokeWidth;
  final Color? color;
  final Color? trackColor;
  final Widget? center;
  final String? semanticsLabel;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final brand = BrandColors.of(context);
    final ring = SizedBox.square(
      dimension: size,
      child: CustomPaint(
        painter: _RingPainter(
          value: value.isNaN ? 0 : value.clamp(0, 1).toDouble(),
          color: color ?? brand.accentFill,
          track: trackColor ?? scheme.surfaceContainerHighest,
          strokeWidth: strokeWidth,
        ),
        child: center == null
            ? null
            : Center(
                child: Padding(
                  padding: EdgeInsets.all(strokeWidth + 2),
                  child: FittedBox(fit: BoxFit.scaleDown, child: center),
                ),
              ),
      ),
    );

    if (semanticsLabel == null) return ring;
    return Semantics(label: semanticsLabel, excludeSemantics: true, child: ring);
  }
}

class _RingPainter extends CustomPainter {
  const _RingPainter({
    required this.value,
    required this.color,
    required this.track,
    required this.strokeWidth,
  });

  final double value;
  final Color color;
  final Color track;
  final double strokeWidth;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    final arcRect = rect.deflate(strokeWidth / 2);
    final base = Paint()
      ..color = track
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;
    canvas.drawArc(arcRect, 0, math.pi * 2, false, base);

    if (value <= 0) return;
    final fg = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round
      ..strokeWidth = strokeWidth;
    canvas.drawArc(arcRect, -math.pi / 2, math.pi * 2 * value, false, fg);
  }

  @override
  bool shouldRepaint(_RingPainter old) =>
      old.value != value ||
      old.color != color ||
      old.track != track ||
      old.strokeWidth != strokeWidth;
}

/// Rounded horizontal bar, custom painted.
class ProgressBar extends StatelessWidget {
  const ProgressBar({
    super.key,
    required this.value,
    this.height = 10,
    this.color,
    this.semanticsLabel,
  });

  final double value;
  final double height;
  final Color? color;
  final String? semanticsLabel;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final brand = BrandColors.of(context);
    final bar = SizedBox(
      height: height,
      width: double.infinity,
      child: CustomPaint(
        painter: _BarPainter(
          value: value.isNaN ? 0 : value.clamp(0, 1).toDouble(),
          color: color ?? brand.accentFill,
          track: scheme.surfaceContainerHighest,
        ),
      ),
    );
    if (semanticsLabel == null) return ExcludeSemantics(child: bar);
    return Semantics(label: semanticsLabel, excludeSemantics: true, child: bar);
  }
}

class _BarPainter extends CustomPainter {
  const _BarPainter({required this.value, required this.color, required this.track});

  final double value;
  final Color color;
  final Color track;

  @override
  void paint(Canvas canvas, Size size) {
    final radius = Radius.circular(size.height / 2);
    canvas.drawRRect(
      RRect.fromRectAndRadius(Offset.zero & size, radius),
      Paint()..color = track,
    );
    if (value <= 0) return;
    final width = math.max(size.height, size.width * value);
    canvas.drawRRect(
      RRect.fromRectAndRadius(Rect.fromLTWH(0, 0, width, size.height), radius),
      Paint()..color = color,
    );
  }

  @override
  bool shouldRepaint(_BarPainter old) =>
      old.value != value || old.color != color || old.track != track;
}

/// Small stat card: icon, big value, label and optional caption/progress.
class StatCard extends StatelessWidget {
  const StatCard({
    super.key,
    required this.icon,
    required this.value,
    required this.label,
    this.caption,
    this.progress,
    this.tint,
    this.onTap,
  });

  final IconData icon;
  final String value;
  final String label;
  final String? caption;

  /// Optional 0..1 bar under the value.
  final double? progress;

  /// Container tint for the icon bubble (defaults to primaryContainer).
  final Color? tint;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    final content = Padding(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(AppSpacing.sm),
            decoration: BoxDecoration(
              color: tint ?? scheme.primaryContainer,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Icon(icon, size: 20, color: scheme.onPrimaryContainer),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(value, style: theme.textTheme.titleLarge),
          Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
          ),
          if (progress != null) ...[
            const SizedBox(height: AppSpacing.sm),
            ProgressBar(value: progress!, height: 6),
          ],
          if (caption != null) ...[
            const SizedBox(height: AppSpacing.xs),
            Text(
              caption!,
              style: theme.textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ],
        ],
      ),
    );

    return Card(
      child: Semantics(
        button: onTap != null,
        label: [label, value, if (caption != null) caption].join(', '),
        excludeSemantics: true,
        child: onTap == null ? content : InkWell(onTap: onTap, child: content),
      ),
    );
  }
}

/// Lays out [children] in 2 columns on phones (1 column at very large
/// text or very narrow widths, 3-4 on tablets). Rows size to content so
/// nothing overflows at 2x text.
class ResponsiveGrid extends StatelessWidget {
  const ResponsiveGrid({
    super.key,
    required this.children,
    this.minTileWidth = 150,
    this.spacing = AppSpacing.md,
  });

  final List<Widget> children;
  final double minTileWidth;
  final double spacing;

  @override
  Widget build(BuildContext context) {
    final scale = MediaQuery.textScalerOf(context).scale(1);
    return LayoutBuilder(builder: (context, constraints) {
      final minWidth = minTileWidth * (scale > 1.4 ? scale / 1.4 : 1);
      final columns = math.max(1, math.min(4, (constraints.maxWidth + spacing) ~/ (minWidth + spacing)));
      final rows = <Widget>[];
      for (var i = 0; i < children.length; i += columns) {
        final cells = <Widget>[];
        for (var c = 0; c < columns; c++) {
          if (c > 0) cells.add(SizedBox(width: spacing));
          final index = i + c;
          cells.add(Expanded(
            child: index < children.length ? children[index] : const SizedBox.shrink(),
          ));
        }
        if (rows.isNotEmpty) rows.add(SizedBox(height: spacing));
        // Equal heights per row, sized to the tallest tile's content.
        rows.add(IntrinsicHeight(
          child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: cells),
        ));
      }
      return Column(mainAxisSize: MainAxisSize.min, children: rows);
    });
  }
}
