import 'dart:convert';

import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

import '../connectivity/connectivity_service.dart';
import '../network/api_client.dart';
import '../storage/local_database.dart';

class SyncService {
  SyncService({
    required this.api,
    required this.database,
    required this.connectivity,
  });

  final ApiClient api;
  final LocalDatabase database;
  final ConnectivityService connectivity;

  static const Uuid _uuid = Uuid();

  static const List<String> _syncTables = [
    'courses',
    'assignments',
    'events',
    'announcements',
  ];

  Future<void> queueAction(
    String type,
    Map<String, dynamic> payload,
  ) async {
    final db = await database.database;

    await db.insert(
      'pending_actions',
      {
        'id': _uuid.v4(),
        'action_type': type,
        'payload_json': jsonEncode(payload),
        'occurred_at': DateTime.now().toUtc().toIso8601String(),
        'retry_count': 0,
        'last_error': null,
      },
      conflictAlgorithm: ConflictAlgorithm.abort,
    );
  }

  Future<void> syncNow() async {
    final isOnline = await connectivity.isOnline;

    if (!isOnline) {
      return;
    }

    final db = await database.database;

    final lastSyncedAt = await _getLastSyncedAt(db);

    await _pushPendingActions(db);

    final response = await api.dio.get(
      '/api/v1/participant/sync',
      queryParameters: lastSyncedAt == null
          ? null
          : {
              'since': lastSyncedAt,
            },
    );

    final responseBody = response.data;

    if (responseBody is! Map) {
      throw const FormatException(
        'Invalid synchronisation response.',
      );
    }

    final responseMap = Map<String, dynamic>.from(
      responseBody,
    );

    final rawData = responseMap['data'];

    if (rawData is! Map) {
      throw const FormatException(
        'Synchronisation response does not contain valid data.',
      );
    }

    final data = Map<String, dynamic>.from(rawData);

    final serverTime = data['server_time']?.toString() ??
        DateTime.now().toUtc().toIso8601String();

    await db.transaction((transaction) async {
      for (final table in _syncTables) {
        final rows = data[table];

        if (rows is! List) {
          continue;
        }

        for (final raw in rows) {
          if (raw is! Map) {
            continue;
          }

          final row = Map<String, dynamic>.from(raw);

          final id = row['id'];

          if (id == null) {
            continue;
          }

          await transaction.insert(
            table,
            {
              'id': id.toString(),
              'data_json': jsonEncode(row),
              'updated_at': row['updated_at']?.toString(),
              'deleted_at': row['deleted_at']?.toString(),
            },
            conflictAlgorithm: ConflictAlgorithm.replace,
          );
        }
      }

      await transaction.insert(
        'sync_meta',
        {
          'key': 'last_synced_at',
          'value': serverTime,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  Future<String?> _getLastSyncedAt(Database db) async {
    final rows = await db.query(
      'sync_meta',
      where: 'key = ?',
      whereArgs: ['last_synced_at'],
      limit: 1,
    );

    if (rows.isEmpty) {
      return null;
    }

    return rows.first['value']?.toString();
  }

  Future<void> _pushPendingActions(Database db) async {
    final rows = await db.query(
      'pending_actions',
      orderBy: 'occurred_at ASC',
      limit: 100,
    );

    if (rows.isEmpty) {
      return;
    }

    final actions = rows.map((row) {
      return {
        'client_action_id': row['id'],
        'type': row['action_type'],
        'payload': _decodePayload(
          row['payload_json']?.toString(),
        ),
        'occurred_at': row['occurred_at'],
      };
    }).toList();

    try {
      final response = await api.dio.post(
        '/api/v1/participant/offline-actions',
        data: {
          'actions': actions,
        },
      );

      final acceptedIds = _extractAcceptedActionIds(
        response.data,
      );

      for (final id in acceptedIds) {
        await db.delete(
          'pending_actions',
          where: 'id = ?',
          whereArgs: [id],
        );
      }
    } catch (error) {
      await _recordPendingActionFailure(
        db,
        rows,
        error.toString(),
      );

      rethrow;
    }
  }

  Map<String, dynamic> _decodePayload(
    String? raw,
  ) {
    if (raw == null || raw.trim().isEmpty) {
      return <String, dynamic>{};
    }

    try {
      final decoded = jsonDecode(raw);

      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
    } catch (_) {
      // Keep invalid local payload isolated rather than
      // crashing the entire local database.
    }

    return <String, dynamic>{};
  }

  Set<String> _extractAcceptedActionIds(
    dynamic responseBody,
  ) {
    if (responseBody is! Map) {
      return <String>{};
    }

    final response = Map<String, dynamic>.from(
      responseBody,
    );

    final rawResults =
        response['data'] ?? response['results'] ?? response['processed'];

    if (rawResults is! List) {
      return <String>{};
    }

    final acceptedIds = <String>{};

    for (final raw in rawResults) {
      if (raw is! Map) {
        continue;
      }

      final item = Map<String, dynamic>.from(raw);

      final id = item['client_action_id']?.toString();

      if (id == null || id.isEmpty) {
        continue;
      }

      final status = item['status']?.toString().toLowerCase();

      final success = item['success'] == true ||
          status == null ||
          status == 'success' ||
          status == 'processed' ||
          status == 'received' ||
          status == 'completed';

      if (success) {
        acceptedIds.add(id);
      }
    }

    return acceptedIds;
  }

  Future<void> _recordPendingActionFailure(
    Database db,
    List<Map<String, Object?>> rows,
    String error,
  ) async {
    for (final row in rows) {
      final id = row['id']?.toString();

      if (id == null || id.isEmpty) {
        continue;
      }

      final retryCount = (row['retry_count'] as int?) ?? 0;

      await db.update(
        'pending_actions',
        {
          'retry_count': retryCount + 1,
          'last_error': error.length > 500 ? error.substring(0, 500) : error,
        },
        where: 'id = ?',
        whereArgs: [id],
      );
    }
  }

  Future<int> pendingActionsCount() async {
    final db = await database.database;

    final result = Sqflite.firstIntValue(
      await db.rawQuery(
        'SELECT COUNT(*) FROM pending_actions',
      ),
    );

    return result ?? 0;
  }

  Future<DateTime?> lastSyncedAt() async {
    final db = await database.database;

    final value = await _getLastSyncedAt(db);

    if (value == null) {
      return null;
    }

    return DateTime.tryParse(value);
  }
}
