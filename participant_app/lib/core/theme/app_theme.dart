import 'package:flutter/material.dart';

/// Brand palette, built around the WitU / ElevateHer360 logo.
///
/// Contrast (WCAG 2.x, measured against the surface each colour is used on):
/// * [brown] #5E2800 on white 11.8:1, on [cream] 11.2:1: safe for body text.
/// * [orange] #FF641A on white is only 2.96:1, so it is never used for
///   normal-size text on light surfaces. It is used for fills, icons, rings
///   and decorative shapes, always with [ink] text on top (5.96:1).
/// * [orangeText] #B23C00 on white 5.9:1, on [cream] 5.6:1: the text-safe
///   orange used as the light scheme's `secondary`.
/// * [rose] #C2185B on white 5.9:1: text-safe accent, used sparingly.
/// * [blush] #F8D7DA and [peach] #FFE8DC are container fills; text on them
///   uses [ink] or [roseInk] (both above 10:1).
abstract final class AppColors {
  // Core brand.
  static const Color brown = Color(0xFF5E2800);
  static const Color brownDeep = Color(0xFF3F1A00);
  static const Color orange = Color(0xFFFF641A);
  static const Color orangeText = Color(0xFFB23C00);

  // Supporting soft tones.
  static const Color peach = Color(0xFFFFE8DC);
  static const Color cream = Color(0xFFFFF8F3);
  static const Color blush = Color(0xFFF8D7DA);
  static const Color rose = Color(0xFFC2185B);
  static const Color roseInk = Color(0xFF5C0A2A);

  /// Darkest brown, for text on orange/peach fills (5.96:1 on [orange]).
  static const Color ink = Color(0xFF2B1200);

  // Warm dark theme surfaces (deep brown-plum, never grey).
  static const Color plumNight = Color(0xFF1D1114);
  static const Color plumSurface = Color(0xFF2A1A1C);

  /// Success / warning accents used by status pills and banners. Every
  /// pairing (foreground on container) exceeds 4.5:1 contrast.
  static const Color successContainerLight = Color(0xFFDDF3E4);
  static const Color onSuccessContainerLight = Color(0xFF0B3D1F);
  static const Color successContainerDark = Color(0xFF123A22);
  static const Color onSuccessContainerDark = Color(0xFFC4EED1);

  static const Color warningContainerLight = Color(0xFFFFE8B0);
  static const Color onWarningContainerLight = Color(0xFF3D2C00);
  static const Color warningContainerDark = Color(0xFF4A3800);
  static const Color onWarningContainerDark = Color(0xFFFFE8B0);

  /// Header gradient. White text on its lightest stop (#9A3F10) is 6.8:1.
  static const List<Color> headerGradient = [
    brownDeep,
    brown,
    Color(0xFF9A3F10),
  ];
}

/// 4-point spacing scale used everywhere instead of ad-hoc numbers.
abstract final class AppSpacing {
  static const double xxs = 2;
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 24;
  static const double xxl = 32;

  /// Standard horizontal page gutter.
  static const double page = 16;

  /// Minimum interactive size required by Android and iOS guidelines.
  static const double minTouchTarget = 48;

  /// Maximum readable line width for long-form text on tablets.
  static const double readableWidth = 720;

  static const EdgeInsets pagePadding = EdgeInsets.all(page);
  static const EdgeInsets listPadding =
      EdgeInsets.fromLTRB(page, sm, page, xl);
}

abstract final class AppRadius {
  static const double sm = 8;
  static const double md = 14;
  static const double lg = 20;
  static const double xl = 28;
  static const double pill = 999;
}

/// Brand colours that aren't part of [ColorScheme], resolved per brightness.
@immutable
class BrandColors extends ThemeExtension<BrandColors> {
  const BrandColors({
    required this.headerGradient,
    required this.onHeader,
    required this.blobA,
    required this.blobB,
    required this.blobC,
    required this.accentFill,
    required this.onAccentFill,
    required this.pageGradient,
  });

  /// Gradient for greeting, drawer and login headers; [onHeader] text on
  /// it meets AA.
  final List<Color> headerGradient;
  final Color onHeader;

  /// Soft decorative blob colours, always drawn translucent.
  final Color blobA;
  final Color blobB;
  final Color blobC;

  /// Orange fill (rings, progress bars) and the text colour used on it.
  final Color accentFill;
  final Color onAccentFill;

  /// Very soft full-page background wash.
  final List<Color> pageGradient;

  static BrandColors of(BuildContext context) =>
      Theme.of(context).extension<BrandColors>() ?? light;

