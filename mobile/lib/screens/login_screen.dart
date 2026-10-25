import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kReleaseMode;
import 'package:google_sign_in/google_sign_in.dart';
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
  final _googleSignIn = GoogleSignIn(
    scopes: ['email'],
    serverClientId: ApiService.googleServerClientId.isEmpty ? null : ApiService.googleServerClientId,
  );
  final _username = TextEditingController();
  final _password = TextEditingController();
  final _server = TextEditingController(text: ApiService.defaultServerHost);
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
    const legacy = {
      'http://127.0.0.1:8080',
      ApiService.localWifiHost,
      'http://192.168.1.18',
      'http://192.168.1.19',
      'http://192.168.37.246',
    };
    final savedHostIsLocal = saved != null && ApiService.webPathForHost(saved).isNotEmpty;
    if (saved == null || saved.isEmpty || legacy.contains(saved) || (kReleaseMode && savedHostIsLocal)) {
      saved = ApiService.defaultServerHost;
      await SessionService.saveServerHost(saved);
    }
    if (mounted) {
      setState(() => _server.text = saved!);
      ApiService.customHost = saved;
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
        final isLocal = ApiService.webPathForHost(server).isNotEmpty;
        _error = isLocal
          ? 'Cannot reach local server. Check that Apache is running and the phone and PC are on the same Wi-Fi.\n\nDefault: ${ApiService.defaultServerHost}\n\nTried:\n$tried'
          : 'Cannot reach the server. Check your internet connection and Server settings.\n\nDefault: ${ApiService.defaultServerHost}\n\nTried:\n$tried';
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submitGoogle() async {
    if (ApiService.googleServerClientId.isEmpty) {
      setState(() => _error = 'Google sign-in is not configured. Add the Web OAuth client ID to the app run configuration.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });
    final server = _server.text.trim();
    ApiService.customHost = server;
    await SessionService.saveServerHost(server);

    var stage = 'Google account selection';
    try {
      final account = await _googleSignIn.signIn();
      if (account == null) return;
      stage = 'Google identity token';
      final idToken = (await account.authentication).idToken;
      if (idToken == null || idToken.isEmpty) {
        throw StateError('Google did not return an identity token. Check the Web OAuth client ID.');
      }

      stage = 'Local server login';
      final res = await ApiService.loginWithGoogle(idToken);
      if (res['success'] != true || ApiService.token == null) {
        setState(() => _error = res['error']?.toString() ?? 'Google sign-in failed.');
        return;
      }

      await SessionService.saveSession(
        token: ApiService.token!,
        role: ApiService.role ?? 'student',
        username: ApiService.username ?? account.email,
      );
      widget.onLoggedIn(ApiService.role ?? 'student');
    } catch (error) {
      setState(() {
        final details = error is StateError ? error.message : error.toString();
        _error = '$stage failed: $details';
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
                const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SchoolLogo(size: 80),
                    SizedBox(width: 14),
                    SchoolLogo(size: 80, asset: SchoolLogo.skAsset),
                  ],
                ),
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
                const Text('SLSU Tiaong · SK Barangay Cabay', style: TextStyle(color: AppTheme.textSecondary, fontSize: 13)),
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
                      if (!kReleaseMode)
                        Align(
                          alignment: Alignment.centerLeft,
                          child: TextButton(
                            onPressed: () => setState(() => _showServer = !_showServer),
                            child: Text(_showServer ? 'Hide server settings' : 'Server settings'),
                          ),
                        ),
                      if (!kReleaseMode && _showServer) ...[
                        TextField(
                          controller: _server,
                          decoration: const InputDecoration(
                            labelText: 'Server URL',
                            hintText: ApiService.localWifiHost,
                            prefixIcon: Icon(Icons.dns_outlined, size: 20),
                          ),
                          keyboardType: TextInputType.url,
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Local Wi-Fi: ${ApiService.localWifiHost} · USB: ${ApiService.localUsbHost}',
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
                      const SizedBox(height: 10),
                      OutlinedButton.icon(
                        onPressed: _loading ? null : _submitGoogle,
                        icon: const Icon(Icons.g_mobiledata, size: 24),
                        label: const Text('CONTINUE WITH GOOGLE'),
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
