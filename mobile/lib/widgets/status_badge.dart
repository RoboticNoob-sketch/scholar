import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class StatusBadge extends StatelessWidget {
  const StatusBadge({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (color, bg) = switch (status) {
      'claimed' || 'active' || 'open' => (AppTheme.accent, const Color(0x291ED760)),
      'pending' || 'draft' => (AppTheme.warning, const Color(0x29FFA42B)),
      'void' || 'expired' || 'inactive' || 'closed' => (AppTheme.negative, const Color(0x29F3727F)),
      _ => (AppTheme.textSecondary, AppTheme.elevated),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(
        status.isEmpty ? 'unknown' : status[0].toUpperCase() + status.substring(1),
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}
