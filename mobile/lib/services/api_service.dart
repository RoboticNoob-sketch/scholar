import 'dart:convert';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:file_picker/file_picker.dart';
import 'package:http/http.dart' as http;

class ApiService {
  static const productionHost = 'https://scholarship-qr-monitor.online';
  static const googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue: '53011635142-vu8rdf9iliouls09pun58prf1ku0k294.apps.googleusercontent.com',
  );
  static const localWifiHost = 'http://192.168.1.44';
  static const localUsbHost = 'http://127.0.0.1:8080';
  static const localWebPath = '/scholarship-qr-monitor/web';

  static const _defaultAndroidHosts = [
    localWifiHost,
    localUsbHost,
    'http://192.168.1.18',
    'http://192.168.1.19',
    productionHost,
  ];

  static String customHost = localWifiHost;
  static String phoneBaseUrl = '';
  static String lastTriedUrl = '';
  static List<String> triedUrls = [];

  static String? token;
  static String? role;
  static String? username;
  static Map<String, dynamic>? scholar;

  static String webPathForHost(String host) {
    final h = host.trim().toLowerCase().replaceAll(RegExp(r'/+$'), '');
    final hostname = Uri.tryParse(h)?.host ?? h;
    final isPrivateIpv4 = RegExp(
      r'^(?:10(?:\.\d{1,3}){3}|172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2}|192\.168(?:\.\d{1,3}){2})$',
    ).hasMatch(hostname);
    if (h.contains('127.0.0.1') ||
        h.contains('localhost') ||
        isPrivateIpv4) {
      return localWebPath;
    }
    return '';
  }

  static String baseForHost(String host) {
    final clean = host.trim().replaceAll(RegExp(r'/+$'), '');
    if (clean.isEmpty) return productionHost;
    return '$clean${webPathForHost(clean)}';
  }

  static List<String> get _hosts {
    final hosts = <String>[];
    final custom = customHost.trim().replaceAll(RegExp(r'/+$'), '');
    if (custom.isNotEmpty) hosts.add(custom);
    if (kIsWeb) return [productionHost];
    if (Platform.isAndroid) {
      hosts.addAll(_defaultAndroidHosts);
    } else {
      hosts.add(productionHost);
    }
    return hosts.toSet().toList();
  }

  static String get baseUrl {
    if (phoneBaseUrl.isNotEmpty) return phoneBaseUrl;
    return baseForHost(_hosts.first);
  }

  /// Resolves API photo paths to a full URL the image loader can fetch.
  static String? resolveMediaUrl(String? url) {
    if (url == null || url.trim().isEmpty) return null;
    final trimmed = url.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
      return trimmed;
    }
    final base = baseUrl.replaceAll(RegExp(r'/+$'), '');
    final path = trimmed.replaceFirst(RegExp(r'^/+'), '');
    return '$base/$path';
  }

  static Map<String, String>? get mediaHeaders {
    final currentToken = token;
    if (currentToken == null || currentToken.isEmpty) return null;
    return {'Authorization': 'Bearer $currentToken'};
  }

  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  static Uri _uri(String path, [String? base]) {
    final root = (base ?? baseUrl).replaceAll(RegExp(r'/+$'), '');
    final segment = path.replaceFirst(RegExp(r'^/+'), '');
    return Uri.parse('$root/$segment');
  }

  static Future<String?> _probeHost(String host) async {
    final candidate = baseForHost(host);
    lastTriedUrl = candidate;
    triedUrls.add(candidate);
    try {
      final res = await http.get(_uri('login.php', candidate)).timeout(const Duration(seconds: 8));
      if (res.statusCode == 200) return candidate;
    } catch (_) {}
    return null;
  }

  static Future<String> resolveBaseUrl({bool force = false}) async {
    if (!force && phoneBaseUrl.isNotEmpty) return phoneBaseUrl;
    if (kIsWeb) return baseForHost(productionHost);
    if (!Platform.isAndroid) return baseForHost(productionHost);

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

  static Future<Map<String, dynamic>> login(String user, String password) =>
      _authenticate('api/auth/login.php', {'username': user, 'password': password});

  static Future<Map<String, dynamic>> loginWithGoogle(String idToken) =>
      _authenticate('api/auth/google-login.php', {'id_token': idToken});

  static Future<Map<String, dynamic>> _authenticate(String path, Map<String, dynamic> body) async {
    phoneBaseUrl = '';
    triedUrls = [];
    Object? lastError;

    for (final host in _hosts) {
      final candidate = baseForHost(host);
      lastTriedUrl = candidate;
      triedUrls.add(candidate);
      try {
        final res = await http
            .post(
              _uri(path, candidate),
              headers: _headers,
              body: jsonEncode(body),
            )
            .timeout(const Duration(seconds: 15));

        phoneBaseUrl = candidate;
        final data = _decode(res.body, res.statusCode);
        if (data['success'] == true && data['token'] != null) {
          token = data['token'] as String;
          role = data['role']?.toString();
          username = (data['user'] as Map?)?['username']?.toString();
          scholar = data['scholar'] is Map<String, dynamic> ? data['scholar'] as Map<String, dynamic> : null;
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
  static Future<Map<String, dynamic>> getApplications() => _get('api/student/applications.php');

  static Future<Map<String, dynamic>> submitApplication({
    required Map<String, String> fields,
    PlatformFile? photo,
    PlatformFile? enrollmentForm,
  }) async {
    try {
      await resolveBaseUrl();
    } on StateError {
      return {'success': false, 'error': 'Cannot reach server. Check your internet connection and Server settings.'};
    }

    final request = http.MultipartRequest('POST', _uri('api/student/applications.php'));
    if (token != null) request.headers['Authorization'] = 'Bearer $token';
    request.fields.addAll(fields);

    try {
      if (photo != null) {
        final bytes = photo.bytes;
        if (bytes == null) return {'success': false, 'error': 'Could not read the selected photo. Choose it again.'};
        request.files.add(http.MultipartFile.fromBytes('photo_2x2', bytes, filename: photo.name));
      }
      if (enrollmentForm != null) {
        final bytes = enrollmentForm.bytes;
        if (bytes == null) return {'success': false, 'error': 'Could not read the selected enrollment form. Choose it again.'};
        request.files.add(http.MultipartFile.fromBytes('enrollment_form', bytes, filename: enrollmentForm.name));
      }

      final streamed = await request.send().timeout(const Duration(seconds: 60));
      final response = await http.Response.fromStream(streamed);
      return _decode(response.body, response.statusCode);
    } catch (error) {
      return {'success': false, 'error': 'Upload failed. Check your connection and try again.'};
    }
  }

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
      return {
        'success': false,
        'error': 'Cannot reach server. Check internet connection and Server settings on login.',
      };
    }
    final res = await http.get(_uri(path), headers: _headers).timeout(const Duration(seconds: 15));
    return _decode(res.body, res.statusCode);
  }

  static Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> body) async {
    try {
      await resolveBaseUrl();
    } on StateError {
      return {
        'success': false,
        'error': 'Cannot reach server. Check internet connection and Server settings on login.',
      };
    }
    final res = await http
        .post(_uri(path), headers: _headers, body: jsonEncode(body))
        .timeout(const Duration(seconds: 15));
    return _decode(res.body, res.statusCode);
  }

  static Map<String, dynamic> _decode(String body, int status) {
    final decoded = jsonDecode(body);
    if (decoded is Map<String, dynamic>) return decoded;
    return {'success': false, 'error': 'Invalid response', 'status': status};
  }
}
