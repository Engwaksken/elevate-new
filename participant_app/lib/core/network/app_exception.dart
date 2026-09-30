import 'dart:io';

import 'package:dio/dio.dart';

enum AppErrorKind {
  offline,
  timeout,
  unauthorised,
  forbidden,
  notFound,
  validation,
  server,
  wifiOnly,
  unknown,
}

/// A user-safe error. [message] is always suitable for display; raw
/// exception text is never exposed to the user.
class AppException implements Exception {
  const AppException(
    this.kind,
    this.message, {
    this.statusCode,
    this.fieldErrors = const {},
    this.code,
  });

  final AppErrorKind kind;
  final String message;
  final int? statusCode;

  /// Laravel 422 `errors` map (field -> first message) for inline display.
  final Map<String, String> fieldErrors;

  /// Machine-readable business-rule code from the API body (`code`), e.g.
  /// `overdue` when an assignment's due date has passed.
  final String? code;

  /// True for the 422 "the due date has passed" rule on submissions.
  bool get isOverdue =>
      kind == AppErrorKind.validation && code?.toLowerCase() == 'overdue';

  String? fieldError(String field) => fieldErrors[field];

  /// True for network problems and 5xx/429 responses: trying again later
  /// may succeed. 4xx responses (403, 404, 422...) are permanent.
  bool get isRetryable =>
      kind == AppErrorKind.offline ||
      kind == AppErrorKind.timeout ||
      kind == AppErrorKind.server ||
      (kind == AppErrorKind.unknown && statusCode == null);

  static const offline = AppException(
    AppErrorKind.offline,
    'You appear to be offline. Check your connection and try again.',
  );

  static const genericMessage = 'Something went wrong. Please try again.';

  /// Converts any thrown object into an [AppException].
  static AppException from(Object error) {
    if (error is AppException) return error;

    if (error is DioException) {
      final inner = error.error;
      if (inner is AppException) return inner;
      return fromDio(error);
    }

    if (error is SocketException) return offline;

    return const AppException(AppErrorKind.unknown, genericMessage);
  }

  static AppException fromDio(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const AppException(
          AppErrorKind.timeout,
          'The connection is slow and the request timed out. Please try again.',
        );
      case DioExceptionType.connectionError:
        return offline;
      case DioExceptionType.badCertificate:
        return const AppException(
          AppErrorKind.unknown,
          'A secure connection could not be established.',
        );
      case DioExceptionType.cancel:
        return const AppException(
          AppErrorKind.unknown,
          'The request was cancelled.',
        );
      case DioExceptionType.badResponse:
        return _fromStatus(error.response);
      default:
        // Includes DioExceptionType.unknown and timeout types added in
        // newer Dio versions (e.g. transformTimeout).
        if (error.error is SocketException) return offline;
        if (error.type.name.toLowerCase().contains('timeout')) {
          return const AppException(
            AppErrorKind.timeout,
            'The connection is slow and the request timed out. Please try again.',
          );
        }
        return const AppException(AppErrorKind.unknown, genericMessage);
    }
  }

  static const _notFoundMessage = "This content isn't available yet.";

  static AppException _fromStatus(Response<dynamic>? response) {
    final status = response?.statusCode ?? 0;
    final data = response?.data;
    final server = _serverMessage(data);

    if (status == 401) {
      return const AppException(
        AppErrorKind.unauthorised,
        'Your session has expired. Please sign in again.',
        statusCode: 401,
      );
    }

    if (status == 403) {
      return AppException(
        AppErrorKind.forbidden,
        server ?? "You don't have access to this content.",
        statusCode: 403,
      );
    }

    if (status == 404 || status == 405) {
      // Generic "unknown route" messages are replaced with friendlier text;
      // specific ones ("This lesson has no downloadable file.") are kept.
      final generic = server == null ||
          server.toLowerCase().contains('endpoint does not exist') ||
          server.toLowerCase().contains('not supported for route') ||
          server.toLowerCase() == 'not found.' ||
          server.toLowerCase() == 'not found';
      return AppException(
        AppErrorKind.notFound,
        generic ? _notFoundMessage : server,
        statusCode: status,
      );
    }

    if (status == 422) {
      return AppException(
        AppErrorKind.validation,
        server ?? 'Please check the details you entered.',
        statusCode: 422,
        fieldErrors: _fieldErrors(data),
        code: errorCode(data),
      );
    }

    if (status == 429) {
      return const AppException(
        AppErrorKind.server,
        'Too many attempts. Please wait a minute and try again.',
        statusCode: 429,
      );
    }

    if (status >= 500) {
      return AppException(
        AppErrorKind.server,
        'Our server is having trouble right now. Please try again later.',
        statusCode: status,
      );
    }

    return AppException(
      AppErrorKind.unknown,
      server ?? genericMessage,
      statusCode: status == 0 ? null : status,
    );
  }

  /// Reads a string `code` from an API error body, if present.
  static String? errorCode(dynamic data) {
    if (data is! Map) return null;
    final value = data['code']?.toString().trim() ?? '';
    return value.isEmpty ? null : value;
  }

  static Map<String, String> _fieldErrors(dynamic data) {
    if (data is! Map || data['errors'] is! Map) return const {};
    final result = <String, String>{};
    (data['errors'] as Map).forEach((key, value) {
      final first = value is List && value.isNotEmpty ? value.first : value;
      final text = first?.toString().trim() ?? '';
      if (text.isNotEmpty) result[key.toString()] = text;
    });
    return result;
  }

  /// The API's human-readable `message` (always JSON on api/* routes).
  /// Ignores anything that looks like HTML, a stack trace or is too long.
  static String? _serverMessage(dynamic data) {
    if (data is! Map) return null;

    String? clean(dynamic value) {
      final text = value?.toString().trim() ?? '';
      if (text.isEmpty || text.length > 200) return null;
      if (text.contains('<') || text.contains('Exception') || text.contains('SQLSTATE')) {
        return null;
      }
      return text;
    }

    final message = clean(data['message']);
    if (message != null) return message;

    final errors = data['errors'];
    if (errors is Map) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) {
          final text = clean(value.first);
          if (text != null) return text;
        }
      }
    }
    return null;
  }

  @override
  String toString() => message;
}

/// Shorthand used by the UI layer.
String friendlyError(Object error) => AppException.from(error).message;
