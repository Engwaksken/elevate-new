import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/app_config.dart';

class ApiService {
  ApiService._();

  static final ApiService instance = ApiService._();

  static const String _tokenKey = 'auth_token';
  static const String _currentUserKey = 'current_user';

  final FlutterSecureStorage _secure = const FlutterSecureStorage();

  late final Dio dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 45),
      sendTimeout: const Duration(seconds: 45),
      headers: const {
        'Accept': 'application/json',
      },
      responseType: ResponseType.json,
    ),
  )..interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await getToken();

          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }

          options.headers['Accept'] = 'application/json';

          handler.next(options);
        },
        onError: (error, handler) {
          handler.next(error);
        },
      ),
    );

  // =========================================================
  // TOKEN / SESSION
  // =========================================================

  Future<String?> getToken() async {
    final token = await _secure.read(key: _tokenKey);

    if (token == null) {
      return null;
    }

    final cleaned = token.trim();

    if (cleaned.isEmpty) {
      return null;
    }

    return cleaned;
  }

  Future<void> saveToken(String token) async {
    final cleaned = token.trim();

    if (cleaned.isEmpty) {
      return;
    }

    await _secure.write(
      key: _tokenKey,
      value: cleaned,
    );
  }

  Future<void> clearToken() async {
    await _secure.delete(
      key: _tokenKey,
    );
  }

  Future<bool> hasToken() async {
    final token = await getToken();

    return token != null && token.isNotEmpty;
  }

  // =========================================================
  // AUTHENTICATION
  // =========================================================

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await dio.post(
      '/login',
      data: {
        'email': email.trim(),
        'password': password,
        'device_name': 'ElevateHer360 Flutter',
      },
    );

    final data = _mapResponse(
      response.data,
    );

    final token = data['token']?.toString();

    if (token == null || token.trim().isEmpty) {
      throw const ApiServiceException(
        'Login succeeded but no authentication token was returned.',
      );
    }

    await saveToken(token);

    final user = data['user'];

    if (user is Map) {
      await _secure.write(
        key: _currentUserKey,
        value: jsonEncode(
          Map<String, dynamic>.from(user),
        ),
      );
    }

    return data;
  }

  Future<void> logout() async {
    try {
      if (await hasToken()) {
        await dio.post('/logout');
      }
    } catch (_) {
      // Logout must still clear the local session when
      // the device has no internet connection.
    } finally {
      await clearSession();
    }
  }

  Future<void> clearSession() async {
    await _secure.delete(
      key: _tokenKey,
    );

    await _secure.delete(
      key: _currentUserKey,
    );
  }

  Future<Map<String, dynamic>?> currentUser() async {
    final raw = await _secure.read(
      key: _currentUserKey,
    );

    if (raw == null || raw.trim().isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);

      if (decoded is Map) {
        return Map<String, dynamic>.from(
          decoded,
        );
      }
    } catch (_) {
      await _secure.delete(
        key: _currentUserKey,
      );
    }

    return null;
  }

  Future<void> saveCurrentUser(
    Map<String, dynamic> user,
  ) async {
    await _secure.write(
      key: _currentUserKey,
      value: jsonEncode(user),
    );
  }

  // =========================================================
  // PARTICIPANT
  // =========================================================

  Future<Map<String, dynamic>> me() async {
    final response = await dio.get('/me');

    final data = _mapResponse(
      response.data,
    );

    final user = data['user'];

    if (user is Map) {
      await saveCurrentUser(
        Map<String, dynamic>.from(
          user,
        ),
      );
    }

    return data;
  }

  Future<Map<String, dynamic>> dashboard() async {
    final response = await dio.get('/dashboard');

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // SYNCHRONISATION
  // =========================================================

  Future<Map<String, dynamic>> sync({
    String? lastSyncedAt,
  }) async {
    final response = await dio.get(
      '/sync',
      queryParameters: {
        if (lastSyncedAt != null && lastSyncedAt.trim().isNotEmpty)
          'since': lastSyncedAt.trim(),
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> postOfflineActions(
    List<Map<String, dynamic>> actions,
  ) async {
    if (actions.isEmpty) {
      return {
        'data': <dynamic>[],
      };
    }

    final response = await dio.post(
      '/offline-actions',
      data: {
        'actions': actions,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // COURSES
  // =========================================================

  Future<Map<String, dynamic>> courses({
    int page = 1,
    String? search,
  }) async {
    final response = await dio.get(
      '/courses',
      queryParameters: {
        'page': page,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> course(
    int id,
  ) async {
    final response = await dio.get(
      '/courses/$id',
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // ASSIGNMENTS
  // =========================================================

  Future<Map<String, dynamic>> assignments({
    int page = 1,
  }) async {
    final response = await dio.get(
      '/assignments',
      queryParameters: {
        'page': page,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> submitAssignment({
    required int assessmentId,
    String? text,
    String? localFilePath,
    String? clientSubmissionId,
  }) async {
    MultipartFile? file;

    if (localFilePath != null && localFilePath.trim().isNotEmpty) {
      final localFile = File(localFilePath);

      if (await localFile.exists()) {
        file = await MultipartFile.fromFile(
          localFile.path,
          filename: localFile.uri.pathSegments.last,
        );
      }
    }

    final form = FormData.fromMap({
      if (text != null && text.trim().isNotEmpty)
        'submission_text': text.trim(),
      if (clientSubmissionId != null && clientSubmissionId.trim().isNotEmpty)
        'client_submission_id': clientSubmissionId.trim(),
      if (file != null) 'submission_file': file,
    });

    final response = await dio.post(
      '/assignments/$assessmentId/submit',
      data: form,
      options: Options(
        contentType: 'multipart/form-data',
      ),
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // LESSON PROGRESS
  // =========================================================

  Future<Map<String, dynamic>> markLessonProgress({
    required int lessonId,
    required bool completed,
    int seconds = 0,
  }) async {
    final response = await dio.put(
      '/lessons/$lessonId/progress',
      data: {
        'completed': completed,
        'time_spent_seconds': seconds < 0 ? 0 : seconds,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // MENTORSHIP
  // =========================================================

  Future<Map<String, dynamic>> mentorship() async {
    final response = await dio.get('/mentorship');

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // JOBS AND OPPORTUNITIES
  // =========================================================

  Future<Map<String, dynamic>> jobs({
    int page = 1,
    String? search,
  }) async {
    final response = await dio.get(
      '/jobs',
      queryParameters: {
        'page': page,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> saveJob(
    int jobId,
  ) async {
    final response = await dio.post(
      '/jobs/$jobId/save',
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> unsaveJob(
    int jobId,
  ) async {
    final response = await dio.delete(
      '/jobs/$jobId/save',
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // EVENTS
  // =========================================================

  Future<Map<String, dynamic>> events({
    int page = 1,
  }) async {
    final response = await dio.get(
      '/events',
      queryParameters: {
        'page': page,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // ANNOUNCEMENTS
  // =========================================================

  Future<Map<String, dynamic>> announcements({
    int page = 1,
  }) async {
    final response = await dio.get(
      '/announcements',
      queryParameters: {
        'page': page,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // NOTIFICATIONS
  // =========================================================

  Future<Map<String, dynamic>> notifications({
    int page = 1,
  }) async {
    final response = await dio.get(
      '/notifications',
      queryParameters: {
        'page': page,
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> markNotificationRead(
    dynamic notificationId,
  ) async {
    final response = await dio.put(
      '/notifications/$notificationId/read',
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // PUSH NOTIFICATIONS / DEVICE TOKEN
  // =========================================================

  Future<Map<String, dynamic>> registerDeviceToken({
    required String deviceId,
    required String token,
    required String platform,
    String? appVersion,
  }) async {
    final response = await dio.post(
      '/device-token',
      data: {
        'device_id': deviceId.trim(),
        'fcm_token': token.trim(),
        'platform': platform.trim(),
        if (appVersion != null && appVersion.trim().isNotEmpty)
          'app_version': appVersion.trim(),
      },
    );

    return _mapResponse(
      response.data,
    );
  }

  /// The current Laravel participant routes expose:
  ///
  /// POST /device-token
  ///
  /// but currently do not expose:
  ///
  /// DELETE /device-token
  ///
  /// Therefore this method intentionally performs no
  /// remote request until the backend route is added.
  Future<void> unregisterDeviceToken({
    required String deviceId,
  }) async {
    return;
  }

  // =========================================================
  // GENERIC HTTP HELPERS
  // =========================================================

  Future<Map<String, dynamic>> get(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await dio.get(
      _normaliseEndpoint(endpoint),
      queryParameters: queryParameters,
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> post(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await dio.post(
      _normaliseEndpoint(endpoint),
      data: data,
      queryParameters: queryParameters,
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> put(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await dio.put(
      _normaliseEndpoint(endpoint),
      data: data,
      queryParameters: queryParameters,
    );

    return _mapResponse(
      response.data,
    );
  }

  Future<Map<String, dynamic>> delete(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await dio.delete(
      _normaliseEndpoint(endpoint),
      data: data,
      queryParameters: queryParameters,
    );

    return _mapResponse(
      response.data,
    );
  }

  // =========================================================
  // HELPERS
  // =========================================================

  String _normaliseEndpoint(
    String endpoint,
  ) {
    final cleaned = endpoint.trim();

    if (cleaned.isEmpty) {
      return '/';
    }

    if (cleaned.startsWith('/')) {
      return cleaned;
    }

    return '/$cleaned';
  }

  Map<String, dynamic> _mapResponse(
    dynamic response,
  ) {
    if (response == null) {
      return <String, dynamic>{};
    }

    if (response is Map<String, dynamic>) {
      return response;
    }

    if (response is Map) {
      return Map<String, dynamic>.from(
        response,
      );
    }

    return <String, dynamic>{
      'data': response,
    };
  }
}

class ApiServiceException implements Exception {
  const ApiServiceException(
    this.message,
  );

  final String message;

  @override
  String toString() => message;
}
