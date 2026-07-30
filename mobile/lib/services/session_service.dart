import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SessionService {
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'scholarly_token';
  static const _roleKey = 'scholarly_role';
  static const _usernameKey = 'scholarly_username';
  static const _serverKey = 'scholarly_server';

  static Future<void> saveSession({
    required String token,
    required String role,
    required String username,
  }) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _roleKey, value: role);
    await _storage.write(key: _usernameKey, value: username);
  }

  static Future<String?> getToken() => _storage.read(key: _tokenKey);
  static Future<String?> getRole() => _storage.read(key: _roleKey);
  static Future<String?> getUsername() => _storage.read(key: _usernameKey);
  static Future<void> saveServerHost(String host) => _storage.write(key: _serverKey, value: host);
  static Future<String?> getServerHost() => _storage.read(key: _serverKey);

  static Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _roleKey);
    await _storage.delete(key: _usernameKey);
    await _storage.delete(key: _serverKey);
  }
}
