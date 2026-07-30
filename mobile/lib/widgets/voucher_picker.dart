import 'package:flutter/material.dart';
import '../models/student_voucher.dart';
import '../theme/app_theme.dart';

class VoucherPicker extends StatelessWidget {
  const VoucherPicker({
    super.key,
    required this.vouchers,
    required this.selectedId,
    required this.onSelected,
  });

  final List<StudentVoucher> vouchers;
  final int selectedId;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    if (vouchers.length <= 1) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '${vouchers.length} programs ready to claim',
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.textSecondary),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 92,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: vouchers.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (context, index) {
              final voucher = vouchers[index];
              final selected = voucher.voucherId == selectedId;
              return GestureDetector(
                onTap: () => onSelected(voucher.voucherId),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  width: 168,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: selected ? AppTheme.accent.withValues(alpha: 0.12) : AppTheme.elevated,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: selected ? AppTheme.accent : AppTheme.border,
                      width: selected ? 1.5 : 1,
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        voucher.programName,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          color: selected ? AppTheme.accent : AppTheme.textPrimary,
                          height: 1.2,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        voucher.amountFormatted,
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppTheme.textSecondary),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
