import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  ApiClient({
    required String baseUrl,
    FlutterSecureStorage? storage,
  })  : _storage = storage ?? const FlutterSecureStorage(),
        dio = Dio(
          BaseOptions(
            baseUrl: baseUrl,
            connectTimeout: const Duration(seconds: 20),
            receiveTimeout: const Duration(seconds: 30),
            sendTimeout: const Duration(seconds: 30),
            headers: const {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read(key: 'auth_token');

          if (token != null && token.trim().isNotEmpty) {
            options.headers['Authorization'] = 'Bearer ${token.trim()}';
          }

          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            // Do not automatically clear the token here.
            // Authentication/logout logic should decide whether
            // the stored session is truly invalid.
          }

          handler.next(error);
        },
      ),
    );
  }

  final Dio dio;
  final FlutterSecureStorage _storage;

  Future<void> saveToken(String token) async {
    await _storage.write(
      key: 'auth_token',
      value: token,
    );
  }

  Future<String?> getToken() {
    return _storage.read(key: 'auth_token');
  }

  Future<void> clearToken() async {
    await _storage.delete(key: 'auth_token');
  }

  Future<bool> get hasToken async {
    final token = await getToken();
    return token != null && token.trim().isNotEmpty;
  }
}
