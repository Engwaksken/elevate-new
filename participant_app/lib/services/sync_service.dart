import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';

import '../core/logger.dart';
import '../core/network/app_exception.dart';
import 'api_service.dart';
import 'local_database.dart';
import 'notification_service.dart';
import 'participant_data_service.dart';
import 'reading_time_service.dart';

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
    await queueAction(submissionOperation, {
      'assessment_id': assessmentId,
      'submission_text': text,
      'local_file_path': localFilePath,
      // When she pressed Submit: lets the server apply the 72-hour offline
      // grace window if the due date passes before the device reconnects.
      'client_created_at': DateTime.now().toUtc().toIso8601String(),
    });
  }

  Future<void> queueAttendance({
    required int sessionId,
    required bool attended,
  }) =>
      queueAction(attendanceOperation, {
        'session_id': sessionId,
        'attended': attended,
      });

  /// Queued operation types sent to their own endpoint by the app rather
  /// than through /offline-actions.
  static const String submissionOperation = 'assignment_submission';
  static const String attendanceOperation = 'mentorship_attendance';

  Future<void>? _flushing;

  /// Sends the offline queue. Concurrent calls share one run, so an
  /// operation is never sent twice at the same time.
  Future<void> flushOfflineActions() {
    return _flushing ??= _flush().whenComplete(() => _flushing = null);
  }

  /// Marks a cached assignment as closed after the server rejected a
  /// submission with code "overdue", so the screen shows the extension card.
  Future<void> markAssignmentOverdue(int assessmentId, {bool fromQueue = false}) async {
    if (fromQueue) {
      // Kept apart from the assignment itself, which the next refresh
      // replaces with the server's copy.
      await _db.cacheItem(
        collection: rejectedSubmissionsCollection,
        itemId: assessmentId.toString(),
        payload: {
          'assessment_id': assessmentId,
          'reason': 'overdue',
          'at': DateTime.now().toUtc().toIso8601String(),
        },
      );
    }
    final item = await _db.readItem('assignments', assessmentId.toString());
    if (item != null) {
      item['is_overdue'] = true;
      item['can_submit'] = false;
      await _db.cacheItem(
        collection: 'assignments',
        itemId: assessmentId.toString(),
        payload: item,
      );
    }
    notifyLocalChange();
  }

  /// Queued submissions the server rejected as overdue (by assessment id).
  static const String rejectedSubmissionsCollection = 'rejected_submissions';

  static bool _isPermanent(AppException error) => !error.isRetryable;

  Future<void> _flush() async {
    if (!await isOnline()) return;

    final pending = await _db.pendingOperations();
    if (pending.isEmpty) return;

    final completed = <String>[];
    final serverOperations = <Map<String, dynamic>>[];
    final acknowledged = <String, Map<String, dynamic>>{};
    final rejected = <String>[];

    for (final item in pending) {
      final type = item['type']?.toString() ?? '';
      final clientId = item['client_operation_id']?.toString() ?? '';
      final payload = Map<String, dynamic>.from(item['payload'] as Map? ?? {});

      if (type == submissionOperation) {
        final assessmentId = int.tryParse(payload['assessment_id'].toString());
        try {
          await _api.submitAssignment(
            assessmentId: assessmentId ?? 0,
            text: payload['submission_text']?.toString(),
            localFilePath: payload['local_file_path']?.toString(),
            clientSubmissionId: clientId,
            // Older queued items have no client_created_at: fall back to
            // the time the operation was queued.
            clientCreatedAt: payload['client_created_at']?.toString() ??
                item['created_at']?.toString(),
          );
          completed.add(clientId);
        } catch (error) {
          final mapped = AppException.from(error);
          if (!_isPermanent(mapped)) {
            appLog('Queued submission failed; will retry', error);
          } else {
            // 403/404/422 (e.g. "Maximum attempts reached", or the due
            // date passed) never succeed: drop them.
            appLog('Dropping queued submission (${mapped.statusCode} ${mapped.code ?? ''})');
            if (mapped.isOverdue && assessmentId != null) {
              await markAssignmentOverdue(assessmentId, fromQueue: true);
            }
            completed.add(clientId);
          }
        }
        continue;
      }

      if (type == attendanceOperation) {
        try {
          await _api.recordAttendance(
            sessionId: int.parse(payload['session_id'].toString()),
            attended: payload['attended'] == true,
          );
          completed.add(clientId);
        } catch (error) {
          final mapped = AppException.from(error);
          if (_isPermanent(mapped)) {
            appLog('Dropping queued attendance (${mapped.statusCode})');
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
          final id = raw['client_operation_id']?.toString() ?? '';
          final status = raw['status']?.toString();
          final code = int.tryParse(raw['code']?.toString() ?? '');
          // Drop processed/duplicate ones, and failures that can never
          // succeed (4xx such as 403/404/422). Retry 5xx and unknown ones.
          final permanentFailure = status == 'failed' &&
              code != null &&
              code >= 400 &&
              code < 500 &&
              code != 408 &&
              code != 429;
          final errorCode = raw['error_code']?.toString().toLowerCase();
          if (errorCode == 'overdue') {
            final op = serverOperations.firstWhere(
              (o) => o['client_operation_id'] == id,
              orElse: () => const {},
            );
            final payload = op['payload'];
            final assessmentId =
                payload is Map ? int.tryParse(payload['assessment_id']?.toString() ?? '') : null;
            if (assessmentId != null) {
              await markAssignmentOverdue(assessmentId, fromQueue: true);
            }
          }
          if (status == 'processed' || status == 'duplicate') {
            completed.add(id);
            final inner = raw['result'];
            acknowledged[id] =
                inner is Map ? Map<String, dynamic>.from(inner) : <String, dynamic>{};
          } else if (permanentFailure) {
            completed.add(id);
            rejected.add(id);
          }
        }
      }
    }

    // Settle reading time first: if the app stops between these two
    // steps, a retried operation is answered "duplicate" and the seconds
    // are still counted once.
    await ReadingTimeService.instance.onOperationResults(
      acknowledged: acknowledged,
      rejected: rejected,
    );
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
      // Reading time counted offline goes out with the rest of the queue.
      await ReadingTimeService.instance.queuePending();
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

      // Profile, progress, mentorship attendance and assignment deadlines
      // (v2 fields). Each step tolerates an older backend.
      await ParticipantDataService.instance.refreshAll();

      // Send any remaining reading-time batches (over an hour unsent).
      await ReadingTimeService.instance.flush();

      dataVersion.value++;
    } finally {
      syncing.value = false;
    }
  }
}
