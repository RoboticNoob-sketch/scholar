import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'app_card.dart';

class KpiCard extends StatelessWidget {
  const KpiCard({
    super.key,
    required this.label,
    required this.value,
    this.highlight = false,
    this.icon,
  });

  final String label;
  final String value;
  final bool highlight;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      accentTop: highlight ? AppTheme.accent : null,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  label.toUpperCase(),
                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.textTertiary, letterSpacing: 0.8),
                ),
              ),
              if (icon != null) Icon(icon, size: 16, color: highlight ? AppTheme.accent : AppTheme.textTertiary),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              color: highlight ? AppTheme.accent : AppTheme.textPrimary,
              letterSpacing: -0.5,
            ),
          ),
        ],
      ),
    );
  }
}
