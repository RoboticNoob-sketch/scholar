import 'package:flutter/material.dart';
import '../models/student_voucher.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/empty_state.dart';
import '../widgets/greeting_header.dart';
import '../widgets/status_badge.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.onShowQr});

  final void Function(int voucherId) onShowQr;

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

  Widget _voucherCard(StudentVoucher voucher) {
    return AppCard(
      accentTop: AppTheme.accent,
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
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
                    Text(voucher.programName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                    const SizedBox(height: 4),
                    Text(voucher.batchName, style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                    const SizedBox(height: 2),
                    Text(
                      '${voucher.venue} · ${voucher.distributionDate}',
                      style: const TextStyle(fontSize: 11, color: AppTheme.textTertiary),
                    ),
                  ],
                ),
              ),
              StatusBadge(status: voucher.status),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            voucher.amountFormatted,
            style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: AppTheme.accent, letterSpacing: -0.5),
          ),
          if (voucher.claimedAt != null) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.check_circle_outline, size: 14, color: AppTheme.textSecondary),
                const SizedBox(width: 6),
                Text('Claimed ${voucher.claimedAt}', style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
              ],
            ),
          ],
          if (voucher.isPending) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => widget.onShowQr(voucher.voucherId),
                icon: const Icon(Icons.qr_code_2, size: 20),
                label: const Text('SHOW VOUCHER QR'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final vouchers = StudentVoucher.listFromStatus(_data);
    final pending = vouchers.where((v) => v.isPending).toList();
    final claimedOpen = vouchers.where((v) => !v.isPending).toList();

    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          GreetingHeader(
            name: _greetingName.split(' ').first,
            badge: pending.isNotEmpty
                ? StatusBadge(status: 'pending')
                : (vouchers.isNotEmpty ? StatusBadge(status: vouchers.first.status) : null),
          ),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_error != null)
            EmptyState(message: _error!, icon: Icons.cloud_off_outlined)
          else if (vouchers.isEmpty)
            const EmptyState(
              message: 'No active distribution batch right now.\nCheck back when the scholarship office opens a batch.',
              icon: Icons.event_busy_outlined,
            )
          else ...[
            if (pending.length > 1)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  'You qualify for ${pending.length} programs. Each program has its own voucher QR — show the correct one at the desk.',
                  style: TextStyle(fontSize: 12, color: AppTheme.textSecondary.withValues(alpha: 0.95), height: 1.45),
                ),
              ),
            ...pending.map(_voucherCard),
            ...claimedOpen.map(_voucherCard),
          ],
          if (pending.isNotEmpty) ...[
            const SizedBox(height: 4),
            AppCard(
              child: Row(
                children: [
                  const Icon(Icons.info_outline, size: 18, color: AppTheme.accent),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      pending.length > 1
                          ? 'Claim each program separately. Staff will scan the matching voucher QR for that program.'
                          : 'Bring your voucher QR to the distribution desk when claiming your assistance.',
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
