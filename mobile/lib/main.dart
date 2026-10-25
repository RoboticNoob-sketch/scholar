import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'screens/login_screen.dart';
import 'screens/main_shell.dart';
import 'screens/staff/staff_main_shell.dart';
import 'services/api_service.dart';
import 'services/session_service.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
  ));
  runApp(const ScholarlyApp());
}

class ScholarlyApp extends StatelessWidget {
  const ScholarlyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Scholarly',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.dark,
      home: const BootstrapScreen(),
    );
  }
}

class BootstrapScreen extends StatefulWidget {
  const BootstrapScreen({super.key});

  @override
  State<BootstrapScreen> createState() => _BootstrapScreenState();
}

class _BootstrapScreenState extends State<BootstrapScreen> {
  bool _loading = true;
  bool _loggedIn = false;
  bool _hasScholar = false;
  String? _role;

  @override
  void initState() {
    super.initState();
    _checkSession();
  }

  Future<void> _checkSession() async {
    final token = await SessionService.getToken();
    final role = await SessionService.getRole();
    final username = await SessionService.getUsername();
    final server = await SessionService.getServerHost();
    if (server != null && server.isNotEmpty) {
      ApiService.customHost = server;
    } else {
      ApiService.customHost = ApiService.productionHost;
    }
    if (token != null) {
      ApiService.token = token;
      ApiService.role = role;
      ApiService.username = username;
      _role = role;
      _loggedIn = true;
      if (role == 'student') {
        try {
          final response = await ApiService.getProfile();
          final profile = response['profile'];
          _hasScholar = response['success'] == true && profile is Map && profile['is_scholar'] == true;
        } catch (_) {
          _hasScholar = ApiService.scholar != null;
        }
      }
    }
    if (mounted) setState(() => _loading = false);
  }

  void _onLoggedIn(String role) {
    setState(() {
      _loggedIn = true;
      _role = role;
      _hasScholar = ApiService.scholar != null;
    });
  }

  void _onLogout() {
    setState(() {
      _loggedIn = false;
      _role = null;
      _hasScholar = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: AppTheme.accent)),
      );
    }
    if (!_loggedIn) {
      return LoginScreen(onLoggedIn: _onLoggedIn);
    }
    if (_role == 'staff') {
      return StaffMainShell(onLogout: _onLogout);
    }
    return MainShell(onLogout: _onLogout, hasScholar: _hasScholar);
  }
}
