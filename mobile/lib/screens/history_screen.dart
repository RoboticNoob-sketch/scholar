import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/empty_state.dart';
import '../widgets/page_header.dart';
import '../widgets/status_badge.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final res = await ApiService.getHistory();
    if (mounted) {
      setState(() {
        _items = (res['items'] as List?) ?? [];
        _loading = false;
      });
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
          PageHeader(title: 'Claim History', subtitle: '${_items.length} record${_items.length == 1 ? '' : 's'}'),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_items.isEmpty)
            const EmptyState(message: 'No claim history yet.\nYour redeemed vouchers will appear here.', icon: Icons.history)
          else
            ..._items.map((item) {
              final map = item as Map<String, dynamic>;
              return AppCard(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(16),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: AppTheme.accent.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.receipt_long_outlined, color: AppTheme.accent, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(child: Text(map['batch_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700))),
                              StatusBadge(status: map['status']?.toString() ?? ''),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            map['amount_formatted']?.toString() ?? '',
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.accent),
                          ),
                          Text(
                            '${map['program_name']} · ${map['date_formatted']}',
                            style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                          ),
                        ],
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
