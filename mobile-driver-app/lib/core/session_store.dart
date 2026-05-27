import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SessionStore {
  static const _tokenKey = 'azyk_driver_token';
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  Future<String?> readToken() {
    return _storage.read(key: _tokenKey);
  }

  Future<void> saveToken(String token) {
    return _storage.write(key: _tokenKey, value: token);
  }

  Future<bool> hasToken() async {
    final token = await readToken();
    return token != null && token.isNotEmpty;
  }

  Future<void> clear() {
    return _storage.delete(key: _tokenKey);
  }
}

