import 'package:flutter/material.dart';
import 'app_card.dart';
import '../theme/app_theme.dart';

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.message,
    this.icon = Icons.inbox_outlined,
    this.action,
  });

  final String message;
  final IconData icon;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
      child: Column(
        children: [
          Icon(icon, size: 40, color: AppTheme.textTertiary),
          const SizedBox(height: 14),
          Text(message, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.textSecondary, height: 1.4)),
          if (action != null) ...[const SizedBox(height: 18), action!],
        ],
      ),
    );
  }
}
