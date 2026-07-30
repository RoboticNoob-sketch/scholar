import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Subtle top glow + safe-area wrapper for tab screens.
class ScreenBackdrop extends StatelessWidget {
  const ScreenBackdrop({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          stops: [0, 0.35, 1],
          colors: [
            Color(0xFF1A2E1F),
            AppTheme.page,
            AppTheme.page,
          ],
        ),
      ),
      child: SafeArea(bottom: false, child: child),
    );
  }
}

class AppNavShell extends StatelessWidget {
  const AppNavShell({
    super.key,
    required this.body,
    required this.selectedIndex,
    required this.onDestinationSelected,
    required this.destinations,
  });

  final Widget body;
  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;
  final List<NavigationDestination> destinations;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: ScreenBackdrop(child: body),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          border: Border(top: BorderSide(color: AppTheme.border)),
        ),
        child: NavigationBar(
          selectedIndex: selectedIndex,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          onDestinationSelected: onDestinationSelected,
          destinations: destinations,
        ),
      ),
    );
  }
}
