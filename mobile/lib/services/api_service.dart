import 'dart:convert';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;

class ApiService {
  static const _path = '/scholarship-qr-monitor/web';

  static const _defaultAndroidHosts = [
    'http://127.0.0.1:8080',
    'http://192.168.1.18',
    'http://192.168.1.19',
  ];

  static String customHost = '';
  static String phoneBaseUrl = '';
  static String lastTriedUrl = '';
  static List<String> triedUrls = [];

  static String? token;
  static String? role;
  static String? username;
  static Map<String, dynamic>? scholar;

  static List<String> get _hosts {
    final hosts = <String>[];
    final custom = customHost.trim().replaceAll(RegExp(r'/+$'), '');
    if (custom.isNotEmpty) hosts.add(custom);
    if (kIsWeb) return ['http://localhost'];
    if (Platform.isAndroid) {
      hosts.addAll(_defaultAndroidHosts);
    } else {
      hosts.add('http://localhost');
    }
    return hosts.toSet().toList();
  }

  static String get baseUrl {
    if (phoneBaseUrl.isNotEmpty) return phoneBaseUrl;
    if (kIsWeb) return 'http://localhost$_path';
    if (Platform.isAndroid) return '${_hosts.first}$_path';
    return 'http://localhost$_path';
  }

  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  static Uri _uri(String path, [String? base]) => Uri.parse('${base ?? baseUrl}/$path');

  static Future<String?> _probeHost(String host) async {
    final candidate = '$host$_path';
    lastTriedUrl = candidate;
    triedUrls.add(candidate);
    try {
      final res = await http.get(_uri('login.php', candidate)).timeout(const Duration(seconds: 5));
      if (res.statusCode == 200) return candidate;
    } catch (_) {}
    return null;
  }

  static Future<String> resolveBaseUrl({bool force = false}) async {
    if (!force && phoneBaseUrl.isNotEmpty) return phoneBaseUrl;
    if (kIsWeb) return 'http://localhost$_path';
    if (!Platform.isAndroid) return 'http://localhost$_path';

    if (force) phoneBaseUrl = '';
    triedUrls = [];

    for (final host in _hosts) {
      final found = await _probeHost(host);
      if (found != null) {
        phoneBaseUrl = found;
        return found;
      }
    }

    throw StateError('Cannot reach server');
  }

  static Future<Map<String, dynamic>> login(String user, String password) async {
    phoneBaseUrl = '';
    triedUrls = [];
    Object? lastError;

    for (final host in _hosts) {
      final candidate = '$host$_path';
      lastTriedUrl = candidate;
      triedUrls.add(candidate);
      try {
        final res = await http
            .post(
              _uri('api/auth/login.php', candidate),
              headers: _headers,
              body: jsonEncode({'username': user, 'password': password}),
            )
            .timeout(const Duration(seconds: 12));

        phoneBaseUrl = candidate;
        final data = _decode(res.body, res.statusCode);
        if (data['success'] == true && data['token'] != null) {
          token = data['token'] as String;
          role = data['role']?.toString();
          username = (data['user'] as Map?)?['username']?.toString();
          if (data['scholar'] is Map<String, dynamic>) {
            scholar = data['scholar'] as Map<String, dynamic>;
          }
        }
        return data;
      } catch (e) {
        lastError = e;
        continue;
      }
    }

    throw lastError ?? StateError('Cannot reach server');
  }

  // ── Student ──
  static Future<Map<String, dynamic>> getStatus() => _get('api/student/status.php');
  static Future<Map<String, dynamic>> getProfile() => _get('api/student/profile.php');
  static Future<Map<String, dynamic>> getHistory() => _get('api/student/history.php');

  // ── Staff ──
  static Future<Map<String, dynamic>> getOpenBatches() => _get('api/staff/batches.php');
  static Future<Map<String, dynamic>> getStaffClaimsToday() => _get('api/staff/claims-today.php');
  static Future<Map<String, dynamic>> getRecentClaims(int batchId) =>
      _get('api/claims/recent.php?batch_id=$batchId');

  static Future<Map<String, dynamic>> redeemVoucher({
    required int batchId,
    required String payload,
    String? verificationToken,
  }) =>
      _post('api/claims/redeem.php', {
        'batch_id': batchId,
        'payload': payload,
        if (verificationToken != null && verificationToken.isNotEmpty)
          'verification_token': verificationToken,
      });

  static Future<Map<String, dynamic>> verifyProfile(String payload, {int? batchId}) =>
      _post('api/claims/verify-profile.php', {
        'payload': payload,
        if (batchId != null) 'batch_id': batchId,
      });

  static Future<Map<String, dynamic>> _get(String path) async {
    try {
      await resolveBaseUrl();
    } on StateError {
      return {'success': false, 'error': 'Cannot reach server. Use Wi-Fi and check Server settings on login.'};
    }
    final res = await http.get(_uri(path), headers: _headers).timeout(const Duration(seconds: 10));
    return _decode(res.body, res.statusCode);
  }

  static Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> body) async {
    try {
      await resolveBaseUrl();
    } on StateError {
      return {'success': false, 'error': 'Cannot reach server. Use Wi-Fi and check Server settings on login.'};
    }
    final res = await http
        .post(_uri(path), headers: _headers, body: jsonEncode(body))
        .timeout(const Duration(seconds: 10));
    return _decode(res.body, res.statusCode);
  }

  static Map<String, dynamic> _decode(String body, int status) {
    final decoded = jsonDecode(body);
    if (decoded is Map<String, dynamic>) return decoded;
    return {'success': false, 'error': 'Invalid response', 'status': status};
  }
}
