import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/page_header.dart';
import '../widgets/segment_tabs.dart';

class QrScreen extends StatefulWidget {
  const QrScreen({super.key});

  @override
  State<QrScreen> createState() => _QrScreenState();
}

class _QrScreenState extends State<QrScreen> {
  int _tab = 0;
  Map<String, dynamic>? _data;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final res = await ApiService.getStatus();
    if (mounted) setState(() => _data = res);
  }

  @override
  Widget build(BuildContext context) {
    final batch = _data?['current_batch'] as Map<String, dynamic>?;
    final profileQr = _data?['profile_qr']?.toString();
    final voucherQr = batch?['voucher_qr']?.toString();
    final voucherPending = batch?['voucher_status'] == 'pending';

    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          const PageHeader(title: 'My QR Codes', subtitle: 'Show at the distribution desk'),
          SegmentTabs(
            labels: const ['CLAIM VOUCHER', 'PROFILE'],
            index: _tab,
            onChanged: (i) => setState(() => _tab = i),
          ),
          const SizedBox(height: 20),
          if (_tab == 0)
            _qrCard(
              data: voucherPending ? voucherQr : null,
              title: voucherPending ? 'Claim Voucher' : 'Voucher unavailable',
              subtitle: voucherPending
                  ? (batch?['batch_name']?.toString() ?? '')
                  : (batch == null
                      ? 'No open batch right now'
                      : 'This voucher is already ${batch['voucher_status']}'),
              hint: voucherPending ? 'Show this voucher QR to staff at the desk' : null,
              size: 220,
            )
          else
            _qrCard(
              data: profileQr,
              title: 'Scholar Profile',
              subtitle: 'Identity verification',
              hint: 'For identity verification only',
              size: 180,
            ),
        ],
      ),
    );
  }

  Widget _qrCard({
    required String? data,
    required String title,
    required String subtitle,
    required double size,
    String? hint,
  }) {
    return AppCard(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          if (data != null)
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [BoxShadow(color: AppTheme.accent.withValues(alpha: 0.15), blurRadius: 24, offset: const Offset(0, 8))],
              ),
              child: QrImageView(data: data, size: size, backgroundColor: Colors.white),
            )
          else
            SizedBox(
              height: size + 28,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.qr_code_2_outlined, size: 48, color: AppTheme.textTertiary.withValues(alpha: 0.6)),
                    const SizedBox(height: 12),
                    Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.textSecondary)),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 20),
          Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          if (subtitle.isNotEmpty && data != null) ...[
            const SizedBox(height: 4),
            Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
          ],
          if (hint != null) ...[
            const SizedBox(height: 10),
            Text(hint, textAlign: TextAlign.center, style: const TextStyle(fontSize: 11, color: AppTheme.textTertiary, height: 1.4)),
          ],
        ],
      ),
    );
  }
}
