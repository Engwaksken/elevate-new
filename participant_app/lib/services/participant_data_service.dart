import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/painting.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

import '../core/formatters.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import 'api_service.dart';
import 'local_database.dart';

/// Offline-first access to the v2 participant endpoints: profile, profile
/// photo, progress, and full refreshes of mentorship sessions and
/// assignments (which carry fields /sync may not include).
class ParticipantDataService {
  ParticipantDataService._();

  static final ParticipantDataService instance = ParticipantDataService._();

  static const _profileCollection = 'profile';
  static const _progressCollection = 'progress';
  static const _supportCollection = 'support';
  static const _key = 'me';
  static const _photoSourceMeta = 'profile_photo_source';

  final LocalDatabase _db = LocalDatabase.instance;
  final ApiService _api = ApiService.instance;

  /// Bumped when the cached profile or photo changes (drawer, avatars).
  final ValueNotifier<int> profileVersion = ValueNotifier<int>(0);

  // ---------------------------------------------------------
  // Profile
  // ---------------------------------------------------------

  /// Cached `{profile, editable_fields}` or null if never loaded.
  Future<Map<String, dynamic>?> cachedProfile() =>
      _db.readItem(_profileCollection, _key);

  /// Fetches GET /profile, caches it and refreshes the photo file.
  Future<Map<String, dynamic>> refreshProfile() async {
    final data = await _api.profile();
    await _storeProfile(data);
    return data;
  }

  Future<void> _storeProfile(Map<String, dynamic> data) async {
    final profile = data['profile'];
    if (profile is! Map) return;
    final existing = await cachedProfile();
    final merged = {
      'profile': Map<String, dynamic>.from(profile),
      'editable_fields': data['editable_fields'] ?? existing?['editable_fields'] ?? const [],
      'cached_at': DateTime.now().toUtc().toIso8601String(),
    };
    await _db.cacheItem(collection: _profileCollection, itemId: _key, payload: merged);

    // Keep the signed-in user (drawer header, greeting) in step.
    final user = await _api.currentUser() ?? <String, dynamic>{};
    for (final key in ['name', 'email', 'phone']) {
      if (profile[key] != null) user[key] = profile[key];
    }
    await _api.saveCurrentUser(user);

    await _syncPhoto(profile['photo_url']?.toString());
    profileVersion.value++;
  }

