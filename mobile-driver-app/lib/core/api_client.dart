import 'dart:convert';

import 'package:http/http.dart' as http;

import 'session_store.dart';

class ApiClient {
  ApiClient({
    required this.sessionStore,
    this.baseUrl = 'http://localhost:8000/api',
  });

  final SessionStore sessionStore;
  final String baseUrl;

  Future<Map<String, dynamic>> getJson(String path) async {
    final response = await http.get(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
    );

    return _decode(response);
  }

  Future<Map<String, dynamic>> postJson(String path, [Map<String, dynamic>? body]) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
      body: jsonEncode(body ?? {}),
    );

    return _decode(response);
  }

  Future<Map<String, dynamic>> postMultipart(
    String path, {
    required Map<String, String> fields,
    required String fileField,
    required List<int> fileBytes,
    required String filename,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl$path'));
    request.headers.addAll(await _authHeaders());
    request.fields.addAll(fields);
    request.files.add(
      http.MultipartFile.fromBytes(
        fileField,
        fileBytes,
        filename: filename,
      ),
    );

    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);

    return _decode(response);
  }

  Future<void> login(String login, String password) async {
    final data = await postJson('/auth/driver/login', {
      'login': login,
      'password': password,
    });

    await sessionStore.saveToken(data['token'] as String);
  }

  Future<void> logout() async {
    try {
      await postJson('/auth/logout');
    } finally {
      await sessionStore.clear();
    }
  }

  Future<Map<String, String>> _headers() async {
    final token = await sessionStore.readToken();

    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, String>> _authHeaders() async {
    final token = await sessionStore.readToken();

    return {
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode >= 400) {
      final errors = decoded['errors'];
      String? firstError;
      if (errors is Map && errors.isNotEmpty) {
        final value = errors.values.first;
        if (value is List && value.isNotEmpty) {
          firstError = value.first.toString();
        } else if (value != null) {
          firstError = value.toString();
        }
      }

      throw ApiException(
        statusCode: response.statusCode,
        message: firstError ?? decoded['message']?.toString() ?? 'Ошибка запроса',
      );
    }

    return decoded;
  }
}

class ApiException implements Exception {
  ApiException({required this.statusCode, required this.message});

  final int statusCode;
  final String message;

  @override
  String toString() => message;
}
