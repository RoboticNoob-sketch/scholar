import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../theme/app_theme.dart';

class ScholarAvatar extends StatefulWidget {
  const ScholarAvatar({
    super.key,
    required this.initials,
    this.photoUrl,
    this.radius = 38,
  });

  final String? photoUrl;
  final String initials;
  final double radius;

  @override
  State<ScholarAvatar> createState() => _ScholarAvatarState();
}

class _ScholarAvatarState extends State<ScholarAvatar> {
  bool _loadFailed = false;

  @override
  void didUpdateWidget(covariant ScholarAvatar oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.photoUrl != widget.photoUrl) {
      _loadFailed = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final resolved = ApiService.resolveMediaUrl(widget.photoUrl);
    final showImage = resolved != null && resolved.isNotEmpty && !_loadFailed;
    final label = widget.initials.isEmpty ? 'SL' : widget.initials;

    return CircleAvatar(
      radius: widget.radius,
      backgroundColor: AppTheme.elevated,
      backgroundImage: showImage ? NetworkImage(resolved) : null,
      onBackgroundImageError: showImage
          ? (_, __) {
              if (mounted) setState(() => _loadFailed = true);
            }
          : null,
      child: showImage
          ? null
          : Text(
              label,
              style: TextStyle(
                fontSize: widget.radius * 0.68,
                fontWeight: FontWeight.w800,
                color: AppTheme.accent,
              ),
            ),
    );
  }
}