  /// PUT /profile; returns the updated profile. Throws [AppException]
  /// (422 with field errors, or offline).
  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> fields) async {
    final data = await _api.updateProfile(fields);
    if (data['profile'] is Map) {
      await _storeProfile(data);
    } else {
      await refreshProfile();
    }
    return (await cachedProfile()) ?? data;
  }

  /// POST /profile/photo; the response carries the updated profile.
  Future<void> uploadPhoto(String path) async {
    final data = await _api.uploadProfilePhoto(path);
    final profile = data['profile'];
    final url = profile is Map ? profile['photo_url']?.toString() : null;
    // Use the chosen file straight away (no re-download of the same image).
    await _copyLocalPhoto(path, source: url ?? 'local');
    if (profile is Map) {
      await _storeProfile(data);
    } else {
      try {
        await refreshProfile();
      } catch (error) {
        appLog('Profile refresh after photo upload failed', error);
      }
    }
    profileVersion.value++;
  }

  /// DELETE /profile/photo (idempotent).
  Future<void> removePhoto() async {
    final data = await _api.deleteProfilePhoto();
    if (data['profile'] is Map) {
      await _storeProfile(data);
    } else {
      await _syncPhoto(null);
      final cached = await cachedProfile();
      final profile = cached?['profile'];
      if (cached != null && profile is Map) {
        profile['photo_url'] = null;
        await _db.cacheItem(collection: _profileCollection, itemId: _key, payload: cached);
      }
    }
    profileVersion.value++;
  }

  /// PUT /profile/password (204). Other sessions are revoked by the server;
  /// this device's token stays valid.
  Future<void> changePassword({
    required String currentPassword,
    required String password,
    required String confirmation,
  }) =>
      _api.changePassword(
        currentPassword: currentPassword,
        password: password,
        confirmation: confirmation,
      );

  Future<File> _photoFile() async {
    final dir = await getApplicationSupportDirectory();
    return File(p.join(dir.path, 'profile_photo'));
  }

  /// The cached profile photo, if one is saved on this device.
  Future<File?> photoFile() async {
    try {
      final file = await _photoFile();
      return await file.exists() ? file : null;
    } catch (_) {
      return null;
    }
  }

  Future<void> _copyLocalPhoto(String path, {required String source}) async {
    try {
      final target = await _photoFile();
      await File(path).copy(target.path);
      await _db.setMeta(_photoSourceMeta, source);
      _evict(target);
    } catch (error) {
      appLog('Could not cache the chosen photo', error);
    }
  }

  /// Downloads the photo via the authenticated GET /profile/photo when the
  /// server's `photo_url` changed; deletes the local copy when it's gone.
  Future<void> _syncPhoto(String? photoUrl) async {
    try {
      final file = await _photoFile();
      final url = photoUrl?.trim() ?? '';
      if (url.isEmpty) {
        if (await file.exists()) await file.delete();
        await _db.setMeta(_photoSourceMeta, null);
        _evict(file);
        return;
      }
      if (await _db.getMeta(_photoSourceMeta) == url && await file.exists()) return;

      final temp = File('${file.path}.download');
      await _api.downloadProfilePhoto(temp.path);
      await temp.rename(file.path);
      await _db.setMeta(_photoSourceMeta, url);
      _evict(file);
    } catch (error) {
      appLog('Profile photo download failed', error);
    }
  }

  // ---------------------------------------------------------
  // Progress
  // ---------------------------------------------------------

  Future<Map<String, dynamic>?> cachedProgress() =>
      _db.readItem(_progressCollection, _key);

  Future<Map<String, dynamic>> refreshProgress() async {
    final data = await _api.progress();
    await _db.cacheItem(
      collection: _progressCollection,
      itemId: _key,
      payload: {...data, 'cached_at': DateTime.now().toUtc().toIso8601String()},
    );
    return data;
  }

  // ---------------------------------------------------------
  // Help & support
  // ---------------------------------------------------------

  Future<Map<String, dynamic>?> cachedSupport() async {
    final cached = await _db.readItem(_supportCollection, _key);
    final support = cached?['support'];
    return support is Map ? Map<String, dynamic>.from(support) : null;
  }

  Future<Map<String, dynamic>> refreshSupport() async {
    final data = await _api.support();
    await _db.cacheItem(collection: _supportCollection, itemId: _key, payload: data);
    final support = data['support'];
    return support is Map ? Map<String, dynamic>.from(support) : const {};
  }

  /// Server-reported time per course id, from the cached progress.
  Future<Map<int, int>> courseTimeSeconds() async {
    final progress = await cachedProgress();
    final courses = progress?['courses'];
    return {
      if (courses is List)
        for (final c in courses.whereType<Map>())
          if (asInt(c['id']) != null) asInt(c['id'])!: asInt(c['time_spent_seconds']) ?? 0,
    };
  }

  // ---------------------------------------------------------
  // Mentorship and assignments
  // ---------------------------------------------------------

  /// Replaces cached sessions with GET /mentorship `sessions`.
  Future<void> refreshMentorship() async {
    final data = await _api.mentorship();
    final sessions = data['sessions'];
    if (sessions is List) await _db.replaceCollection('mentorship', sessions);
  }

  /// Replaces cached assignments with every page of GET /assignments.
  Future<void> refreshAssignments({int maxPages = 10}) async {
    final all = <dynamic>[];
    var page = 1;
    while (page <= maxPages) {
      final data = await _api.assignments(page: page);
      final items = data['data'];
      if (items is! List) break;
      all.addAll(items);
      final last = asInt(data['last_page']) ?? page;
      if (page >= last) {
        await _db.replaceCollection('assignments', all);
        return;
      }
      page++;
    }
    // Too many pages to be sure we saw everything: merge instead.
    await _db.mergeCollection('assignments', all);
  }

  /// Runs every v2 refresh, tolerating endpoints the server doesn't have
  /// yet (404) so an older backend never breaks sync.
  Future<void> refreshAll() async {
    for (final task in <Future<void> Function()>[
      refreshProfile,
      refreshProgress,
      refreshMentorship,
      refreshAssignments,
    ]) {
      try {
        await task();
      } catch (error) {
        final mapped = AppException.from(error);
        if (mapped.kind == AppErrorKind.unauthorised) rethrow;
        appLog('Participant refresh step failed (${mapped.statusCode})', error);
      }
    }
  }
}

/// Drops the decoded image for [file] so an updated photo is shown.
void _evict(File file) {
  FileImage(file).evict();
}
