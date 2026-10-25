import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'home_screen.dart';
import 'qr_screen.dart';
import 'history_screen.dart';
import 'profile_screen.dart';
import 'application_screen.dart';
import 'welcome_wizard.dart';
import '../services/api_service.dart';
import '../services/session_service.dart';
import '../theme/app_theme.dart';
import '../utils/application_updates.dart';
import '../widgets/app_nav_shell.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.onLogout, required this.hasScholar});

  final VoidCallback onLogout;
  final bool hasScholar;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> with WidgetsBindingObserver {
  static const _pollInterval = Duration(seconds: 45);

  int _index = 0;
  int? _qrVoucherId;
  int _unreadApplicationUpdates = 0;
  int _progressRefresh = 0;
  String? _latestUpdateTitle;
  bool _bannerDismissed = false;
  bool _checking = false;
  Timer? _poller;
  late bool _hasScholar;

  int get _applicationIndex => _hasScholar ? 3 : 0;

  @override
  void initState() {
    super.initState();
    _hasScholar = widget.hasScholar;
    WidgetsBinding.instance.addObserver(this);
    _poller = Timer.periodic(_pollInterval, (_) => _checkForUpdates());
    _checkForUpdates();
    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeShowWelcome());
  }

  Future<void> _maybeShowWelcome() async {
    final username = await SessionService.getUsername();
    if (username == null || !mounted || await SessionService.hasSeenWelcome(username)) return;
    await SessionService.markWelcomeSeen(username);
    if (mounted) await WelcomeWizard.show(context);
  }

  @override
  void dispose() {
    _poller?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _checkForUpdates();
  }

  // The Progress tab is only built while selected, so unread updates are checked here instead.
  Future<void> _checkForUpdates() async {
    if (!mounted) return;
    // On the Progress tab the screen refreshes itself quietly, so new updates show up without a manual refresh.
    if (_index == _applicationIndex) {
      setState(() => _progressRefresh++);
      return;
    }
    if (_checking) return;
    _checking = true;
    try {
      final response = await ApiService.getApplications();
      if (!mounted || response['success'] != true || _index == _applicationIndex) return;
      final updates = (response['updates'] as List? ?? const [])
          .whereType<Map>()
          .map((event) => Map<String, dynamic>.from(event))
          .toList();
      final lastRead = await SessionService.getLastApplicationUpdateId() ?? 0;
      final unread = updates.where((event) => updateId(event['id']) > lastRead).toList();
      final newest = unread.isEmpty ? null : unread.reduce((a, b) => updateId(a['id']) >= updateId(b['id']) ? a : b);
      if (!mounted) return;
      final increased = unread.length > _unreadApplicationUpdates;
      setState(() {
        _unreadApplicationUpdates = unread.length;
        _latestUpdateTitle = newest == null ? null : describeApplicationUpdate(newest).title;
        if (increased) _bannerDismissed = false;
      });
      if (increased) HapticFeedback.mediumImpact();
    } catch (_) {
      // Offline or server error: try again on the next poll.
    } finally {
      _checking = false;
    }
  }

  void _openProgress() => setState(() => _index = _applicationIndex);

  Widget _updateBanner() {
    final count = _unreadApplicationUpdates;
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Material(
        color: AppTheme.accent.withValues(alpha: 0.12),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: AppTheme.accent.withValues(alpha: 0.5)),
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: _openProgress,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 4, 10),
            child: Row(
              children: [
                const Icon(Icons.notifications_active_outlined, color: AppTheme.accent, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        count == 1 ? 'New update on your application' : '$count new updates on your application',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                      ),
                      if (_latestUpdateTitle != null)
                        Text(_latestUpdateTitle!, style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
                    ],
                  ),
                ),
                const Text('VIEW', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppTheme.accent)),
                IconButton(
                  tooltip: 'Dismiss',
                  visualDensity: VisualDensity.compact,
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () => setState(() => _bannerDismissed = true),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  void didUpdateWidget(covariant MainShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.hasScholar != widget.hasScholar) _hasScholar = widget.hasScholar;
  }

  void _setScholarStatus(bool hasScholar) {
    if (_hasScholar == hasScholar) return;
    setState(() {
      _hasScholar = hasScholar;
      _index = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    final applicationIndex = _hasScholar ? 3 : 0;
    final profileIndex = _hasScholar ? 4 : 1;
    final pages = _hasScholar
        ? [
            HomeScreen(
              onShowQr: (voucherId) => setState(() {
                _qrVoucherId = voucherId;
                _index = 1;
              }),
            ),
            QrScreen(
              key: ValueKey(_qrVoucherId),
              initialVoucherId: _qrVoucherId,
            ),
            const HistoryScreen(),
            ApplicationScreen(
              refreshTick: _progressRefresh,
              isVisible: _index == applicationIndex,
              onUnreadChanged: (count) => setState(() => _unreadApplicationUpdates = count),
            ),
            ProfileScreen(
              onLogout: widget.onLogout,
              isVisible: _index == profileIndex,
              onScholarStatusChanged: _setScholarStatus,
              onShowGuide: () => WelcomeWizard.show(context),
            ),
          ]
        : [
            ApplicationScreen(
              refreshTick: _progressRefresh,
              isVisible: _index == applicationIndex,
              onUnreadChanged: (count) => setState(() => _unreadApplicationUpdates = count),
            ),
            ProfileScreen(
              onLogout: widget.onLogout,
              isVisible: _index == profileIndex,
              onScholarStatusChanged: _setScholarStatus,
              onShowGuide: () => WelcomeWizard.show(context),
            ),
          ];

    final progressDestination = NavigationDestination(
      icon: Badge(
        isLabelVisible: _unreadApplicationUpdates > 0,
        label: Text(_unreadApplicationUpdates > 9 ? '9+' : '$_unreadApplicationUpdates'),
        child: const Icon(Icons.assignment_outlined),
      ),
      selectedIcon: Badge(
        isLabelVisible: _unreadApplicationUpdates > 0,
        label: Text(_unreadApplicationUpdates > 9 ? '9+' : '$_unreadApplicationUpdates'),
        child: const Icon(Icons.assignment),
      ),
      label: 'Progress',
    );

    return AppNavShell(
      selectedIndex: _index,
      onDestinationSelected: (i) {
        setState(() => _index = i);
        if (i != _applicationIndex) _checkForUpdates();
      },
      destinations: _hasScholar
          ? [
              const NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
              const NavigationDestination(icon: Icon(Icons.qr_code_2_outlined), selectedIcon: Icon(Icons.qr_code_2), label: 'My QR'),
              const NavigationDestination(icon: Icon(Icons.history_outlined), selectedIcon: Icon(Icons.history), label: 'History'),
              progressDestination,
              const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
            ]
          : [
              progressDestination,
              const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
            ],
      body: Column(
        children: [
          if (_unreadApplicationUpdates > 0 && !_bannerDismissed && _index != applicationIndex) _updateBanner(),
          Expanded(child: pages[_index]),
        ],
      ),
    );
  }
}
