import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/app_config.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import '../core/session_events.dart';

/// Single HTTP client for the participant API
/// (base: https://site.elevateher360.org/api/v1/participant).
///
/// Every public method throws [AppException] on failure, never a raw
/// [DioException], so UI code can show `error.message` safely.
class ApiService {
  ApiService._();

  static final ApiService instance = ApiService._();

  static const String _tokenKey = 'auth_token';
  static const String _currentUserKey = 'current_user';

  final FlutterSecureStorage _secure = const FlutterSecureStorage();

  late final Uri _apiUri = Uri.parse(AppConfig.apiBaseUrl);

  late final Dio dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 45),
      sendTimeout: const Duration(seconds: 45),
      headers: const {'Accept': 'application/json'},
      responseType: ResponseType.json,
    ),
  )..interceptors.add(
      InterceptorsWrapper(
        onRequest: _onRequest,
        onError: _onError,
      ),
    );

  Future<void> _onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Only send the bearer token to our own API host, never to third-party
    // file hosts or CDNs that a lesson URL may point at.
    if (options.uri.host == _apiUri.host) {
      final token = await getToken();
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    } else {
      options.headers.remove('Authorization');
    }

    handler.next(options);
  }

  Future<void> _onError(
    DioException error,
    ErrorInterceptorHandler handler,
  ) async {
    final mapped = AppException.fromDio(error);

    appLog(
      'HTTP ${error.requestOptions.method} ${error.requestOptions.uri.path} '
      'failed (${error.response?.statusCode ?? error.type.name})',
    );

    final isLogin = error.requestOptions.path.endsWith('/login');
    final isOwnHost = error.requestOptions.uri.host == _apiUri.host;

    if (mapped.kind == AppErrorKind.unauthorised && !isLogin && isOwnHost) {
      if (await hasToken()) {
        await clearSession();
        SessionEvents.instance.notifyExpired(mapped.message);
      }
    }

    handler.next(error.copyWith(error: mapped, message: mapped.message));
  }

  /// Runs [call] and converts any failure into an [AppException].
  Future<T> _guard<T>(Future<T> Function() call) async {
    try {
      return await call();
    } on AppException {
      rethrow;
    } catch (error, stack) {
      if (error is! DioException) {
        appLog('Unexpected API error', error, stack);
      }
      throw AppException.from(error);
    }
  }

  Future<Map<String, dynamic>> _getMap(
    String path, {
    Map<String, dynamic>? query,
  }) =>
      _guard(() async {
        final response = await dio.get(path, queryParameters: query);
        return _mapResponse(response.data);
      });

  // =========================================================
  // TOKEN / SESSION
  // =========================================================

  Future<String?> getToken() async {
    final token = (await _secure.read(key: _tokenKey))?.trim();
    return token == null || token.isEmpty ? null : token;
  }

  Future<void> saveToken(String token) async {
    final cleaned = token.trim();
    if (cleaned.isEmpty) return;
    await _secure.write(key: _tokenKey, value: cleaned);
  }

  Future<bool> hasToken() async => await getToken() != null;

  // =========================================================
  // AUTHENTICATION
  // =========================================================

  /// POST /api/v1/participant/login (outside the auth group).
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) =>
      _guard(() async {
        final response = await dio.post(
          '/login',
          data: {
            'email': email.trim().toLowerCase(),
            'password': password,
            'device_name': 'ElevateHer360 ${Platform.operatingSystem}',
          },
        );

        final data = _mapResponse(response.data);
        final token = data['token']?.toString();

        if (token == null || token.trim().isEmpty) {
          throw const AppException(
            AppErrorKind.unknown,
            "We couldn't sign you in. Please try again.",
          );
        }

        await saveToken(token);

        final user = data['user'];
        if (user is Map) {
          await saveCurrentUser(Map<String, dynamic>.from(user));
        }

        return data;
      });

  Future<void> logout() async {
    try {
      if (await hasToken()) {
        await dio.post('/logout');
      }
    } catch (_) {
      // Logout must still clear the local session when offline.
    } finally {
      await clearSession();
    }
  }

  Future<void> clearSession() async {
    await _secure.delete(key: _tokenKey);
    await _secure.delete(key: _currentUserKey);
  }

  Future<Map<String, dynamic>?> currentUser() async {
    final raw = await _secure.read(key: _currentUserKey);
    if (raw == null || raw.trim().isEmpty) return null;

    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map) return Map<String, dynamic>.from(decoded);
    } catch (_) {
      await _secure.delete(key: _currentUserKey);
    }
    return null;
  }

  Future<void> saveCurrentUser(Map<String, dynamic> user) async {
    await _secure.write(key: _currentUserKey, value: jsonEncode(user));
  }

  // =========================================================
  // PARTICIPANT
  // =========================================================

  Future<Map<String, dynamic>> me() async {
    final data = await _getMap('/me');
    final user = data['user'];
    if (user is Map) {
      await saveCurrentUser(Map<String, dynamic>.from(user));
    }
    return data;
  }

  Future<Map<String, dynamic>> dashboard() => _getMap('/dashboard');

  // =========================================================
  // PROFILE
  // =========================================================

  /// GET /profile -> `{profile: {...}, editable_fields: [...]}`.
  Future<Map<String, dynamic>> profile() => _getMap('/profile');

  /// PUT /profile with the editable fields. 422 carries `errors`.
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> fields) =>
      _guard(() async {
        final response = await dio.put('/profile', data: fields);
        return _mapResponse(response.data);
      });

  /// POST /profile/photo (multipart `photo`, jpg/png/webp, 5 MB max).
  Future<Map<String, dynamic>> uploadProfilePhoto(String localPath) =>
      _guard(() async {
        final file = File(localPath);
        final form = FormData.fromMap({
          'photo': await MultipartFile.fromFile(
            file.path,
            filename: file.uri.pathSegments.last,
          ),
        });
        final response = await dio.post(
          '/profile/photo',
          data: form,
          options: Options(contentType: 'multipart/form-data'),
        );
        return _mapResponse(response.data);
      });

  /// DELETE /profile/photo.
  Future<Map<String, dynamic>> deleteProfilePhoto() => _guard(() async {
        final response = await dio.delete('/profile/photo');
        return _mapResponse(response.data);
      });

  /// GET /profile/photo (authenticated image stream) saved to [savePath].
  Future<void> downloadProfilePhoto(String savePath) => _guard(() async {
        await dio.download(
          '/profile/photo',
          savePath,
          options: Options(headers: {'Accept': 'image/*'}),
        );
      });

  /// PUT /profile/password. 422 (e.g. wrong current password) has `errors`.
  Future<Map<String, dynamic>> changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) =>
      _guard(() async {
        final response = await dio.put('/profile/password', data: {
          'current_password': currentPassword,
          'password': password,
          'password_confirmation': confirmation,
        });
        return _mapResponse(response.data);
      });

  // =========================================================
  // PROGRESS
  // =========================================================

  /// GET /progress -> `{summary, courses, recent_activity}`.
  Future<Map<String, dynamic>> progress() => _getMap('/progress');

  /// Help & Support contacts managed in Admin > Support Settings.
  Future<Map<String, dynamic>> support() => _getMap('/support');

  // =========================================================
  // SYNCHRONISATION
  // =========================================================

  /// GET /sync?last_synced_at=... (omit for a full sync).
  Future<Map<String, dynamic>> sync({String? lastSyncedAt}) {
    final since = lastSyncedAt?.trim() ?? '';
    return _getMap(
      '/sync',
      query: since.isEmpty ? null : {'last_synced_at': since},
    );
  }

  /// POST /offline-actions. The backend expects
  /// `{operations: [{client_operation_id, type, payload}]}` and returns
  /// `{results: [{client_operation_id, status}]}`.
  Future<Map<String, dynamic>> postOfflineActions(
    List<Map<String, dynamic>> operations,
  ) async {
    if (operations.isEmpty) return {'results': <dynamic>[]};

    return _guard(() async {
      final response = await dio.post(
        '/offline-actions',
        data: {'operations': operations},
      );
      return _mapResponse(response.data);
    });
  }

  // =========================================================
  // COURSES AND LESSONS
  // =========================================================

  Future<Map<String, dynamic>> courses({int page = 1, String? search}) =>
      _getMap('/courses', query: {
        'page': page,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      });

  Future<Map<String, dynamic>> course(int id) => _getMap('/courses/$id');

  /// GET /lessons/{id} -> `{"lesson": {...}}`. Also records "opened" on
  /// the server. Returns 403 for locked modules.
  Future<Map<String, dynamic>> lesson(int id) async {
    final data = await _getMap('/lessons/$id');
    for (final key in ['lesson', 'data']) {
      final inner = data[key];
      if (inner is Map) return Map<String, dynamic>.from(inner);
    }
    return data;
  }

  /// Streams an authenticated API file (lesson file, extra lesson file or
  /// assignment attachment) to [savePath].
  ///
  /// [path] is a `download_path` relative to the participant base
  /// (e.g. `/lessons/1/download`) or an absolute URL on the API host.
  /// Public `/storage/...` URLs are deliberately not supported: they are
  /// unauthenticated and were the source of broken (404) downloads.
  Future<Response<dynamic>> downloadApiFile({
    required String path,
    required String savePath,
    ProgressCallback? onProgress,
    CancelToken? cancelToken,
  }) =>
      _guard(() {
        final target = resolveApiPath(path);
        if (target == null) {
          throw const AppException(
            AppErrorKind.notFound,
            "This file isn't available yet.",
          );
        }
        return dio.download(
          target,
          savePath,
          onReceiveProgress: onProgress,
          cancelToken: cancelToken,
          options: Options(headers: {'Accept': '*/*'}),
        );
      });

  /// Returns a Dio path for [value] if it is a relative API path or an
  /// absolute URL on the API host; otherwise null.
  String? resolveApiPath(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return null;

    final uri = Uri.tryParse(text);
    if (uri == null) return null;

    if (!uri.hasScheme) {
      if (text.startsWith('/storage/')) return null;
      return text.startsWith('/') ? text : '/$text';
    }

    if (uri.host != _apiUri.host) return null;
    if (!uri.path.startsWith(_apiUri.path)) return null;
    return text;
  }

  /// PUT /lessons/{id}/progress. Response: `lesson_id`, `completed`,
  /// `completed_at`, `course_progress_percent`, `course_status`.
  Future<Map<String, dynamic>> markLessonProgress({
    required int lessonId,
    required bool completed,
    int seconds = 0,
  }) =>
      _guard(() async {
        final response = await dio.put(
          '/lessons/$lessonId/progress',
          data: {
            'completed': completed,
            // Reading time is sent separately as time_spent_seconds_delta
            // (ReadingTimeService); legacy seconds only when given.
            if (seconds > 0) 'time_spent_seconds': seconds,
          },
        );
        return _mapResponse(response.data);
      });

  // =========================================================
  // ASSIGNMENTS
  // =========================================================

  Future<Map<String, dynamic>> assignments({int page = 1}) =>
      _getMap('/assignments', query: {'page': page});

  /// POST /assignments/{id}/extension-requests -> 201 `{extension_request}`.
  Future<Map<String, dynamic>> requestExtension({
    required int assessmentId,
    required String reason,
    DateTime? requestedDueAt,
  }) =>
      _guard(() async {
        final response = await dio.post(
          '/assignments/$assessmentId/extension-requests',
          data: {
            'reason': reason.trim(),
            if (requestedDueAt != null)
              'requested_due_at': requestedDueAt.toUtc().toIso8601String(),
          },
        );
        return _mapResponse(response.data);
      });

  Future<Map<String, dynamic>> submitAssignment({
    required int assessmentId,
    String? text,
    String? localFilePath,
    String? clientSubmissionId,
    String? clientCreatedAt,
  }) =>
      _guard(() async {
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
          if (clientSubmissionId != null &&
              clientSubmissionId.trim().isNotEmpty)
            'client_submission_id': clientSubmissionId.trim(),
          // Only honoured with client_submission_id (queued submissions).
          if (clientSubmissionId != null &&
              clientCreatedAt != null &&
              clientCreatedAt.trim().isNotEmpty)
            'client_created_at': clientCreatedAt.trim(),
          if (file != null) 'submission_file': file,
        });

        final response = await dio.post(
          '/assignments/$assessmentId/submit',
          data: form,
          options: Options(contentType: 'multipart/form-data'),
        );
        return _mapResponse(response.data);
      });

  // =========================================================
  // MENTORSHIP, JOBS, EVENTS, ANNOUNCEMENTS
  // =========================================================

  Future<Map<String, dynamic>> mentorship() => _getMap('/mentorship');

  /// POST /mentorship/sessions/{id}/attendance `{attended}` (sessions from
  /// the past 14 days only).
  Future<Map<String, dynamic>> recordAttendance({
    required int sessionId,
    required bool attended,
  }) =>
      _guard(() async {
        final response = await dio.post(
          '/mentorship/sessions/$sessionId/attendance',
          data: {'attended': attended},
        );
        return _mapResponse(response.data);
      });

  Future<Map<String, dynamic>> jobs({int page = 1, String? search}) =>
      _getMap('/jobs', query: {
        'page': page,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      });

  Future<Map<String, dynamic>> saveJob(int jobId) => _guard(() async {
        final response = await dio.post('/jobs/$jobId/save');
        return _mapResponse(response.data);
      });

  Future<Map<String, dynamic>> unsaveJob(int jobId) => _guard(() async {
        final response = await dio.delete('/jobs/$jobId/save');
        return _mapResponse(response.data);
      });

  Future<Map<String, dynamic>> events({int page = 1}) =>
      _getMap('/events', query: {'page': page});

  Future<Map<String, dynamic>> announcements({int page = 1}) =>
      _getMap('/announcements', query: {'page': page});

  // =========================================================
  // NOTIFICATIONS
  // =========================================================

  Future<Map<String, dynamic>> notifications({int page = 1}) =>
      _getMap('/notifications', query: {'page': page});

  Future<Map<String, dynamic>> markNotificationRead(dynamic notificationId) =>
      _guard(() async {
        final response = await dio.put('/notifications/$notificationId/read');
        return _mapResponse(response.data);
      });

  // =========================================================
  // PUSH NOTIFICATIONS / DEVICE TOKEN
  // =========================================================

  /// POST /device-token (`fcm_token` is accepted as an alias of `token`).
  Future<Map<String, dynamic>> registerDeviceToken({
    required String deviceId,
    required String token,
    required String platform,
    String? appVersion,
  }) =>
      _guard(() async {
        final response = await dio.post(
          '/device-token',
          data: {
            'device_id': deviceId.trim(),
            'token': token.trim(),
            'platform': platform.trim(),
            'app_version': (appVersion ?? AppConfig.appVersion).trim(),
          },
        );
        return _mapResponse(response.data);
      });

  /// DELETE /device-token with `{device_id}`.
  Future<void> unregisterDeviceToken({required String deviceId}) =>
      _guard(() async {
        await dio.delete('/device-token', data: {'device_id': deviceId.trim()});
      });

  // =========================================================
  // HELPERS
  // =========================================================

  Map<String, dynamic> _mapResponse(dynamic response) {
    if (response == null) return <String, dynamic>{};
    if (response is Map<String, dynamic>) return response;
    if (response is Map) return Map<String, dynamic>.from(response);
    return <String, dynamic>{'data': response};
  }
}
