import 'package:flutter/material.dart';
import 'staff_dashboard_screen.dart';
import 'staff_scanner_screen.dart';
import 'staff_claims_screen.dart';
import '../../widgets/app_nav_shell.dart';

class StaffMainShell extends StatefulWidget {
  const StaffMainShell({super.key, required this.onLogout});

  final VoidCallback onLogout;

  @override
  State<StaffMainShell> createState() => _StaffMainShellState();
}

class _StaffMainShellState extends State<StaffMainShell> {
  int _index = 0;
  int? _selectedBatchId;
  String _selectedBatchName = '';

  void _openScanner(int batchId, String batchName) {
    setState(() {
      _selectedBatchId = batchId;
      _selectedBatchName = batchName;
      _index = 1;
    });
  }

  @override
  Widget build(BuildContext context) {
    return AppNavShell(
      selectedIndex: _index,
      onDestinationSelected: (i) => setState(() => _index = i),
      destinations: const [
        NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Desk'),
        NavigationDestination(icon: Icon(Icons.qr_code_scanner_outlined), selectedIcon: Icon(Icons.qr_code_scanner), label: 'Scanner'),
        NavigationDestination(icon: Icon(Icons.list_alt_outlined), selectedIcon: Icon(Icons.list_alt), label: 'Claims'),
      ],
      body: IndexedStack(
        index: _index,
        children: [
          StaffDashboardScreen(onStartScan: _openScanner),
          StaffScannerScreen(
            isActive: _index == 1,
            batchId: _selectedBatchId,
            batchName: _selectedBatchName,
            onSelectBatch: () => setState(() => _index = 0),
          ),
          StaffClaimsScreen(onLogout: widget.onLogout),
        ],
      ),
    );
  }
}
