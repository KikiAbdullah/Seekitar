import 'package:flutter/material.dart';

/// Tema terang tunggal untuk seluruh aplikasi.
///
/// Seekitar tidak memakai dark mode sama sekali — semua layar selalu dirender
/// dengan palet terang agar kontras teks konsisten dan form tetap terbaca.
class AppTheme {
  static const Color primary = Color(0xFF168A4A);
  static const Color primarySubtle = Color(0xFFE7F6EC);
  static const Color heroGradientStart = Color(0xFFE9FAF1);
  static const Color surfaceWhite = Color(0xFFFFFFFF);
  static const Color backgroundWarm = Color(0xFFF8FAF9);

  static ThemeData get light => _buildLight();

  static ThemeData _buildLight() {
    final cs = ColorScheme.fromSeed(seedColor: primary, brightness: Brightness.light);

    return ThemeData(
      useMaterial3: true,
      colorScheme: cs,
      brightness: Brightness.light,
      fontFamily: 'Plus Jakarta Sans',

      textTheme: const TextTheme(
        bodyMedium: TextStyle(color: Color(0xFF1B1F1C), height: 1.4),
        bodyLarge: TextStyle(color: Color(0xFF1B1F1C), height: 1.4),
        bodySmall: TextStyle(color: Color(0xFF5C665F), height: 1.35),
      ),

      appBarTheme: AppBarTheme(
        centerTitle: false, elevation: 0, scrolledUnderElevation: 0.5,
        backgroundColor: surfaceWhite, surfaceTintColor: Colors.transparent,
        foregroundColor: cs.onSurface,
        iconTheme: const IconThemeData(color: Color(0xFF1B1F1C)),
        titleTextStyle: TextStyle(color: cs.onSurface, fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -0.3),
      ),

      cardTheme: CardThemeData(
        elevation: 0, color: surfaceWhite, surfaceTintColor: Colors.transparent,
        shadowColor: Colors.black.withOpacity(0.04),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      ),

      elevatedButtonTheme: ElevatedButtonThemeData(style: ElevatedButton.styleFrom(
        backgroundColor: primary, foregroundColor: Colors.white, elevation: 0,
        disabledBackgroundColor: const Color(0xFFD4DAD5),
        disabledForegroundColor: const Color(0xFF8A918C),
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, letterSpacing: -0.2),
      )),

      outlinedButtonTheme: OutlinedButtonThemeData(style: OutlinedButton.styleFrom(
        foregroundColor: primary,
        side: BorderSide(color: primary.withOpacity(0.4), width: 1.5),
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
      )),

      textButtonTheme: TextButtonThemeData(style: TextButton.styleFrom(
        foregroundColor: primary,
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
      )),

      inputDecorationTheme: InputDecorationTheme(
        filled: true, fillColor: surfaceWhite,
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        labelStyle: const TextStyle(color: Color(0xFF5C665F), fontWeight: FontWeight.w600),
        hintStyle: const TextStyle(color: Color(0xFF9AA39D)),
        errorStyle: const TextStyle(color: Color(0xFFB3261E), fontWeight: FontWeight.w600),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFFDFE5E0))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: primary, width: 2)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFFB3261E), width: 1.5)),
        focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFFB3261E), width: 2)),
      ),

      navigationBarTheme: NavigationBarThemeData(
        elevation: 0, height: 72, backgroundColor: surfaceWhite, surfaceTintColor: Colors.transparent,
        indicatorColor: primarySubtle,
        indicatorShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith((s) => IconThemeData(color: s.contains(WidgetState.selected) ? primary : Colors.grey.shade500, size: 24)),
        labelTextStyle: WidgetStateProperty.resolveWith((s) => TextStyle(color: s.contains(WidgetState.selected) ? primary : Colors.grey.shade600, fontSize: 10.5, fontWeight: s.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500)),
      ),

      chipTheme: ChipThemeData(
        backgroundColor: Colors.grey.shade100, selectedColor: primary,
        labelStyle: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF1B1F1C)),
        secondaryLabelStyle: const TextStyle(fontSize: 13, color: Colors.white, fontWeight: FontWeight.w600),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        side: BorderSide.none, padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      ),

      tabBarTheme: TabBarThemeData(
        labelColor: primary, unselectedLabelColor: Colors.grey.shade600,
        indicatorColor: primary, indicatorSize: TabBarIndicatorSize.label,
        labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      ),

      bottomSheetTheme: BottomSheetThemeData(backgroundColor: surfaceWhite, surfaceTintColor: Colors.transparent, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28)))),
      scaffoldBackgroundColor: backgroundWarm,
      dialogTheme: DialogThemeData(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)), elevation: 0, backgroundColor: surfaceWhite, surfaceTintColor: Colors.transparent),
      snackBarTheme: SnackBarThemeData(behavior: SnackBarBehavior.floating, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)), elevation: 1, backgroundColor: const Color(0xFF1B1F1C), contentTextStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
      dividerTheme: DividerThemeData(color: cs.outlineVariant.withOpacity(0.5), thickness: 1, space: 0),
      progressIndicatorTheme: ProgressIndicatorThemeData(color: primary, linearTrackColor: primarySubtle),
      floatingActionButtonTheme: FloatingActionButtonThemeData(backgroundColor: primary, foregroundColor: Colors.white, elevation: 2),
    );
  }
}
