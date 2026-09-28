import 'dart:convert';

import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

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
      version: 2,
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
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        if (oldVersion < 2) {
          await _createDownloadsTable(db);
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

  Future<void> clearAll() async {
    final db = await database;
    final batch = db.batch();

    batch.delete('cached_items');
    batch.delete('offline_operations');
    batch.delete('meta');
    batch.delete('offline_downloads');

    await batch.commit(noResult: true);
  }
}
