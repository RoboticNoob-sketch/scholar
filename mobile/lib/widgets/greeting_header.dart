import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class GreetingHeader extends StatelessWidget {
  const GreetingHeader({super.key, required this.name, this.badge});

  final String name;
  final Widget? badge;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Hello,', style: TextStyle(color: AppTheme.textSecondary, fontSize: 14)),
                const SizedBox(height: 2),
                Text(
                  name,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.3,
                      ),
                ),
              ],
            ),
          ),
          if (badge != null) badge!,
        ],
      ),
    );
  }
}
