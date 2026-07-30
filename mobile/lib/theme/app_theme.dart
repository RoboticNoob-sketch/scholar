import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const accent = Color(0xFF1ED760);
  static const accentDark = Color(0xFF04250F);
  static const page = Color(0xFF121212);
  static const card = Color(0xFF181818);
  static const cardAlt = Color(0xFF1F1F1F);
  static const elevated = Color(0xFF252525);
  static const border = Color(0x14FFFFFF);
  static const textPrimary = Colors.white;
  static const textSecondary = Color(0xFFB3B3B3);
  static const textTertiary = Color(0xFF737373);
  static const warning = Color(0xFFFFA42B);
  static const negative = Color(0xFFF3727F);

  static TextStyle get _font => GoogleFonts.workSans();

  static BoxDecoration cardDecoration({Color? accentBar}) {
    return BoxDecoration(
      color: card,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: border),
      boxShadow: accentBar != null
          ? [BoxShadow(color: accentBar.withValues(alpha: 0.12), blurRadius: 24, offset: const Offset(0, 8))]
          : null,
    );
  }

  static BoxDecoration accentTopCard(Color bar) {
    return BoxDecoration(
      color: card,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: border),
      gradient: LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        stops: const [0, 0.03, 1],
        colors: [bar, card, card],
      ),
    );
  }

  static ThemeData get dark {
    final base = GoogleFonts.workSansTextTheme(ThemeData.dark().textTheme);
    return ThemeData(
      brightness: Brightness.dark,
      scaffoldBackgroundColor: page,
      colorScheme: const ColorScheme.dark(
        primary: accent,
        onPrimary: accentDark,
        surface: card,
        onSurface: textPrimary,
      ),
      textTheme: base.apply(
        bodyColor: textPrimary,
        displayColor: textPrimary,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: page,
        foregroundColor: textPrimary,
        elevation: 0,
        titleTextStyle: _font.copyWith(fontSize: 18, fontWeight: FontWeight.w800),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: elevated,
        labelStyle: _font.copyWith(color: textSecondary, fontSize: 13),
        hintStyle: _font.copyWith(color: textTertiary),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: accent, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: accent,
          foregroundColor: accentDark,
          elevation: 0,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: _font.copyWith(fontWeight: FontWeight.w700, letterSpacing: 1.1, fontSize: 13),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: textPrimary,
          side: const BorderSide(color: border),
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: _font.copyWith(fontWeight: FontWeight.w700, letterSpacing: 1, fontSize: 12),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: accent,
          textStyle: _font.copyWith(fontWeight: FontWeight.w600, fontSize: 13),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: card,
        indicatorColor: accent.withValues(alpha: 0.14),
        elevation: 0,
        height: 68,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return _font.copyWith(
            fontSize: 11,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? accent : textSecondary,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: selected ? accent : textSecondary,
            size: 22,
          );
        }),
      ),
      dividerColor: border,
      splashColor: accent.withValues(alpha: 0.08),
      highlightColor: accent.withValues(alpha: 0.05),
    );
  }
}
