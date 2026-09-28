import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:uuid/uuid.dart';

import 'api_service.dart';
import 'local_database.dart';
import 'notification_service.dart';

class SyncService {
  SyncService._();

  static final SyncService instance = SyncService._();

  final LocalDatabase _db = LocalDatabase.instance;
  final ApiService _api = ApiService.instance;
  final Uuid _uuid = const Uuid();

  StreamSubscription<List<ConnectivityResult>>? _subscription;
  final StreamController<bool> _onlineController =
      StreamController<bool>.broadcast();

  Stream<bool> get onlineChanges => _onlineController.stream;

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
        } catch (_) {}
      }
    });
  }

  Future<void> dispose() async {
    await _subscription?.cancel();
    await _onlineController.close();
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
  }

  Future<void> flushOfflineActions() async {
    if (!await isOnline()) return;

    final pending = await _db.pendingOperations();
    if (pending.isEmpty) return;

    final payload = pending
        .map(
          (item) => {
            'client_operation_id': item['client_operation_id'],
            'type': item['type'],
            'payload': item['payload'],
          },
        )
        .toList();

    final result = await _api.postOfflineActions(payload);
    final rows = result['results'];
    if (rows is! List) return;

    final processed = <String>[];
    for (final raw in rows) {
      if (raw is! Map) continue;
      final row = Map<String, dynamic>.from(raw);
      if (row['status'] == 'processed' || row['status'] == 'duplicate') {
        processed.add(row['client_operation_id'].toString());
      }
    }
    await _db.removeOperations(processed);
  }

  Future<void> syncNow() async {
    if (!await isOnline()) return;

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

    final lastSyncedAt = data['last_synced_at']?.toString();
    await _db.setMeta('last_synced_at', lastSyncedAt);

    final reminders = data['local_reminders'];
    if (reminders is List) {
      await NotificationService.instance.scheduleFromSync(reminders);
    }
  }
}
