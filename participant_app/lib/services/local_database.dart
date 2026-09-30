import 'dart:convert';

import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

import '../core/reading_time_ledger.dart';

class LocalDatabase {
  static final LocalDatabase instance = LocalDatabase._();
  LocalDatabase._();

  Database? _db;

  Future<Database> get database async {
    if (_db != null) return _db!;

    final dbPath =
        join(await getDatabasesPath(), 'elevateher360_participant.db');
    _db = await openDatabase(
      dbPath,
      version: 3,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE cached_items(
            collection_name TEXT NOT NULL,
            item_id TEXT NOT NULL,
            payload TEXT NOT NULL,
            updated_at TEXT,
            PRIMARY KEY(collection_name, item_id)
          )
        ''');

        await db.execute('''
          CREATE TABLE offline_operations(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_operation_id TEXT UNIQUE NOT NULL,
            type TEXT NOT NULL,
            payload TEXT,
            created_at TEXT NOT NULL
          )
        ''');

        await db.execute('''
          CREATE TABLE meta(
            meta_key TEXT PRIMARY KEY,
            meta_value TEXT
          )
        ''');

        await _createDownloadsTable(db);
        await _createReadingTimeTable(db);
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        // Additive migrations only: existing cache, queue and downloads
        // are kept.
        if (oldVersion < 2) {
          await _createDownloadsTable(db);
        }
        if (oldVersion < 3) {
          await _createReadingTimeTable(db);
        }
      },
    );

    return _db!;
  }

  static Future<void> _createDownloadsTable(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS offline_downloads(
        download_key TEXT PRIMARY KEY,
        local_path TEXT NOT NULL,
        source_url TEXT NOT NULL,
        file_name TEXT NOT NULL,
        downloaded_at TEXT NOT NULL
      )
    ''');
  }

