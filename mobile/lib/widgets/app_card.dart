import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class AppCard extends StatelessWidget {
  const AppCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.accentTop,
    this.margin,
  });

  final Widget child;
  final EdgeInsets padding;
  final Color? accentTop;
  final EdgeInsets? margin;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: accentTop != null
          ? AppTheme.accentTopCard(accentTop!)
          : AppTheme.cardDecoration(),
      child: Padding(padding: padding, child: child),
    );
  }
}
