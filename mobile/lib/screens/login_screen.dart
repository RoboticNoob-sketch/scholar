import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/session_service.dart';
import '../widgets/app_card.dart';
import '../widgets/school_logo.dart';
import '../theme/app_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.onLoggedIn});

  final void Function(String role) onLoggedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _username = TextEditingController();
  final _password = TextEditingController();
  final _server = TextEditingController(text: 'http://127.0.0.1:8080');
  bool _loading = false;
  bool _obscure = true;
  bool _showServer = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadServer();
  }

  Future<void> _loadServer() async {
    var saved = await SessionService.getServerHost();
    if (saved == 'http://192.168.1.18' || saved == 'http://192.168.37.246') {
      saved = 'http://127.0.0.1:8080';
      await SessionService.saveServerHost(saved);
    }
    if (mounted && saved != null && saved.isNotEmpty) {
      setState(() => _server.text = saved!);
    }
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final server = _server.text.trim();
    ApiService.customHost = server;
    await SessionService.saveServerHost(server);

    try {
      final res = await ApiService.login(_username.text.trim(), _password.text);
      if (res['success'] == true && ApiService.token != null) {
        final role = res['role']?.toString() ?? 'student';
        await SessionService.saveSession(
          token: ApiService.token!,
          role: role,
          username: ApiService.username ?? _username.text.trim(),
        );
        widget.onLoggedIn(role);
      } else {
        setState(() => _error = res['error']?.toString() ?? 'Login failed');
      }
    } catch (_) {
      final tried = ApiService.triedUrls.isEmpty ? ApiService.lastTriedUrl : ApiService.triedUrls.join('\n');
      setState(() {
        _error = 'Cannot reach server.\n\nYour router may block phone↔PC (AP isolation).\n\nFix options:\nA) Router: disable AP/client isolation\nB) USB: keep cable + use http://127.0.0.1:8080\nC) Hotspot: phone hotspot → PC joins → use PC ipconfig IP\n\nTried:\n$tried';
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _username.dispose();
    _password.dispose();
    _server.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            stops: [0, 0.45, 1],
            colors: [Color(0xFF1A2E1F), AppTheme.page, AppTheme.page],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                const SizedBox(height: 16),
                const SchoolLogo(size: 88),
                const SizedBox(height: 18),
                RichText(
                  text: const TextSpan(
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppTheme.textPrimary),
                    children: [
                      TextSpan(text: 'Scholarly'),
                      TextSpan(text: '.', style: TextStyle(color: AppTheme.accent)),
                    ],
                  ),
                ),
                const SizedBox(height: 4),
                const Text('SLSU Tiaong · Scholarship Office', style: TextStyle(color: AppTheme.textSecondary, fontSize: 13)),
                const SizedBox(height: 32),
                AppCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      TextField(
                        controller: _username,
                        decoration: const InputDecoration(
                          labelText: 'Username',
                          hintText: 'Enter username',
                          prefixIcon: Icon(Icons.person_outline, size: 20),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _password,
                        obscureText: _obscure,
                        decoration: InputDecoration(
                          labelText: 'Password',
                          hintText: 'Enter password',
                          prefixIcon: const Icon(Icons.lock_outline, size: 20),
                          suffixIcon: IconButton(
                            icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 20),
                            onPressed: () => setState(() => _obscure = !_obscure),
                          ),
                        ),
                      ),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton(
                          onPressed: () => setState(() => _showServer = !_showServer),
                          child: Text(_showServer ? 'Hide server settings' : 'Server settings'),
                        ),
                      ),
                      if (_showServer) ...[
                        TextField(
                          controller: _server,
                          decoration: const InputDecoration(
                            labelText: 'Server URL',
                            hintText: 'http://127.0.0.1:8080',
                            prefixIcon: Icon(Icons.dns_outlined, size: 20),
                          ),
                          keyboardType: TextInputType.url,
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Wi-Fi: http://YOUR_PC_IP (no port) · USB: http://127.0.0.1:8080',
                          style: TextStyle(fontSize: 10, color: AppTheme.textTertiary),
                        ),
                      ],
                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: AppTheme.negative.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: AppTheme.negative.withValues(alpha: 0.3)),
                          ),
                          child: Text(_error!, style: const TextStyle(color: AppTheme.negative, fontSize: 12, height: 1.4)),
                        ),
                      ],
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loading ? null : _submit,
                        child: Text(_loading ? 'LOGGING IN...' : 'LOG IN'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                const Text('v1.0.0 · Student & Staff Portal', style: TextStyle(fontSize: 11, color: AppTheme.textTertiary)),
                const SizedBox(height: 8),
                const Text(
                  'Student: maria.santos / password\nStaff: staff1 / password',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 11, color: AppTheme.textSecondary, height: 1.5),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