  /// Unsent reading time per lesson (v3). `pending_seconds` hasn't been
  /// queued yet; `in_flight_seconds` is queued as offline operation
  /// `in_flight_op_id` and is cleared only when the server acknowledges it.
  /// `server_seconds` is the last total the server reported.
  static Future<void> _createReadingTimeTable(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS reading_time(
        lesson_id INTEGER PRIMARY KEY,
        course_id INTEGER,
        pending_seconds INTEGER NOT NULL DEFAULT 0,
        in_flight_seconds INTEGER NOT NULL DEFAULT 0,
        in_flight_op_id TEXT,
        server_seconds INTEGER,
        updated_at TEXT
      )
    ''');
  }

  Future<void> replaceCollection(String name, List<dynamic> items) async {
    final db = await database;
    final batch = db.batch();

    batch.delete(
      'cached_items',
      where: 'collection_name = ?',
      whereArgs: [name],
    );

    for (final raw in items) {
      if (raw is! Map) continue;
      final item = Map<String, dynamic>.from(raw);
      // A full sync includes soft-deleted rows (withTrashed); skip them.
      if (item['deleted_at'] != null) continue;
      final id = (item['id'] ?? item['source_id'] ?? item.hashCode).toString();

      batch.insert(
        'cached_items',
        {
          'collection_name': name,
          'item_id': id,
          'payload': jsonEncode(item),
          'updated_at': item['updated_at']?.toString(),
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }

    await batch.commit(noResult: true);
  }

  Future<void> mergeCollection(String name, List<dynamic> items) async {
    final db = await database;
    final batch = db.batch();

    for (final raw in items) {
      if (raw is! Map) continue;
      final item = Map<String, dynamic>.from(raw);
      final id = (item['id'] ?? item['source_id'] ?? item.hashCode).toString();

      if (item['deleted_at'] != null) {
        batch.delete(
          'cached_items',
          where: 'collection_name = ? AND item_id = ?',
          whereArgs: [name, id],
        );
        continue;
      }

      batch.insert(
        'cached_items',
        {
          'collection_name': name,
          'item_id': id,
          'payload': jsonEncode(item),
          'updated_at': item['updated_at']?.toString(),
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }

    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> readCollection(String name) async {
    final db = await database;
    final rows = await db.query(
      'cached_items',
      where: 'collection_name = ?',
      whereArgs: [name],
    );

    return rows
        .map(
          (row) => Map<String, dynamic>.from(
            jsonDecode(row['payload'] as String) as Map,
          ),
        )
        .toList();
  }

  Future<Map<String, dynamic>?> readItem(
    String collection,
    String itemId,
  ) async {
    final db = await database;
    final rows = await db.query(
      'cached_items',
      where: 'collection_name = ? AND item_id = ?',
      whereArgs: [collection, itemId],
      limit: 1,
    );

    if (rows.isEmpty) return null;

    return Map<String, dynamic>.from(
      jsonDecode(rows.first['payload'] as String) as Map,
    );
  }

  Future<void> cacheItem({
    required String collection,
    required String itemId,
    required Map<String, dynamic> payload,
  }) async {
    final db = await database;

    await db.insert(
      'cached_items',
      {
        'collection_name': collection,
        'item_id': itemId,
        'payload': jsonEncode(payload),
        'updated_at': payload['updated_at']?.toString(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> enqueueOperation({
    required String clientOperationId,
    required String type,
    required Map<String, dynamic> payload,
  }) async {
    final db = await database;

    await db.insert(
      'offline_operations',
      {
        'client_operation_id': clientOperationId,
        'type': type,
        'payload': jsonEncode(payload),
        'created_at': DateTime.now().toUtc().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );
  }

  Future<List<Map<String, dynamic>>> pendingOperations() async {
    final db = await database;
    final rows = await db.query(
      'offline_operations',
      orderBy: 'id ASC',
    );

    return rows.map((row) {
      return {
        'id': row['id'],
        'client_operation_id': row['client_operation_id'],
        'type': row['type'],
        'created_at': row['created_at'],
        'payload': row['payload'] == null
            ? <String, dynamic>{}
            : Map<String, dynamic>.from(
                jsonDecode(row['payload'] as String) as Map,
              ),
      };
    }).toList();
  }

  Future<int> pendingOperationCount() async {
    final db = await database;
    final result = Sqflite.firstIntValue(
      await db.rawQuery('SELECT COUNT(*) FROM offline_operations'),
    );
    return result ?? 0;
  }

  Future<void> removeOperations(Iterable<String> clientIds) async {
    final ids = clientIds.toList();
    if (ids.isEmpty) return;

    final db = await database;
    final marks = List.filled(ids.length, '?').join(',');

    await db.delete(
      'offline_operations',
      where: 'client_operation_id IN ($marks)',
      whereArgs: ids,
    );
  }

  Future<void> setMeta(String key, String? value) async {
    final db = await database;

    await db.insert(
      'meta',
      {'meta_key': key, 'meta_value': value},
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<String?> getMeta(String key) async {
    final db = await database;
    final rows = await db.query(
      'meta',
      columns: ['meta_value'],
      where: 'meta_key = ?',
      whereArgs: [key],
      limit: 1,
    );

    return rows.isEmpty ? null : rows.first['meta_value'] as String?;
  }

  Future<void> saveDownload({
    required String downloadKey,
    required String localPath,
    required String sourceUrl,
    required String fileName,
  }) async {
    final db = await database;

    await db.insert(
      'offline_downloads',
      {
        'download_key': downloadKey,
        'local_path': localPath,
        'source_url': sourceUrl,
        'file_name': fileName,
        'downloaded_at': DateTime.now().toUtc().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<String?> downloadPath(String key) async {
    final db = await database;
    final rows = await db.query(
      'offline_downloads',
      columns: ['local_path'],
      where: 'download_key = ?',
      whereArgs: [key],
      limit: 1,
    );

    return rows.isEmpty ? null : rows.first['local_path'] as String?;
  }

  Future<List<Map<String, dynamic>>> downloads() async {
    final db = await database;
    return db.query('offline_downloads', orderBy: 'downloaded_at DESC');
  }

  Future<void> removeDownload(String key) async {
    final db = await database;
    await db.delete(
      'offline_downloads',
      where: 'download_key = ?',
      whereArgs: [key],
    );
  }

  // ---------------------------------------------------------
  // Lesson completion and course progress
  // ---------------------------------------------------------

  static const String _localCompletion = 'lesson_completion_local';

  /// Lessons completed according to the last sync (`lesson_progress`
  /// rows with `completed_at`), overridden by changes made on this device
  /// that may still be waiting in the offline queue.
  ///
  /// [alsoCompleted] adds lessons the server reported complete in another
  /// payload (e.g. `lesson.progress.completed` in the course tree).
  Future<Set<int>> completedLessonIds({
    Iterable<int> alsoCompleted = const [],
  }) async {
    final completed = <int>{...alsoCompleted};

    for (final row in await readCollection('lesson_progress')) {
      final id = int.tryParse(row['lesson_id']?.toString() ?? '');
      if (id != null && row['completed_at'] != null) completed.add(id);
    }

    for (final row in await readCollection(_localCompletion)) {
      final id = int.tryParse(row['lesson_id']?.toString() ?? '');
      if (id == null) continue;
      if (row['completed'] == true) {
        completed.add(id);
      } else {
        completed.remove(id);
      }
    }

    return completed;
  }

  Future<void> setLocalLessonCompletion(int lessonId, bool completed) {
    return cacheItem(
      collection: _localCompletion,
      itemId: lessonId.toString(),
      payload: {
        'lesson_id': lessonId,
        'completed': completed,
        'updated_at': DateTime.now().toUtc().toIso8601String(),
      },
    );
  }

  /// Progress (0-100) per course id, from synced enrolments.
  Future<Map<int, double>> courseProgress() async {
    final result = <int, double>{};
    for (final row in await readCollection('enrolments')) {
      final id = int.tryParse(row['course_id']?.toString() ?? '');
      final value = double.tryParse(row['progress_percent']?.toString() ?? '');
      if (id != null && value != null) {
        result[id] = value.clamp(0, 100).toDouble();
      }
    }
    return result;
  }

  Future<void> setCourseProgress(
    int courseId,
    double percent, {
    String? status,
  }) async {
    for (final row in await readCollection('enrolments')) {
      if (row['course_id']?.toString() != courseId.toString()) continue;
      row['progress_percent'] = percent;
      if (status != null && status.isNotEmpty) row['status'] = status;
      await cacheItem(
        collection: 'enrolments',
        itemId: (row['id'] ?? row['course_id']).toString(),
        payload: row,
      );
    }
  }

  Future<void> clearAll() async {
    final db = await database;
    final batch = db.batch();

    batch.delete('cached_items');
    batch.delete('offline_operations');
    batch.delete('meta');
    batch.delete('offline_downloads');
    batch.delete('reading_time');

    await batch.commit(noResult: true);
  }

  // ---------------------------------------------------------
  // Reading time (see ReadingTimeService and ReadingTimeEntry)
  // ---------------------------------------------------------

  static Future<ReadingTimeEntry> _entry(Transaction txn, int lessonId) async {
    final rows = await txn.query(
      'reading_time',
      where: 'lesson_id = ?',
      whereArgs: [lessonId],
      limit: 1,
    );
    return ReadingTimeEntry.fromRow(rows.isEmpty ? null : rows.first);
  }

  static Future<void> _saveEntry(
    Transaction txn,
    int lessonId,
    ReadingTimeEntry entry, {
    int? courseId,
  }) async {
    final row = {
      ...entry.toRow(),
      'updated_at': DateTime.now().toUtc().toIso8601String(),
    };
    final updated = await txn.update(
      'reading_time',
      {...row, if (courseId != null) 'course_id': courseId},
      where: 'lesson_id = ?',
      whereArgs: [lessonId],
    );
    if (updated == 0) {
      await txn.insert('reading_time', {
        ...row,
        'lesson_id': lessonId,
        'course_id': courseId,
      });
    }
  }

  Future<void> addReadingSeconds({
    required int lessonId,
    int? courseId,
    required int seconds,
  }) async {
    if (seconds <= 0) return;
    final db = await database;
    await db.transaction((txn) async {
      final entry = await _entry(txn, lessonId);
      await _saveEntry(txn, lessonId, entry.add(seconds), courseId: courseId);
    });
  }

  Future<List<Map<String, dynamic>>> readingTimeRows() async {
    final db = await database;
    final rows = await db.query('reading_time');
    return rows.map(Map<String, dynamic>.from).toList();
  }

  Future<Map<String, dynamic>?> readingTimeRow(int lessonId) async {
    final db = await database;
    final rows = await db.query(
      'reading_time',
      where: 'lesson_id = ?',
      whereArgs: [lessonId],
      limit: 1,
    );
    return rows.isEmpty ? null : Map<String, dynamic>.from(rows.first);
  }

  /// Atomically moves up to [maxSeconds] of pending time into flight and
  /// enqueues it as one offline operation. Returns false when nothing was
  /// queued: no pending time, or a previous batch is still waiting for the
  /// server (it is retried with its original operation id, never re-queued).
  Future<bool> queueReadingTime({
    required int lessonId,
    required int maxSeconds,
    required String clientOperationId,
    required String type,
    required Map<String, dynamic> Function(int seconds) payload,
  }) async {
    final db = await database;
    return db.transaction((txn) async {
      final entry = await _entry(txn, lessonId);
      final next = entry.beginBatch(clientOperationId, maxSeconds: maxSeconds);
      if (next == null) return false;

      await txn.insert('offline_operations', {
        'client_operation_id': clientOperationId,
        'type': type,
        'payload': jsonEncode(payload(next.inFlight)),
        'created_at': DateTime.now().toUtc().toIso8601String(),
      });
      await _saveEntry(txn, lessonId, next);
      return true;
    });
  }

  /// Settles in-flight batches for the given operation ids: accepted ones
  /// become part of the acknowledged total ([serverTotals] maps an id to the
  /// lesson total the server reported); rejected ones are dropped.
  Future<void> acknowledgeReadingTime(
    Iterable<String> clientOperationIds, {
    Map<String, int> serverTotals = const {},
    bool accepted = true,
  }) async {
    final ids = clientOperationIds.toList();
    if (ids.isEmpty) return;
    final db = await database;
    await db.transaction((txn) async {
      for (final id in ids) {
        final rows = await txn.query(
          'reading_time',
          where: 'in_flight_op_id = ?',
          whereArgs: [id],
          limit: 1,
        );
        if (rows.isEmpty) continue;
        final lessonId = rows.first['lesson_id'] as int;
        final entry = ReadingTimeEntry.fromRow(rows.first);
        await _saveEntry(
          txn,
          lessonId,
          accepted
              ? entry.acknowledge(id, serverTotal: serverTotals[id])
              : entry.reject(id),
        );
      }
    });
  }

  /// Records a server-reported lesson total (e.g. from a lesson payload).
  Future<void> setServerReadingSeconds(
    int lessonId,
    int seconds, {
    int? courseId,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      final entry = await _entry(txn, lessonId);
      await _saveEntry(
        txn,
        lessonId,
        entry.withServerTotal(seconds),
        courseId: courseId,
      );
    });
  }
}
