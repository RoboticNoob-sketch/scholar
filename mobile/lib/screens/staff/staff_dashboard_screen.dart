import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/kpi_card.dart';
import '../../widgets/page_header.dart';

class StaffDashboardScreen extends StatefulWidget {
  const StaffDashboardScreen({super.key, required this.onStartScan});

  final void Function(int batchId, String batchName) onStartScan;

  @override
  State<StaffDashboardScreen> createState() => _StaffDashboardScreenState();
}

class _StaffDashboardScreenState extends State<StaffDashboardScreen> {
  List<dynamic> _batches = [];
  int _claimsToday = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final batches = await ApiService.getOpenBatches();
      final claims = await ApiService.getStaffClaimsToday();
      if (mounted) {
        setState(() {
          _batches = (batches['items'] as List?) ?? [];
          _claimsToday = (claims['count'] as int?) ?? 0;
        });
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          PageHeader(
            title: 'Distribution Desk',
            subtitle: 'Staff · ${ApiService.username ?? ''}',
          ),
          Row(
            children: [
              Expanded(child: KpiCard(label: 'Scanned today', value: '$_claimsToday', highlight: true, icon: Icons.qr_code_scanner)),
              const SizedBox(width: 12),
              Expanded(child: KpiCard(label: 'Open batches', value: '${_batches.length}', icon: Icons.inventory_2_outlined)),
            ],
          ),
          const SizedBox(height: 24),
          const Text('SELECT BATCH TO SCAN', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppTheme.textTertiary, letterSpacing: 0.8)),
          const SizedBox(height: 12),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_batches.isEmpty)
            const EmptyState(
              message: 'No open batches.\nAsk admin to open a distribution batch.',
              icon: Icons.event_busy_outlined,
            )
          else
            ..._batches.map((b) {
              final map = b as Map<String, dynamic>;
              return AppCard(
                margin: const EdgeInsets.only(bottom: 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: AppTheme.accent.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.local_shipping_outlined, color: AppTheme.accent, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(map['name']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                              Text('${map['program_name']} · ${map['venue']}', style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: () => widget.onStartScan(map['id'] as int, map['name']?.toString() ?? ''),
                        icon: const Icon(Icons.qr_code_scanner, size: 18),
                        label: const Text('START SCANNING'),
                      ),
                    ),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }
}