  static const light = BrandColors(
    headerGradient: AppColors.headerGradient,
    onHeader: Colors.white,
    blobA: AppColors.orange,
    blobB: AppColors.blush,
    blobC: AppColors.peach,
    accentFill: AppColors.orange,
    onAccentFill: AppColors.ink,
    pageGradient: [AppColors.cream, Color(0xFFFFF0E8)],
  );

  static const dark = BrandColors(
    headerGradient: [Color(0xFF2A0F06), Color(0xFF4A1E08), Color(0xFF5E2A12)],
    onHeader: Color(0xFFFFF1E9),
    blobA: Color(0xFFB8491A),
    blobB: Color(0xFF6E2A44),
    blobC: Color(0xFF4A2A22),
    accentFill: Color(0xFFFF8A50),
    onAccentFill: AppColors.ink,
    pageGradient: [AppColors.plumNight, Color(0xFF231417)],
  );

  @override
  BrandColors copyWith() => this;

  @override
  BrandColors lerp(ThemeExtension<BrandColors>? other, double t) {
    if (other is! BrandColors) return this;
    Color l(Color a, Color b) => Color.lerp(a, b, t)!;
    return BrandColors(
      headerGradient: [
        for (var i = 0; i < headerGradient.length; i++)
          l(headerGradient[i], other.headerGradient[i]),
      ],
      onHeader: l(onHeader, other.onHeader),
      blobA: l(blobA, other.blobA),
      blobB: l(blobB, other.blobB),
      blobC: l(blobC, other.blobC),
      accentFill: l(accentFill, other.accentFill),
      onAccentFill: l(onAccentFill, other.onAccentFill),
      pageGradient: [
        l(pageGradient[0], other.pageGradient[0]),
        l(pageGradient[1], other.pageGradient[1]),
      ],
    );
  }
}

abstract final class AppTheme {
  static const String fontFamily = 'Rubik';

  static ColorScheme lightScheme() => ColorScheme.fromSeed(
        seedColor: AppColors.brown,
      ).copyWith(
        primary: AppColors.brown,
        onPrimary: Colors.white,
        primaryContainer: AppColors.peach,
        onPrimaryContainer: AppColors.ink,
        secondary: AppColors.orangeText,
        onSecondary: Colors.white,
        secondaryContainer: const Color(0xFFFFDCC8),
        onSecondaryContainer: AppColors.ink,
        tertiary: AppColors.rose,
        onTertiary: Colors.white,
        tertiaryContainer: AppColors.blush,
        onTertiaryContainer: AppColors.roseInk,
        surface: AppColors.cream,
        onSurface: const Color(0xFF2B1A12),
        onSurfaceVariant: const Color(0xFF5A4238),
        surfaceContainerLowest: Colors.white,
        surfaceContainerLow: const Color(0xFFFFF1E9),
        surfaceContainer: const Color(0xFFFCEBE1),
        surfaceContainerHigh: const Color(0xFFF8E4D9),
        surfaceContainerHighest: const Color(0xFFF3DDD1),
        outline: const Color(0xFF8A7065),
        outlineVariant: const Color(0xFFE6CFC4),
        surfaceTint: AppColors.orange,
      );

  static ColorScheme darkScheme() => ColorScheme.fromSeed(
        seedColor: AppColors.brown,
        brightness: Brightness.dark,
      ).copyWith(
        primary: const Color(0xFFFFB68F),
        onPrimary: const Color(0xFF4A1B00),
        primaryContainer: const Color(0xFF6B3000),
        onPrimaryContainer: const Color(0xFFFFDBC9),
        secondary: const Color(0xFFFF8A50),
        onSecondary: const Color(0xFF3A1200),
        secondaryContainer: const Color(0xFF5A2A10),
        onSecondaryContainer: const Color(0xFFFFDCC8),
        tertiary: const Color(0xFFFFB1C8),
        onTertiary: const Color(0xFF5E1133),
        tertiaryContainer: const Color(0xFF7B2949),
        onTertiaryContainer: const Color(0xFFFFD9E2),
        surface: AppColors.plumNight,
        onSurface: const Color(0xFFF6E6DF),
        onSurfaceVariant: const Color(0xFFD9C2B8),
        surfaceContainerLowest: const Color(0xFF170C0F),
        surfaceContainerLow: const Color(0xFF251619),
        surfaceContainer: AppColors.plumSurface,
        surfaceContainerHigh: const Color(0xFF352326),
        surfaceContainerHighest: const Color(0xFF402D2F),
        outline: const Color(0xFFA08C84),
        outlineVariant: const Color(0xFF4F3B36),
        surfaceTint: const Color(0xFFFF8A50),
      );

