import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';

import '../core/logger.dart';
import '../core/network/app_exception.dart';
import 'api_service.dart';
import 'local_database.dart';
import 'notification_service.dart';

/// Offline-first synchronisation: pulls /sync into SQLite and pushes the
/// queued offline actions to /offline-actions when a connection returns.
class SyncService {
  SyncService._();

  static final SyncService instance = SyncService._();

  final LocalDatabase _db = LocalDatabase.instance;
  final ApiService _api = ApiService.instance;
  final Uuid _uuid = const Uuid();

  StreamSubscription<List<ConnectivityResult>>? _subscription;
  final StreamController<bool> _onlineController =
      StreamController<bool>.broadcast();

  Future<void>? _running;

  Stream<bool> get onlineChanges => _onlineController.stream;

  /// Incremented after every successful sync or local change so screens
  /// reading from the cache can reload.
  final ValueNotifier<int> dataVersion = ValueNotifier<int>(0);

  /// True while a sync is in progress.
  final ValueNotifier<bool> syncing = ValueNotifier<bool>(false);

  void notifyLocalChange() => dataVersion.value++;

  Future<bool> isOnline() async {
    final results = await Connectivity().checkConnectivity();
    return results.any((result) => result != ConnectivityResult.none);
  }

  void startAutoSync() {
    _subscription?.cancel();

    _subscription =
        Connectivity().onConnectivityChanged.listen((results) async {
      final online = results.any((result) => result != ConnectivityResult.none);
      _onlineController.add(online);

      if (online) {
        try {
          await syncNow();
        } catch (error) {
          appLog('Auto sync failed', error);
        }
      }
    });
  }

  Future<void> stopAutoSync() async {
    await _subscription?.cancel();
    _subscription = null;
  }

  Future<void> queueAction(
    String type,
    Map<String, dynamic> payload,
  ) async {
    await _db.enqueueOperation(
      clientOperationId: _uuid.v4(),
      type: type,
      payload: payload,
    );
    notifyLocalChange();
  }

  Future<void> queueAssignmentSubmission({
    required int assessmentId,
    String? text,
    String? localFilePath,
  }) async {
    await queueAction('assignment_submission', {
      'assessment_id': assessmentId,
      'submission_text': text,
      'local_file_path': localFilePath,
    });
  }

  Future<void> flushOfflineActions() async {
    if (!await isOnline()) return;

    final pending = await _db.pendingOperations();
    if (pending.isEmpty) return;

    final completed = <String>[];
    final serverOperations = <Map<String, dynamic>>[];

    for (final item in pending) {
      final type = item['type']?.toString() ?? '';
      final clientId = item['client_operation_id']?.toString() ?? '';
      final payload = Map<String, dynamic>.from(item['payload'] as Map? ?? {});

      if (type == 'assignment_submission') {
        try {
          await _api.submitAssignment(
            assessmentId: int.parse(payload['assessment_id'].toString()),
            text: payload['submission_text']?.toString(),
            localFilePath: payload['local_file_path']?.toString(),
            clientSubmissionId: clientId,
          );
          completed.add(clientId);
        } catch (error) {
          final mapped = AppException.from(error);
          if (mapped.isRetryable) {
            appLog('Queued submission failed; will retry', error);
          } else {
            // 403/404/422 (e.g. "Maximum attempts reached") never succeed.
            appLog('Dropping queued submission (${mapped.statusCode})');
            completed.add(clientId);
          }
        }
        continue;
      }

      serverOperations.add({
        'client_operation_id': clientId,
        'type': type,
        'payload': payload,
      });
    }

    // The backend accepts at most 100 operations per request.
    for (var i = 0; i < serverOperations.length; i += 100) {
      final chunk = serverOperations.sublist(
        i,
        i + 100 > serverOperations.length ? serverOperations.length : i + 100,
      );

      final result = await _api.postOfflineActions(chunk);
      final rows = result['results'];

      if (rows is List) {
        for (final raw in rows) {
          if (raw is! Map) continue;
          final status = raw['status']?.toString();
          final code = int.tryParse(raw['code']?.toString() ?? '');
          // Drop processed/duplicate ones, and failures that can never
          // succeed (4xx such as 403/404). Retry 5xx and unknown failures.
          final permanentFailure = status == 'failed' &&
              code != null &&
              code >= 400 &&
              code < 500 &&
              code != 408 &&
              code != 429;
          if (status == 'processed' ||
              status == 'duplicate' ||
              permanentFailure) {
            completed.add(raw['client_operation_id'].toString());
          }
        }
      }
    }

    await _db.removeOperations(completed);
  }

  /// /sync returns courses without modules or lessons, so fetch the lesson
  /// tree for changed courses and any course not cached yet. This keeps
  /// lessons readable offline before a course has ever been opened.
  Future<void> _prefetchCourseTrees(dynamic changedCourses) async {
    final changed = <int>{
      if (changedCourses is List)
        for (final c in changedCourses.whereType<Map>())
          if (int.tryParse(c['id']?.toString() ?? '') != null)
            int.parse(c['id'].toString()),
    };

    final ids = <int>{...changed};
    for (final course in await _db.readCollection('courses')) {
      final id = int.tryParse(course['id']?.toString() ?? '');
      if (id == null) continue;
      if (await _db.readItem('course_details', 'course_$id') == null) ids.add(id);
    }

    for (final id in ids.take(30)) {
      try {
        final response = await _api.course(id);
        final course = response['course'];
        if (course is Map) {
          await _db.cacheItem(
            collection: 'course_details',
            itemId: 'course_$id',
            payload: Map<String, dynamic>.from(course),
          );
        }
      } catch (error) {
        appLog('Prefetch of course $id failed', error);
      }
    }
  }

  /// Runs a full sync. Concurrent calls share the same run.
  Future<void> syncNow() {
    return _running ??= _syncNow().whenComplete(() => _running = null);
  }

  Future<void> _syncNow() async {
    if (!await isOnline()) return;

    syncing.value = true;
    try {
      await flushOfflineActions();

      final lastSync = await _db.getMeta('last_synced_at');
      final data = await _api.sync(lastSyncedAt: lastSync);

      for (final key in [
        'enrolments',
        'courses',
        'assignments',
        'announcements',
        'mentorship',
        'jobs',
        'events',
        'notifications',
        'lesson_progress',
        'local_reminders',
      ]) {
        final items = data[key];

        if (items is List) {
          if (lastSync == null || lastSync.isEmpty) {
            await _db.replaceCollection(key, items);
          } else {
            await _db.mergeCollection(key, items);
          }
        }
      }

      final lastSyncedAt =
          (data['last_synced_at'] ?? data['server_time'])?.toString();
      await _db.setMeta('last_synced_at', lastSyncedAt);

      final reminders = data['local_reminders'];
      if (reminders is List) {
        await NotificationService.instance.scheduleFromSync(reminders);
      }

      await _prefetchCourseTrees(data['courses']);

      dataVersion.value++;
    } finally {
      syncing.value = false;
    }
  }
}
