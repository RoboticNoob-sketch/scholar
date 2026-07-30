import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../models/student_voucher.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/page_header.dart';
import '../widgets/segment_tabs.dart';
import '../widgets/voucher_picker.dart';

class QrScreen extends StatefulWidget {
  const QrScreen({super.key, this.initialVoucherId});

  final int? initialVoucherId;

  @override
  State<QrScreen> createState() => _QrScreenState();
}

class _QrScreenState extends State<QrScreen> {
  int _tab = 0;
  Map<String, dynamic>? _data;
  String? _error;
  int? _selectedVoucherId;

  @override
  void initState() {
    super.initState();
    _selectedVoucherId = widget.initialVoucherId;
    _load();
  }

  @override
  void didUpdateWidget(covariant QrScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.initialVoucherId != null && widget.initialVoucherId != oldWidget.initialVoucherId) {
      setState(() => _selectedVoucherId = widget.initialVoucherId);
    }
  }

  Future<void> _load() async {
    setState(() => _error = null);
    final res = await ApiService.getStatus();
    if (!mounted) return;
    if (res['success'] == true) {
      final pending = StudentVoucher.listFromStatus(res).where((v) => v.isPending).toList();
      setState(() {
        _data = res;
        _selectedVoucherId = _resolveSelection(pending, _selectedVoucherId);
      });
    } else {
      setState(() {
        _data = null;
        _error = res['error']?.toString() ?? 'Could not load QR codes';
      });
    }
  }

  int? _resolveSelection(List<StudentVoucher> pending, int? preferredId) {
    if (pending.isEmpty) return null;
    if (preferredId != null && pending.any((v) => v.voucherId == preferredId)) {
      return preferredId;
    }
    return pending.first.voucherId;
  }

  StudentVoucher? _selectedVoucher(List<StudentVoucher> pending) {
    if (_selectedVoucherId == null) return pending.isNotEmpty ? pending.first : null;
    for (final voucher in pending) {
      if (voucher.voucherId == _selectedVoucherId) return voucher;
    }
    return pending.isNotEmpty ? pending.first : null;
  }

  @override
  Widget build(BuildContext context) {
    final profileQr = _data?['profile_qr']?.toString();
    final pending = StudentVoucher.listFromStatus(_data).where((v) => v.isPending).toList();
    final selected = _selectedVoucher(pending);

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
          if (_error != null)
            AppCard(
              margin: const EdgeInsets.only(bottom: 16),
              accentTop: AppTheme.negative,
              child: Text(_error!, style: const TextStyle(color: AppTheme.negative, fontSize: 13)),
            ),
          if (_tab == 0) ...[
            if (pending.length > 1) ...[
              VoucherPicker(
                vouchers: pending,
                selectedId: selected?.voucherId ?? pending.first.voucherId,
                onSelected: (id) => setState(() => _selectedVoucherId = id),
              ),
              const SizedBox(height: 16),
            ],
            _qrCard(
              data: selected?.voucherQr,
              title: selected != null ? 'Claim Voucher' : 'Voucher unavailable',
              subtitle: selected != null
                  ? '${selected.programName}\n${selected.batchName}'
                  : (pending.isEmpty
                      ? 'No pending vouchers in open batches'
                      : 'Select a program above'),
              hint: selected != null ? 'Show this voucher QR to staff at the ${selected.programName} desk' : null,
              size: 220,
            ),
          ] else
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
            Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, height: 1.35)),
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
