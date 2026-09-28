import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/app_config.dart';

class ApiService {
  ApiService._();

  static final ApiService instance = ApiService._();

  final FlutterSecureStorage _secure = const FlutterSecureStorage();

  late final Dio dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 45),
      headers: {'Accept': 'application/json'},
    ),
  )..interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _secure.read(key: 'auth_token');
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );

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

    final data = Map<String, dynamic>.from(response.data as Map);
    await _secure.write(key: 'auth_token', value: data['token']?.toString());
    await _secure.write(
      key: 'current_user',
      value: jsonEncode(data['user'] ?? {}),
    );

    return data;
  }

  Future<void> logout() async {
    try {
      await dio.post('/logout');
    } finally {
      await _secure.delete(key: 'auth_token');
      await _secure.delete(key: 'current_user');
    }
  }

  Future<bool> hasToken() async =>
      (await _secure.read(key: 'auth_token'))?.isNotEmpty == true;

  Future<Map<String, dynamic>?> currentUser() async {
    final raw = await _secure.read(key: 'current_user');
    if (raw == null || raw.isEmpty) return null;
    return Map<String, dynamic>.from(jsonDecode(raw) as Map);
  }

  Future<Map<String, dynamic>> sync({String? lastSyncedAt}) async {
    final response = await dio.get(
      '/sync',
      queryParameters: {
        if (lastSyncedAt != null && lastSyncedAt.isNotEmpty)
          'last_synced_at': lastSyncedAt,
      },
    );

    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<Map<String, dynamic>> course(int id) async {
    final response = await dio.get('/courses/$id');
    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<Map<String, dynamic>> postOfflineActions(
    List<Map<String, dynamic>> operations,
  ) async {
    final response = await dio.post(
      '/offline-actions',
      data: {'operations': operations},
    );

    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<void> submitAssignment({
    required int assessmentId,
    String? text,
    String? localFilePath,
    String? clientSubmissionId,
  }) async {
    MultipartFile? file;

    if (localFilePath != null &&
        localFilePath.isNotEmpty &&
        File(localFilePath).existsSync()) {
      file = await MultipartFile.fromFile(localFilePath);
    }

    final form = FormData.fromMap({
      if (text != null && text.trim().isNotEmpty)
        'submission_text': text.trim(),
      if (clientSubmissionId != null)
        'client_submission_id': clientSubmissionId,
      if (file != null) 'submission_file': file,
    });

    await dio.post('/assignments/$assessmentId/submit', data: form);
  }

  Future<void> saveJob(int jobId) async => dio.post('/jobs/$jobId/save');

  Future<void> unsaveJob(int jobId) async => dio.delete('/jobs/$jobId/save');

  Future<void> markLessonProgress({
    required int lessonId,
    required bool completed,
    int seconds = 0,
  }) async {
    await dio.put(
      '/lessons/$lessonId/progress',
      data: {
        'completed': completed,
        'time_spent_seconds': seconds,
      },
    );
  }

  Future<void> markNotificationRead(int notificationId) async {
    await dio.put('/notifications/$notificationId/read');
  }

  Future<void> registerDeviceToken({
    required String deviceId,
    required String token,
    required String platform,
    String? appVersion,
  }) async {
    await dio.post(
      '/device-token',
      data: {
        'device_id': deviceId,
        'token': token,
        'platform': platform,
        if (appVersion != null) 'app_version': appVersion,
      },
    );
  }
}
