import 'package:flutter/material.dart';
import 'home_screen.dart';
import 'qr_screen.dart';
import 'history_screen.dart';
import 'profile_screen.dart';
import 'application_screen.dart';
import '../widgets/app_nav_shell.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.onLogout, required this.hasScholar});

  final VoidCallback onLogout;
  final bool hasScholar;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  int? _qrVoucherId;
  int _unreadApplicationUpdates = 0;
  late bool _hasScholar;

  @override
  void initState() {
    super.initState();
    _hasScholar = widget.hasScholar;
  }

  @override
  void didUpdateWidget(covariant MainShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.hasScholar != widget.hasScholar) _hasScholar = widget.hasScholar;
  }

  void _setScholarStatus(bool hasScholar) {
    if (_hasScholar == hasScholar) return;
    setState(() {
      _hasScholar = hasScholar;
      _index = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    final applicationIndex = _hasScholar ? 3 : 0;
    final profileIndex = _hasScholar ? 4 : 1;
    final pages = _hasScholar
        ? [
            HomeScreen(
              onShowQr: (voucherId) => setState(() {
                _qrVoucherId = voucherId;
                _index = 1;
              }),
            ),
            QrScreen(
              key: ValueKey(_qrVoucherId),
              initialVoucherId: _qrVoucherId,
            ),
            const HistoryScreen(),
            ApplicationScreen(
              isVisible: _index == applicationIndex,
              onUnreadChanged: (count) => setState(() => _unreadApplicationUpdates = count),
            ),
            ProfileScreen(
              onLogout: widget.onLogout,
              isVisible: _index == profileIndex,
              onScholarStatusChanged: _setScholarStatus,
            ),
          ]
        : [
            ApplicationScreen(
              isVisible: _index == applicationIndex,
              onUnreadChanged: (count) => setState(() => _unreadApplicationUpdates = count),
            ),
            ProfileScreen(
              onLogout: widget.onLogout,
              isVisible: _index == profileIndex,
              onScholarStatusChanged: _setScholarStatus,
            ),
          ];

    final progressDestination = NavigationDestination(
      icon: Badge(
        isLabelVisible: _unreadApplicationUpdates > 0,
        label: Text(_unreadApplicationUpdates > 9 ? '9+' : '$_unreadApplicationUpdates'),
        child: const Icon(Icons.assignment_outlined),
      ),
      selectedIcon: Badge(
        isLabelVisible: _unreadApplicationUpdates > 0,
        label: Text(_unreadApplicationUpdates > 9 ? '9+' : '$_unreadApplicationUpdates'),
        child: const Icon(Icons.assignment),
      ),
      label: 'Progress',
    );

    return AppNavShell(
      selectedIndex: _index,
      onDestinationSelected: (i) => setState(() => _index = i),
      destinations: _hasScholar
          ? [
              const NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
              const NavigationDestination(icon: Icon(Icons.qr_code_2_outlined), selectedIcon: Icon(Icons.qr_code_2), label: 'My QR'),
              const NavigationDestination(icon: Icon(Icons.history_outlined), selectedIcon: Icon(Icons.history), label: 'History'),
              progressDestination,
              const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
            ]
          : [
              progressDestination,
              const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
            ],
      body: pages[_index],
    );
  }
}