  static ThemeData light() => _build(lightScheme(), BrandColors.light);

  static ThemeData dark() => _build(darkScheme(), BrandColors.dark);

  static ThemeData _build(ColorScheme scheme, BrandColors brand) {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      fontFamily: fontFamily,
      materialTapTargetSize: MaterialTapTargetSize.padded,
      visualDensity: VisualDensity.standard,
    );

    final text = base.textTheme.copyWith(
      headlineMedium: base.textTheme.headlineMedium?.copyWith(
        fontWeight: FontWeight.w700,
      ),
      headlineSmall: base.textTheme.headlineSmall?.copyWith(
        fontWeight: FontWeight.w700,
      ),
      titleLarge: base.textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.w700,
      ),
      titleMedium: base.textTheme.titleMedium?.copyWith(
        fontWeight: FontWeight.w500,
      ),
      titleSmall: base.textTheme.titleSmall?.copyWith(
        fontWeight: FontWeight.w500,
      ),
      labelLarge: base.textTheme.labelLarge?.copyWith(
        fontWeight: FontWeight.w500,
      ),
      bodyLarge: base.textTheme.bodyLarge?.copyWith(height: 1.6),
      bodyMedium: base.textTheme.bodyMedium?.copyWith(height: 1.45),
    );

    final roundedMd = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
    );
    const stadium = StadiumBorder();

    const buttonMinSize = Size(64, AppSpacing.minTouchTarget);
    final dark = scheme.brightness == Brightness.dark;

    return base.copyWith(
      textTheme: text,
      extensions: [brand],
      scaffoldBackgroundColor: scheme.surface,
      appBarTheme: AppBarTheme(
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        surfaceTintColor: scheme.surfaceTint,
        scrolledUnderElevation: 2,
        centerTitle: false,
        titleTextStyle: text.titleLarge?.copyWith(color: scheme.onSurface),
      ),
      cardTheme: CardThemeData(
        elevation: dark ? 0 : 1.5,
        shadowColor: AppColors.brown.withValues(alpha: 0.18),
        margin: EdgeInsets.zero,
        color: dark ? scheme.surfaceContainer : scheme.surfaceContainerLowest,
        surfaceTintColor: Colors.transparent,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          side: BorderSide(
            color: scheme.outlineVariant.withValues(alpha: dark ? 1 : 0.6),
          ),
        ),
      ),
      listTileTheme: ListTileThemeData(
        minVerticalPadding: AppSpacing.sm,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg,
          vertical: AppSpacing.xs,
        ),
        iconColor: scheme.onSurfaceVariant,
        shape: roundedMd,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor:
            dark ? scheme.surfaceContainerHigh : scheme.surfaceContainerLowest,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: scheme.outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg,
          vertical: AppSpacing.lg,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: buttonMinSize,
          shape: stadium,
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
          textStyle: text.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: buttonMinSize,
          shape: stadium,
          side: BorderSide(color: scheme.outline),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(minimumSize: buttonMinSize),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: IconButton.styleFrom(
          minimumSize: const Size.square(AppSpacing.minTouchTarget),
        ),
      ),
      chipTheme: ChipThemeData(
        shape: stadium,
        side: BorderSide(color: scheme.outlineVariant),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        showCloseIcon: true,
        insetPadding: const EdgeInsets.fromLTRB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          AppSpacing.lg,
        ),
        shape: roundedMd,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor:
            dark ? scheme.surfaceContainer : scheme.surfaceContainerLowest,
        indicatorColor: scheme.secondaryContainer,
        surfaceTintColor: Colors.transparent,
        elevation: 3,
        shadowColor: AppColors.brown.withValues(alpha: 0.2),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      ),
      navigationDrawerTheme: NavigationDrawerThemeData(
        backgroundColor: scheme.surface,
        indicatorColor: scheme.secondaryContainer,
        tileHeight: 52,
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(
        color: dark ? scheme.secondary : AppColors.orange,
        linearTrackColor: scheme.surfaceContainerHighest,
        linearMinHeight: 8,
      ),
      dividerTheme: DividerThemeData(
        color: scheme.outlineVariant,
        space: 1,
      ),
      bottomSheetTheme: BottomSheetThemeData(
        showDragHandle: true,
        backgroundColor: scheme.surface,
        shape: const RoundedRectangleBorder(
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: scheme.surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.xl),
        ),
      ),
    );
  }
}
