import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/session_service.dart';
import '../../theme/app_theme.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/page_header.dart';

class StaffClaimsScreen extends StatefulWidget {
  const StaffClaimsScreen({super.key, required this.onLogout});

  final VoidCallback onLogout;

  @override
  State<StaffClaimsScreen> createState() => _StaffClaimsScreenState();
}

class _StaffClaimsScreenState extends State<StaffClaimsScreen> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final res = await ApiService.getStaffClaimsToday();
    if (mounted) {
      setState(() {
        _items = (res['items'] as List?) ?? [];
        _loading = false;
      });
    }
  }

  Future<void> _logout() async {
    ApiService.token = null;
    ApiService.role = null;
    ApiService.username = null;
    await SessionService.clear();
    widget.onLogout();
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
            title: "Today's Claims",
            subtitle: 'Staff · ${ApiService.username ?? ''}',
          ),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_items.isEmpty)
            const EmptyState(message: 'No claims recorded today.', icon: Icons.receipt_long_outlined)
          else
            ..._items.map((item) {
              final m = item as Map<String, dynamic>;
              return AppCard(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: AppTheme.accent.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.check_circle_outline, color: AppTheme.accent, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(m['scholar_name']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
                          Text('${m['student_no']} · ${m['amount_formatted']}', style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                          Text('${m['batch_name']} · ${m['claimed_at_formatted']}', style: const TextStyle(fontSize: 11, color: AppTheme.textTertiary)),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            }),
          const SizedBox(height: 24),
          OutlinedButton.icon(onPressed: _logout, icon: const Icon(Icons.logout, size: 18), label: const Text('LOG OUT')),
        ],
      ),
    );
  }
}
