class StudentVoucher {
  StudentVoucher({
    required this.voucherId,
    required this.batchId,
    required this.programId,
    required this.batchName,
    required this.programName,
    required this.venue,
    required this.distributionDate,
    required this.amountFormatted,
    required this.status,
    this.voucherQr,
    this.claimedAt,
  });

  final int voucherId;
  final int batchId;
  final int programId;
  final String batchName;
  final String programName;
  final String venue;
  final String distributionDate;
  final String amountFormatted;
  final String status;
  final String? voucherQr;
  final String? claimedAt;

  bool get isPending => status == 'pending';

  factory StudentVoucher.fromMap(Map<String, dynamic> map) {
    return StudentVoucher(
      voucherId: _asInt(map['voucher_id']),
      batchId: _asInt(map['batch_id']),
      programId: _asInt(map['program_id']),
      batchName: map['batch_name']?.toString() ?? '',
      programName: map['program_name']?.toString() ?? '',
      venue: map['venue']?.toString() ?? '',
      distributionDate: map['distribution_date']?.toString() ?? '',
      amountFormatted: map['amount_formatted']?.toString() ?? '',
      status: map['voucher_status']?.toString() ?? 'pending',
      voucherQr: map['voucher_qr']?.toString(),
      claimedAt: map['claimed_at']?.toString(),
    );
  }

  static List<StudentVoucher> listFromStatus(Map<String, dynamic>? data) {
    if (data == null) return const [];

    final raw = data['open_vouchers'];
    if (raw is List && raw.isNotEmpty) {
      return raw
          .whereType<Map>()
          .map((item) => StudentVoucher.fromMap(Map<String, dynamic>.from(item)))
          .toList();
    }

    final legacy = data['current_batch'];
    if (legacy is Map) {
      final map = Map<String, dynamic>.from(legacy);
      return [
        StudentVoucher.fromMap({
          'voucher_id': map['voucher_id'] ?? 0,
          'batch_id': map['batch_id'] ?? 0,
          'program_id': map['program_id'] ?? 0,
          'batch_name': map['batch_name'],
          'program_name': map['program_name'],
          'venue': map['venue'],
          'distribution_date': map['distribution_date'],
          'amount_formatted': map['amount_formatted'],
          'voucher_status': map['voucher_status'],
          'voucher_qr': map['voucher_qr'],
          'claimed_at': map['claimed_at'],
        }),
      ];
    }

    return const [];
  }

  static int _asInt(Object? value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }
}
