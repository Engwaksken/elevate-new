import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

class LocalDatabase {
  Database? _database;

  Future<Database> get database async {
    if (_database != null) {
      return _database!;
    }

    final databasePath = await getDatabasesPath();

    final path = join(
      databasePath,
      'elevateher360_participant.db',
    );

    _database = await openDatabase(
      path,
      version: 2,
      onCreate: _onCreate,
      onUpgrade: _onUpgrade,
    );

    return _database!;
  }

  Future<void> _onCreate(
    Database db,
    int version,
  ) async {
    await db.execute(
      '''
      CREATE TABLE sync_meta (
        key TEXT PRIMARY KEY,
        value TEXT
      )
      ''',
    );

    await db.execute(
      '''
      CREATE TABLE pending_actions (
        id TEXT PRIMARY KEY,
        action_type TEXT NOT NULL,
        payload_json TEXT NOT NULL,
        occurred_at TEXT NOT NULL,
        retry_count INTEGER NOT NULL DEFAULT 0,
        last_error TEXT
      )
      ''',
    );

    final syncTables = [
      'courses',
      'assignments',
      'events',
      'announcements',
    ];

    for (final table in syncTables) {
      await db.execute(
        '''
        CREATE TABLE $table (
          id TEXT PRIMARY KEY,
          data_json TEXT NOT NULL,
          updated_at TEXT,
          deleted_at TEXT
        )
        ''',
      );
    }

    await db.execute(
      '''
      CREATE INDEX pending_actions_occurred_at_idx
      ON pending_actions(occurred_at)
      ''',
    );

    await db.execute(
      '''
      CREATE INDEX pending_actions_retry_count_idx
      ON pending_actions(retry_count)
      ''',
    );
  }

  Future<void> _onUpgrade(
    Database db,
    int oldVersion,
    int newVersion,
  ) async {
    if (oldVersion < 2) {
      final columns = await db.rawQuery(
        'PRAGMA table_info(pending_actions)',
      );

      final columnNames = columns
          .map(
            (column) => column['name']?.toString(),
          )
          .whereType<String>()
          .toSet();

      if (!columnNames.contains('retry_count')) {
        await db.execute(
          '''
          ALTER TABLE pending_actions
          ADD COLUMN retry_count INTEGER NOT NULL DEFAULT 0
          ''',
        );
      }

      if (!columnNames.contains('last_error')) {
        await db.execute(
          '''
          ALTER TABLE pending_actions
          ADD COLUMN last_error TEXT
          ''',
        );
      }
    }
  }

  Future<void> clearSyncData() async {
    final db = await database;

    await db.transaction((transaction) async {
      for (final table in [
        'courses',
        'assignments',
        'events',
        'announcements',
        'pending_actions',
        'sync_meta',
      ]) {
        await transaction.delete(table);
      }
    });
  }

  Future<void> close() async {
    final db = _database;

    if (db == null) {
      return;
    }

    await db.close();
    _database = null;
  }
}
