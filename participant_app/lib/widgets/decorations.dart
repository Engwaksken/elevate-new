import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';

/// Soft organic blobs drawn with bezier curves. Purely decorative: wrap in
/// [ExcludeSemantics] (the widgets below already do).
class SoftBlobsPainter extends CustomPainter {
  const SoftBlobsPainter({
    required this.colors,
    this.opacity = 0.35,
    this.seed = 0,
  });

  final List<Color> colors;
  final double opacity;

  /// Varies the arrangement between screens.
  final int seed;

  @override
  void paint(Canvas canvas, Size size) {
    if (size.isEmpty || colors.isEmpty) return;
    final w = size.width;
    final h = size.height;

    final specs = <(Offset, double, int)>[
      (Offset(w * (seed.isEven ? 0.92 : 0.08), h * 0.08), math.max(w, h) * 0.34, 0),
      (Offset(w * (seed.isEven ? 0.06 : 0.94), h * 0.92), math.max(w, h) * 0.28, 1),
      (Offset(w * 0.62, h * 0.55), math.min(w, h) * 0.18, 2),
    ];

    for (final (center, radius, index) in specs) {
      final paint = Paint()
        ..color = colors[index % colors.length].withValues(alpha: opacity)
        ..style = PaintingStyle.fill;
      canvas.drawPath(_blob(center, radius, index + seed), paint);
    }
  }

  /// A rounded, slightly irregular closed shape around [c].
  static Path _blob(Offset c, double r, int variant) {
    const points = 6;
    final radii = List.generate(points, (i) {
      final wobble = math.sin((i + 1) * (variant + 2) * 1.7) * 0.18;
      return r * (1 + wobble);
    });
    final pts = List.generate(points, (i) {
      final a = (math.pi * 2 / points) * i + variant * 0.4;
      return c + Offset(math.cos(a) * radii[i], math.sin(a) * radii[i]);
    });

    final path = Path();
    for (var i = 0; i < points; i++) {
      final p0 = pts[i];
      final p1 = pts[(i + 1) % points];
      final mid = Offset((p0.dx + p1.dx) / 2, (p0.dy + p1.dy) / 2);
      if (i == 0) {
        final prev = pts[points - 1];
        path.moveTo((prev.dx + p0.dx) / 2, (prev.dy + p0.dy) / 2);
      }
      path.quadraticBezierTo(p0.dx, p0.dy, mid.dx, mid.dy);
    }
    path.close();
    return path;
  }

  @override
  bool shouldRepaint(SoftBlobsPainter old) =>
      old.colors != colors || old.opacity != opacity || old.seed != seed;
}

/// Warm brand gradient with soft blobs and a gentle curved bottom edge.
/// Text placed in [child] should use [BrandColors.onHeader].
class GradientHeader extends StatelessWidget {
  const GradientHeader({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(AppSpacing.xl),
    this.borderRadius = const BorderRadius.all(Radius.circular(AppRadius.xl)),
    this.seed = 0,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final BorderRadius borderRadius;
  final int seed;

  @override
  Widget build(BuildContext context) {
    final brand = BrandColors.of(context);

    return ClipRRect(
      borderRadius: borderRadius,
      child: DecoratedBox(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: brand.headerGradient,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Stack(
          children: [
            Positioned.fill(
              child: ExcludeSemantics(
                child: CustomPaint(
                  painter: SoftBlobsPainter(
                    colors: [brand.blobA, brand.blobB, brand.blobA],
                    opacity: 0.22,
                    seed: seed,
                  ),
                ),
              ),
            ),
            Padding(padding: padding, child: child),
          ],
        ),
      ),
    );
  }
}

/// Very soft page background: a cream wash with faint blobs in the
/// corners, behind scrolling content.
class SoftBackground extends StatelessWidget {
  const SoftBackground({super.key, required this.child, this.seed = 0});

  final Widget child;
  final int seed;

  @override
  Widget build(BuildContext context) {
    final brand = BrandColors.of(context);
    final dark = Theme.of(context).brightness == Brightness.dark;

    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: brand.pageGradient,
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      child: Stack(
        children: [
          Positioned.fill(
            child: ExcludeSemantics(
              child: RepaintBoundary(
                child: CustomPaint(
                  painter: SoftBlobsPainter(
                    colors: [brand.blobC, brand.blobB, brand.blobC],
                    opacity: dark ? 0.25 : 0.55,
                    seed: seed,
                  ),
                ),
              ),
            ),
          ),
          child,
        ],
      ),
    );
  }
}
