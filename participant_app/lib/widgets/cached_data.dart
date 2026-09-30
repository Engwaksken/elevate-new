import 'package:flutter/material.dart';

import '../services/sync_service.dart';
import 'feedback.dart';

/// Shared behaviour for offline-first screens that read from the local
/// cache: initial load, reload after any sync, and pull-to-refresh that
/// triggers a sync with friendly error feedback.
mixin CachedDataMixin<W extends StatefulWidget, D> on State<W> {
  D? data;
  bool loading = true;
  Object? loadError;

  /// Reads this screen's data from the local database.
  Future<D> readCache();

  @override
  void initState() {
    super.initState();
    SyncService.instance.dataVersion.addListener(reload);
    reload();
  }

  @override
  void dispose() {
    SyncService.instance.dataVersion.removeListener(reload);
    super.dispose();
  }

  Future<void> reload() async {
    try {
      final value = await readCache();
      if (!mounted) return;
      setState(() {
        data = value;
        loadError = null;
        loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        loadError = error;
        loading = false;
      });
    }
  }

  /// Pull-to-refresh handler.
  Future<void> refreshFromServer() async {
    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        showAppSnackBar(
          context,
          "You're offline. Showing the content saved on this device.",
        );
      }
      return;
    }

    try {
      await SyncService.instance.syncNow();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error, onRetry: refreshFromServer);
    }
    await reload();
  }
}
