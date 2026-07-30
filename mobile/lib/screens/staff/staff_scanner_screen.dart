import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../services/api_service.dart';
import '../../theme/app_theme.dart';
import '../../utils/qr_payload.dart';
import '../../widgets/app_card.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/page_header.dart';
import '../../widgets/segment_tabs.dart';

enum ScanMode { voucher, profile }

class StaffScannerScreen extends StatefulWidget {
  const StaffScannerScreen({
    super.key,
    required this.batchId,
    required this.batchName,
    required this.onSelectBatch,
    this.isActive = true,
  });

  final int? batchId;
  final String batchName;
  final VoidCallback onSelectBatch;
  final bool isActive;

  @override
  State<StaffScannerScreen> createState() => _StaffScannerScreenState();
}

class _StaffScannerScreenState extends State<StaffScannerScreen> with WidgetsBindingObserver {
  final MobileScannerController _controller = MobileScannerController(
    autoStart: false,
    detectionSpeed: DetectionSpeed.normal,
    facing: CameraFacing.back,
  );

  ScanMode _mode = ScanMode.voucher;
  bool _showScanner = false;
  bool _processing = false;
  DateTime? _lastScanAt;
  String? _resultTitle;
  String? _resultDetail;
  bool _success = false;
  bool _torchOn = false;
  List<dynamic> _recent = [];
  String? _verificationToken;
  String? _verifiedScholarName;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    if (widget.batchId != null) _loadRecent();
    if (widget.isActive) {
      _showScanner = true;
      _scheduleStart();
    }
  }

  @override
  void didUpdateWidget(covariant StaffScannerScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.batchId != null && widget.batchId != oldWidget.batchId) {
      _loadRecent();
    }
    if (widget.isActive != oldWidget.isActive) {
      if (widget.isActive) {
        setState(() => _showScanner = true);
        _scheduleStart();
      } else {
        unawaited(_stopScanner());
        setState(() => _showScanner = false);
      }
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!_controller.value.hasCameraPermission) return;

    switch (state) {
      case AppLifecycleState.detached:
      case AppLifecycleState.hidden:
      case AppLifecycleState.paused:
        return;
      case AppLifecycleState.resumed:
        if (widget.isActive && _showScanner) {
          _scheduleStart();
        }
      case AppLifecycleState.inactive:
        unawaited(_stopScanner());
    }
  }

  void _scheduleStart() {
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted || !widget.isActive || !_showScanner) return;
      try {
        if (!_controller.value.isRunning) {
          await _controller.start();
        }
      } catch (_) {}
    });
  }

  Future<void> _stopScanner() async {
    try {
      if (_controller.value.isRunning) {
        await _controller.stop();
      }
    } catch (_) {}
  }

  Future<void> _retryCamera() async {
    setState(() {
      _resultTitle = null;
      _resultDetail = null;
    });
    await _stopScanner();
    if (mounted && widget.isActive) {
      _scheduleStart();
    }
  }

  Future<void> _loadRecent() async {
    if (widget.batchId == null) return;
    final res = await ApiService.getRecentClaims(widget.batchId!);
    if (mounted) setState(() => _recent = (res['items'] as List?) ?? []);
  }

  Future<void> _handleScan(String raw) async {
    if (_processing) return;
    final payload = normalizeQrPayload(raw);
    if (payload.isEmpty) return;
    final now = DateTime.now();
    if (_lastScanAt != null && now.difference(_lastScanAt!) < const Duration(seconds: 2)) return;
    _lastScanAt = now;
    setState(() {
      _processing = true;
      _resultTitle = null;
      _resultDetail = null;
    });

    try {
      if (_mode == ScanMode.voucher) {
        if (widget.batchId == null) {
          setState(() {
            _success = false;
            _resultTitle = 'No batch selected';
            _resultDetail = 'Go to Desk tab and select an open batch.';
          });
          return;
        }
        if (_verificationToken == null) {
          await _feedback(success: false);
          setState(() {
            _success = false;
            _resultTitle = 'Profile verification required';
            _resultDetail = 'Switch to Profile Verify, scan the scholar QR, then scan the voucher.';
          });
          return;
        }
        final res = await ApiService.redeemVoucher(
          batchId: widget.batchId!,
          payload: payload,
          verificationToken: _verificationToken,
        );
        if (res['success'] == true) {
          final scholar = res['scholar'] as Map?;
          await _feedback(success: true);
          setState(() {
            _success = true;
            _resultTitle = res['message']?.toString() ?? 'Claim recorded';
            _resultDetail = '${scholar?['name']} · ${scholar?['student_no']} · ${res['amount']}';
            _verificationToken = null;
            _verifiedScholarName = null;
          });
          _loadRecent();
        } else {
          await _feedback(success: false);
          final code = res['code']?.toString();
          final clearVerify = code == 'verification_invalid';
          final wrongType = code == 'wrong_qr_type';
          setState(() {
            _success = false;
            _resultTitle = res['error']?.toString() ?? 'Scan failed';
            _resultDetail = wrongType
                ? 'Use Profile Verify for profile QR, then Voucher Scan for the claim voucher.'
                : clearVerify
                    ? 'Go to Profile Verify and scan the same scholar again, then scan their voucher.'
                    : 'Try again or verify the voucher QR.';
            if (clearVerify) {
              _verificationToken = null;
              _verifiedScholarName = null;
            }
          });
        }
      } else {
        final res = await ApiService.verifyProfile(payload, batchId: widget.batchId);
        if (res['success'] == true) {
          final scholar = res['scholar'] as Map?;
          await _feedback(success: true);
          setState(() {
            _success = true;
            _resultTitle = res['message']?.toString() ?? 'Profile verified';
            _resultDetail = '${scholar?['name']} · ${scholar?['student_no']}';
            _verificationToken = res['verification_token']?.toString();
            _verifiedScholarName = scholar?['name']?.toString();
            _mode = ScanMode.voucher;
          });
        } else {
          await _feedback(success: false);
          final wrongType = res['code']?.toString() == 'wrong_qr_type';
          setState(() {
            _success = false;
            _resultTitle = res['error']?.toString() ?? 'Verification failed';
            _resultDetail = wrongType
                ? 'Switch to Voucher Scan for claim voucher QR codes.'
                : 'Ask the scholar to open My QR → Profile tab and pull down to refresh.';
            _verificationToken = null;
            _verifiedScholarName = null;
          });
        }
      }
    } catch (_) {
      await _feedback(success: false);
      setState(() {
        _success = false;
        _resultTitle = 'Connection error';
        _resultDetail = 'Check server and pull to refresh desk.';
      });
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  Future<void> _feedback({required bool success}) async {
    if (success) {
      await HapticFeedback.mediumImpact();
      await SystemSound.play(SystemSoundType.click);
    } else {
      await HapticFeedback.heavyImpact();
    }
  }

  Future<void> _toggleTorch() async {
    await _controller.toggleTorch();
    if (mounted) setState(() => _torchOn = !_torchOn);
  }

  Widget _buildCameraPane() {
    if (!_showScanner) {
      return const Center(
        child: Text('Camera paused', style: TextStyle(color: Colors.white54, fontSize: 13)),
      );
    }

    return MobileScanner(
      controller: _controller,
      fit: BoxFit.cover,
      errorBuilder: (context, error) {
        final message = switch (error.errorCode) {
          MobileScannerErrorCode.permissionDenied => 'Camera permission denied. Enable it in Settings.',
          _ => error.errorDetails?.message ?? 'Camera failed to start',
        };
        return Center(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: Colors.white70, size: 28),
                const SizedBox(height: 10),
                Text(message, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: 12),
                OutlinedButton(
                  onPressed: _retryCamera,
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.white),
                  child: const Text('RETRY'),
                ),
              ],
            ),
          ),
        );
      },
      onDetect: (capture) {
        final code = capture.barcodes.firstOrNull?.rawValue;
        if (code != null && code.isNotEmpty) _handleScan(code);
      },
    );
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    unawaited(_controller.dispose());
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.batchId == null && _mode == ScanMode.voucher) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: EmptyState(
            icon: Icons.qr_code_scanner_outlined,
            message: 'Select a batch first.\nOpen the Desk tab and tap START SCANNING on an open batch.',
            action: ElevatedButton(onPressed: widget.onSelectBatch, child: const Text('GO TO DESK')),
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              PageHeader(title: 'QR Scanner', subtitle: widget.batchName.isNotEmpty ? widget.batchName : 'Profile verify mode'),
              SegmentTabs(
                labels: const ['VOUCHER SCAN', 'PROFILE VERIFY'],
                index: _mode == ScanMode.voucher ? 0 : 1,
                onChanged: (i) => setState(() {
                  _mode = i == 0 ? ScanMode.voucher : ScanMode.profile;
                  _resultTitle = null;
                }),
              ),
              if (_verifiedScholarName != null) ...[
                const SizedBox(height: 12),
                AppCard(
                  accentTop: AppTheme.accent,
                  child: Row(
                    children: [
                      const Icon(Icons.verified_user_outlined, color: AppTheme.accent, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Verified: $_verifiedScholarName', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                            const Text('Scan voucher within 5 minutes', style: TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 16),
              AppCard(
                padding: EdgeInsets.zero,
                child: Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: SizedBox(
                        height: 280,
                        child: ColoredBox(
                          color: Colors.black,
                          child: _buildCameraPane(),
                        ),
                      ),
                    ),
                    if (_showScanner)
                      Positioned(
                        top: 12,
                        right: 12,
                        child: Material(
                          color: Colors.black54,
                          borderRadius: BorderRadius.circular(999),
                          child: IconButton(
                            icon: Icon(_torchOn ? Icons.flash_on : Icons.flash_off, color: Colors.white),
                            tooltip: 'Toggle torch',
                            onPressed: _toggleTorch,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              if (_processing)
                const Padding(padding: EdgeInsets.all(12), child: Center(child: CircularProgressIndicator()))
              else if (_resultTitle != null)
                AppCard(
                  margin: const EdgeInsets.only(top: 16),
                  accentTop: _success ? AppTheme.accent : AppTheme.negative,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(_success ? Icons.check_circle_outline : Icons.error_outline, color: _success ? AppTheme.accent : AppTheme.negative, size: 20),
                          const SizedBox(width: 8),
                          Expanded(child: Text(_resultTitle!, style: TextStyle(fontWeight: FontWeight.w700, color: _success ? AppTheme.accent : AppTheme.negative))),
                        ],
                      ),
                      if (_resultDetail != null) ...[
                        const SizedBox(height: 8),
                        Text(_resultDetail!, style: const TextStyle(fontSize: 13, color: AppTheme.textSecondary)),
                      ],
                    ],
                  ),
                ),
              const SizedBox(height: 16),
              const Text('RECENT AT THIS BATCH', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppTheme.textTertiary, letterSpacing: 0.8)),
              const SizedBox(height: 10),
            ],
          ),
        ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
            children: [
              if (_recent.isEmpty)
                const Text('No claims yet.', style: TextStyle(color: AppTheme.textSecondary, fontSize: 13))
              else
                ..._recent.map((item) {
                  final m = item as Map<String, dynamic>;
                  return AppCard(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle, color: AppTheme.accent, size: 18),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(m['name']?.toString() ?? '', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                              Text(m['time']?.toString() ?? '', style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                }),
            ],
          ),
        ),
      ],
    );
  }
}
