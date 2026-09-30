import 'package:flutter/material.dart';

/// Brand palette. Gold is decorative only: it does not meet WCAG AA as
/// text on light surfaces, so never use it for body text on white.
abstract final class AppColors {
  static const Color maroon = Color(0xFF800000);
  static const Color maroonDark = Color(0xFF4F0000);
  static const Color gold = Color(0xFFD4AF37);

  /// Success / offline accents used by status banners. Both pairings
  /// (foreground on container) exceed 4.5:1 contrast.
  static const Color successContainerLight = Color(0xFFDDF3E4);
  static const Color onSuccessContainerLight = Color(0xFF0B3D1F);
  static const Color successContainerDark = Color(0xFF123A22);
  static const Color onSuccessContainerDark = Color(0xFFC4EED1);

  static const Color warningContainerLight = Color(0xFFFFE8B0);
  static const Color onWarningContainerLight = Color(0xFF3D2C00);
  static const Color warningContainerDark = Color(0xFF4A3800);
  static const Color onWarningContainerDark = Color(0xFFFFE8B0);
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
  static const double md = 12;
  static const double lg = 16;
}

abstract final class AppTheme {
  static ThemeData light() => _build(
        ColorScheme.fromSeed(
          seedColor: AppColors.maroon,
          primary: AppColors.maroon,
          onPrimary: Colors.white,
        ),
      );

  static ThemeData dark() => _build(
        ColorScheme.fromSeed(
          seedColor: AppColors.maroon,
          brightness: Brightness.dark,
        ),
      );

  static ThemeData _build(ColorScheme scheme) {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
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
        fontWeight: FontWeight.w600,
      ),
      bodyLarge: base.textTheme.bodyLarge?.copyWith(height: 1.6),
      bodyMedium: base.textTheme.bodyMedium?.copyWith(height: 1.45),
    );

    final roundedMd = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
    );

    const buttonMinSize = Size(64, AppSpacing.minTouchTarget);

    return base.copyWith(
      textTheme: text,
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
        elevation: 0,
        margin: EdgeInsets.zero,
        color: scheme.surfaceContainerLow,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          side: BorderSide(color: scheme.outlineVariant),
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
        fillColor: scheme.surfaceContainerHighest.withValues(alpha: 0.5),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: scheme.outline),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.lg,
          vertical: AppSpacing.lg,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: buttonMinSize,
          shape: roundedMd,
          textStyle: text.labelLarge?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: buttonMinSize,
          shape: roundedMd,
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
        indicatorColor: scheme.secondaryContainer,
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(
        linearTrackColor: scheme.surfaceContainerHighest,
        linearMinHeight: 6,
      ),
      dividerTheme: DividerThemeData(
        color: scheme.outlineVariant,
        space: 1,
      ),
      bottomSheetTheme: const BottomSheetThemeData(showDragHandle: true),
    );
  }
}
