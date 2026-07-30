import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/session_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/page_header.dart';
import '../widgets/school_logo.dart';
import '../widgets/status_badge.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.onLogout});

  final VoidCallback onLogout;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? _profile;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ApiService.getProfile();
      if (!mounted) return;
      if (res['success'] == true) {
        setState(() => _profile = res['profile'] as Map<String, dynamic>?);
      } else {
        setState(() => _profile = ApiService.scholar);
      }
    } catch (_) {
      if (mounted) setState(() => _profile = ApiService.scholar);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _logout() async {
    ApiService.token = null;
    ApiService.role = null;
    ApiService.username = null;
    ApiService.scholar = null;
    await SessionService.clear();
    widget.onLogout();
  }

  @override
  Widget build(BuildContext context) {
    final p = _profile;
    final name = p?['full_name']?.toString() ?? 'Scholar';
    final initials = name.split(' ').where((e) => e.isNotEmpty).map((e) => e[0]).take(2).join().toUpperCase();
    final courseYear = [p?['course'], p?['year_level']].where((e) => e != null && e.toString().isNotEmpty).join(' · ');
    final programs = ((p?['programs'] as List?) ?? []).join(', ');

    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          const PageHeader(title: 'Profile'),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator(strokeWidth: 2)))
          else ...[
            AppCard(
              accentTop: AppTheme.accent,
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 38,
                    backgroundColor: AppTheme.elevated,
                    child: Text(
                      initials.isEmpty ? 'SL' : initials,
                      style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: AppTheme.accent),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                  Text(p?['student_no']?.toString() ?? '', style: const TextStyle(color: AppTheme.textSecondary)),
                  if (courseYear.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(courseYear, style: const TextStyle(fontSize: 12, color: AppTheme.textTertiary)),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 12),
            AppCard(
              child: Column(
                children: [
                  _row(Icons.email_outlined, 'Email', p?['email']?.toString() ?? '—'),
                  const Divider(height: 20, color: AppTheme.border),
                  _row(Icons.school_outlined, 'Program', programs.isEmpty ? '—' : programs),
                  const Divider(height: 20, color: AppTheme.border),
                  Row(
                    children: [
                      const Icon(Icons.circle_outlined, size: 18, color: AppTheme.textTertiary),
                      const SizedBox(width: 12),
                      const Expanded(child: Text('Status', style: TextStyle(color: AppTheme.textSecondary, fontSize: 13))),
                      StatusBadge(status: p?['status']?.toString() ?? 'active'),
                    ],
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 20),
          OutlinedButton.icon(onPressed: _logout, icon: const Icon(Icons.logout, size: 18), label: const Text('LOG OUT')),
          const SizedBox(height: 20),
          const Center(
            child: Column(
              children: [
                SchoolLogo(size: 36),
                SizedBox(height: 8),
                Text('Scholarly · SLSU Tiaong', style: TextStyle(fontSize: 10, color: AppTheme.textTertiary)),
                Text('Version 1.0.0', style: TextStyle(fontSize: 10, color: AppTheme.textTertiary)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(IconData icon, String label, String value) => Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppTheme.textTertiary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(color: AppTheme.textTertiary, fontSize: 11, fontWeight: FontWeight.w600)),
                const SizedBox(height: 2),
                Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ],
      );
}
