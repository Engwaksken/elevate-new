import 'package:dio/dio.dart';

import '../core/appointment_info.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import 'api_service.dart';

/// Instructor appointments endpoints under `/api/v1/participant/appointments`.
///
/// Uses the shared [ApiService.instance.dio] client (base URL, bearer token
/// and error mapping interceptor). Every method throws [AppException] on
/// failure, never a raw [DioException].
class AppointmentsApi {
  AppointmentsApi({Dio? dio}) : _dioOverride = dio;

  static final AppointmentsApi instance = AppointmentsApi();

  final Dio? _dioOverride;

  Dio get _dio => _dioOverride ?? ApiService.instance.dio;

  static const _base = '/appointments';

  Future<T> _guard<T>(Future<T> Function() call) async {
    try {
      return await call();
    } on AppException {
      rethrow;
    } catch (error, stack) {
      if (error is! DioException) appLog('Unexpected appointments API error', error, stack);
      throw AppException.from(error);
    }
  }

  static Map<String, dynamic> _map(dynamic data) {
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return <String, dynamic>{};
  }

  AppointmentInfo _appointment(dynamic data) {
    final info = AppointmentInfo.fromJson(_map(data)['appointment']);
    if (info == null) {
      throw const AppException(AppErrorKind.unknown, AppException.genericMessage);
    }
    return info;
  }

  /// GET /appointments?scope=upcoming|past|all
  Future<AppointmentList> list({String scope = 'all'}) => _guard(() async {
        final response = await _dio.get(_base, queryParameters: {'scope': scope});
        return AppointmentList.fromJson(_map(response.data));
      });

  /// GET /appointments/options
  Future<AppointmentOptions> options() => _guard(() async {
        final response = await _dio.get('$_base/options');
        return AppointmentOptions.fromJson(_map(response.data));
      });

  /// GET /appointments/{id}
  Future<AppointmentInfo> show(int id) => _guard(() async {
        final response = await _dio.get('$_base/$id');
        return _appointment(response.data);
      });

  /// POST /appointments -> 201 `{message, appointment}`.
  ///
  /// [localStart] is the wall-clock time the participant picked on the
  /// device; it is sent as UTC ISO 8601 in `starts_at`.
  Future<AppointmentInfo> create({
    required int instructorId,
    required int courseId,
    required DateTime localStart,
    required int durationMinutes,
    required String mode,
    required String topic,
    String? details,
  }) =>
      _guard(() async {
        final response = await _dio.post(_base, data: {
          'instructor_user_id': instructorId,
          'course_id': courseId,
          'starts_at': appointmentStartPayload(localStart),
          'duration_minutes': durationMinutes,
          'mode': mode,
          'topic': topic.trim(),
          if (details != null && details.trim().isNotEmpty) 'details': details.trim(),
        });
        return _appointment(response.data);
      });

  /// POST /appointments/{id}/accept-proposal
  Future<AppointmentInfo> acceptProposal(int id) => _guard(() async {
        final response = await _dio.post('$_base/$id/accept-proposal');
        return _appointment(response.data);
      });

  /// POST /appointments/{id}/decline-proposal
  Future<AppointmentInfo> declineProposal(int id) => _guard(() async {
        final response = await _dio.post('$_base/$id/decline-proposal');
        return _appointment(response.data);
      });

  /// POST /appointments/{id}/cancel `{reason?}`
  Future<AppointmentInfo> cancel(int id, {String? reason}) => _guard(() async {
        final response = await _dio.post('$_base/$id/cancel', data: {
          if (reason != null && reason.trim().isNotEmpty) 'reason': reason.trim(),
        });
        return _appointment(response.data);
      });
}
