import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/empty_state.dart';
import '../widgets/greeting_header.dart';
import '../widgets/status_badge.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.onShowQr});

  final VoidCallback onShowQr;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ApiService.getStatus();
      if (!mounted) return;
      if (res['success'] == true) {
        setState(() => _data = res);
      } else {
        setState(() => _error = res['error']?.toString() ?? 'Could not load status');
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'Connection failed. Pull to refresh.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String get _greetingName {
    final fromStatus = (_data?['scholar'] as Map?)?['full_name']?.toString();
    if (fromStatus != null && fromStatus.isNotEmpty) return fromStatus;
    final cached = ApiService.scholar?['full_name']?.toString();
    if (cached != null && cached.isNotEmpty) return cached;
    return _loading ? '...' : 'Scholar';
  }

  @override
  Widget build(BuildContext context) {
    final batch = _data?['current_batch'] as Map<String, dynamic>?;
    final status = batch?['voucher_status']?.toString() ?? 'none';

    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          GreetingHeader(
            name: _greetingName.split(' ').first,
            badge: batch != null ? StatusBadge(status: status) : null,
          ),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_error != null)
            EmptyState(message: _error!, icon: Icons.cloud_off_outlined)
          else if (batch == null)
            const EmptyState(
              message: 'No active distribution batch right now.\nCheck back when the scholarship office opens a batch.',
              icon: Icons.event_busy_outlined,
            )
          else
            AppCard(
              accentTop: AppTheme.accent,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppTheme.accent.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.card_giftcard_outlined, color: AppTheme.accent, size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(batch['batch_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                            const SizedBox(height: 2),
                            Text(
                              '${batch['venue']} · ${batch['distribution_date']}',
                              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Text(
                    batch['amount_formatted'] ?? '',
                    style: const TextStyle(fontSize: 32, fontWeight: FontWeight.w800, color: AppTheme.accent, letterSpacing: -0.5),
                  ),
                  Text(batch['program_name'] ?? '', style: const TextStyle(fontSize: 12, color: AppTheme.textTertiary)),
                  if (batch['claimed_at'] != null) ...[
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        const Icon(Icons.check_circle_outline, size: 14, color: AppTheme.textSecondary),
                        const SizedBox(width: 6),
                        Text('Claimed ${batch['claimed_at']}', style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                      ],
                    ),
                  ],
                  if (status == 'pending') ...[
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: widget.onShowQr,
                        icon: const Icon(Icons.qr_code_2, size: 20),
                        label: const Text('SHOW MY QR CODES'),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          if (batch != null && status == 'pending') ...[
            const SizedBox(height: 16),
            AppCard(
              child: Row(
                children: [
                  const Icon(Icons.info_outline, size: 18, color: AppTheme.accent),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Bring your voucher QR to the distribution desk when claiming your assistance.',
                      style: TextStyle(fontSize: 12, color: AppTheme.textSecondary.withValues(alpha: 0.95), height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
