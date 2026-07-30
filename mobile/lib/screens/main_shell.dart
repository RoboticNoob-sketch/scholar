import 'package:flutter/material.dart';
import 'home_screen.dart';
import 'qr_screen.dart';
import 'history_screen.dart';
import 'profile_screen.dart';
import '../widgets/app_nav_shell.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.onLogout});

  final VoidCallback onLogout;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(onShowQr: () => setState(() => _index = 1)),
      const QrScreen(),
      const HistoryScreen(),
      ProfileScreen(onLogout: widget.onLogout),
    ];

    return AppNavShell(
      selectedIndex: _index,
      onDestinationSelected: (i) => setState(() => _index = i),
      destinations: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
        NavigationDestination(icon: Icon(Icons.qr_code_2_outlined), selectedIcon: Icon(Icons.qr_code_2), label: 'My QR'),
        NavigationDestination(icon: Icon(Icons.history_outlined), selectedIcon: Icon(Icons.history), label: 'History'),
        NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
      ],
      body: pages[_index],
    );
  }
}
