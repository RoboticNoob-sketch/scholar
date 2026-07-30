import 'package:flutter/material.dart';

class SchoolLogo extends StatelessWidget {
  const SchoolLogo({super.key, this.size = 64});

  final double size;

  static const _asset = 'assets/images/slsu-tiaong-logo.png';

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: const BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
      ),
      padding: EdgeInsets.all(size * 0.06),
      child: ClipOval(
        child: Image.asset(
          _asset,
          fit: BoxFit.contain,
          errorBuilder: (_, __, ___) => Icon(Icons.school, size: size * 0.5, color: Colors.grey),
        ),
      ),
    );
  }
}
