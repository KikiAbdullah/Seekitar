import 'package:flutter/material.dart';

class AppTheme {
  static const Color primary = Color(0xFF168A4A);
  static const Color primarySubtle = Color(0xFFE7F6EC);
  static const Color heroGradientStart = Color(0xFFE9FAF1);
  static const Color surfaceWhite = Color(0xFFFFFFFF);
  static const Color backgroundWarm = Color(0xFFF8FAF9);

  static ThemeData get light => _build(Brightness.light);
  static ThemeData get dark => _build(Brightness.dark);

  static ThemeData _build(Brightness b) {
    final isDark = b == Brightness.dark;
    final cs = ColorScheme.fromSeed(seedColor: primary, brightness: b);

    return ThemeData(
      useMaterial3: true,
      colorScheme: cs,
      brightness: b,
      fontFamily: 'Plus Jakarta Sans',

      appBarTheme: AppBarTheme(
        centerTitle: false, elevation: 0, scrolledUnderElevation: 0.5,
        backgroundColor: isDark ? cs.surface : surfaceWhite, surfaceTintColor: Colors.transparent,
        foregroundColor: cs.onSurface,
        titleTextStyle: TextStyle(color: cs.onSurface, fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.3),
      ),

      cardTheme: CardThemeData(
        elevation: 0, color: cs.surface, surfaceTintColor: Colors.transparent,
        shadowColor: Colors.black.withOpacity(isDark ? 0 : 0.04),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      ),

      elevatedButtonTheme: ElevatedButtonThemeData(style: ElevatedButton.styleFrom(
        backgroundColor: cs.primary, foregroundColor: cs.onPrimary, elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, letterSpacing: -0.2),
      )),

      outlinedButtonTheme: OutlinedButtonThemeData(style: OutlinedButton.styleFrom(
        foregroundColor: cs.primary,
        side: BorderSide(color: cs.primary.withOpacity(0.3), width: 1.5),
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
      )),

      inputDecorationTheme: InputDecorationTheme(
        filled: true, fillColor: isDark ? cs.surfaceContainerHighest : Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: isDark ? Colors.grey.shade800 : Colors.grey.shade200)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: primary, width: 2)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: cs.error, width: 1.5)),
      ),

      navigationBarTheme: NavigationBarThemeData(
        elevation: 0, height: 72, backgroundColor: cs.surface, surfaceTintColor: Colors.transparent,
        indicatorColor: isDark ? cs.primary.withOpacity(0.2) : primarySubtle,
        indicatorShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith((s) => IconThemeData(color: s.contains(WidgetState.selected) ? cs.primary : Colors.grey.shade500, size: 24)),
        labelTextStyle: WidgetStateProperty.resolveWith((s) => TextStyle(color: s.contains(WidgetState.selected) ? cs.primary : Colors.grey.shade500, fontSize: 10.5, fontWeight: s.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500)),
      ),

      chipTheme: ChipThemeData(
        backgroundColor: isDark ? cs.surfaceContainerHighest : Colors.grey.shade100, selectedColor: cs.primary,
        labelStyle: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
        secondaryLabelStyle: const TextStyle(fontSize: 13, color: Colors.white, fontWeight: FontWeight.w600),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        side: BorderSide.none, padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      ),

      tabBarTheme: TabBarThemeData(
        labelColor: cs.primary, unselectedLabelColor: Colors.grey.shade500,
        indicatorColor: cs.primary, indicatorSize: TabBarIndicatorSize.label,
        labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
      ),

      bottomSheetTheme: BottomSheetThemeData(backgroundColor: cs.surface, surfaceTintColor: Colors.transparent, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28)))),
      scaffoldBackgroundColor: isDark ? cs.surface : backgroundWarm,
      dialogTheme: DialogThemeData(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)), elevation: 0, backgroundColor: cs.surface, surfaceTintColor: Colors.transparent),
      snackBarTheme: SnackBarThemeData(behavior: SnackBarBehavior.floating, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)), elevation: 1),
      dividerTheme: DividerThemeData(color: cs.outlineVariant.withOpacity(0.5), thickness: 1, space: 0),
    );
  }
}
