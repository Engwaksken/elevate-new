import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';

import '../core/formatters.dart';
import '../core/logger.dart';
import '../core/reading_time_ledger.dart';
import 'local_database.dart';
import 'sync_service.dart';

/// Persists and sends lesson reading time as `time_spent_seconds_delta`.
///
/// Guarantees:
/// * Seconds are written to SQLite (`reading_time`) before anything is sent,
///   so closing the app never loses them.
/// * A batch is moved to "in flight" and queued as one offline operation
///   with a fixed `client_operation_id` in the same transaction. Retries
///   reuse that id and the server deduplicates it, so time is never
///   counted twice.
/// * In-flight seconds are cleared only when the server acknowledges the
///   operation (`processed` / `duplicate`), or when it permanently rejects it.
class ReadingTimeService {
  ReadingTimeService._();

  static final ReadingTimeService instance = ReadingTimeService._();

  /// Offline-action type carrying the delta (see the API contract).
  static const String operationType = 'lesson_progress';

  /// The API accepts deltas of 1..3600 seconds per call.
  static const int maxDeltaSeconds = 3600;

  final LocalDatabase _db = LocalDatabase.instance;
  final Uuid _uuid = const Uuid();

  /// Bumped whenever local reading totals change, for "Time spent" labels.
  final ValueNotifier<int> version = ValueNotifier<int>(0);

  /// Payload of one reading-time operation.
  @visibleForTesting
  static Map<String, dynamic> payloadFor(int lessonId, int seconds) => {
        'lesson_id': lessonId,
        'time_spent_seconds_delta': seconds.clamp(1, maxDeltaSeconds),
      };

  /// Adds counted seconds for a lesson to local storage.
  Future<void> record({
    required int lessonId,
    int? courseId,
    required int seconds,
  }) async {
    if (seconds <= 0) return;
    await _db.addReadingSeconds(
      lessonId: lessonId,
      courseId: courseId,
      seconds: seconds,
    );
    version.value++;
  }

  /// Moves pending seconds into the offline queue (one in-flight batch per
  /// lesson at a time). Returns how many operations were queued.
  Future<int> queuePending({int? lessonId}) async {
    var queued = 0;
    final rows = await _db.readingTimeRows();
    for (final row in rows) {
      final id = row['lesson_id'] as int?;
      if (id == null || (lessonId != null && id != lessonId)) continue;
      if (ReadingTimeEntry.fromRow(row).pending <= 0) continue;
      final ok = await _db.queueReadingTime(
        lessonId: id,
        maxSeconds: maxDeltaSeconds,
        clientOperationId: _uuid.v4(),
        type: operationType,
        payload: (seconds) => payloadFor(id, seconds),
      );
      if (ok) queued++;
    }
    return queued;
  }

  /// Queues pending time and, when online, sends the offline queue. Used
  /// when leaving a lesson, when marking it complete, and by sync.
  Future<void> flush({int? lessonId}) async {
    try {
      await queuePending(lessonId: lessonId);
      if (!await SyncService.instance.isOnline()) return;
      // A lesson can hold more than one batch (over an hour unsent): send
      // the next batch once the previous one is acknowledged.
      for (var round = 0; round < 5; round++) {
        await SyncService.instance.flushOfflineActions();
        if (await queuePending(lessonId: lessonId) == 0) break;
      }
    } catch (error) {
      // Everything is persisted; the next sync retries.
      appLog('Reading time flush deferred', error);
    } finally {
      version.value++;
    }
  }

  /// Called by [SyncService] with the operations the server acknowledged
  /// (and their results) and the ones it permanently rejected.
  Future<void> onOperationResults({
    required Map<String, Map<String, dynamic>> acknowledged,
    Iterable<String> rejected = const [],
  }) async {
    final totals = <String, int>{};
    acknowledged.forEach((id, result) {
      final total = asInt(result['time_spent_seconds']);
      if (total != null) totals[id] = total;
    });
    await _db.acknowledgeReadingTime(acknowledged.keys, serverTotals: totals);
    await _db.acknowledgeReadingTime(rejected, accepted: false);
    if (acknowledged.isNotEmpty || rejected.isNotEmpty) version.value++;
  }

  /// Remembers a lesson total the server reported in a payload.
  Future<void> rememberServerTotal(int lessonId, int seconds, {int? courseId}) async {
    if (seconds < 0) return;
    await _db.setServerReadingSeconds(lessonId, seconds, courseId: courseId);
  }

  /// Best known total for a lesson: the larger of [serverSeconds] and the
  /// last acknowledged total, plus unsent and in-flight seconds.
  Future<int> lessonTotal(int lessonId, {int serverSeconds = 0}) async {
    final row = await _db.readingTimeRow(lessonId);
    return combine(serverSeconds, row);
  }

  /// Best known totals for many lessons (e.g. a course).
  Future<int> lessonsTotal(Map<int, int> serverSecondsByLesson) async {
    final rows = {
      for (final row in await _db.readingTimeRows())
        if (row['lesson_id'] is int) row['lesson_id'] as int: row,
    };
    var total = 0;
    serverSecondsByLesson.forEach((id, server) {
      total += combine(server, rows[id]);
    });
    return total;
  }

  /// Unsent seconds (pending + in flight) for the given lessons, or all.
  Future<int> unsentSeconds({Iterable<int>? lessonIds}) async {
    final ids = lessonIds?.toSet();
    var total = 0;
    for (final row in await _db.readingTimeRows()) {
      if (ids != null && !ids.contains(row['lesson_id'])) continue;
      total += ReadingTimeEntry.fromRow(row).unsent;
    }
    return total;
  }

  static int combine(int serverSeconds, Map<String, dynamic>? row) =>
      ReadingTimeEntry.fromRow(row).total(serverSeconds: serverSeconds);
}
