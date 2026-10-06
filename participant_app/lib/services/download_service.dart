import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/learning_file.dart';
import '../core/logger.dart';
import '../core/network/app_exception.dart';
import 'api_service.dart';
import 'local_database.dart';

/// Saves lesson files and assessment attachments for offline use.
///
/// Only files the download policy allows (spreadsheets, CSV and ZIP, plus
/// her own submissions and certificates) are ever saved here. View-only
/// files are shown by the in-app viewer from memory instead.
class DownloadService {
  DownloadService._();

  static final DownloadService instance = DownloadService._();

  final LocalDatabase _db = LocalDatabase.instance;

  static String lessonKey(int lessonId) => 'lesson_$lessonId';
  static String assessmentKey(String id) => 'assessment_$id';
  static String submissionFileKey(dynamic fileId) => 'submission_file_$fileId';

  static const String viewOnlyMessage =
      "This file is view-only, so it can't be saved on your device. Tap View to read it in the app.";

  /// Course material keys (lesson files and assignment attachments), which
  /// fall under the view-only policy. Certificates and her own submissions
  /// use other prefixes and are never purged.
  static bool isCourseMaterialKey(String key) =>
      key.startsWith('lesson_') || key.startsWith('assessment_');

  Future<bool> wifiOnly() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool('wifi_only_downloads') ?? false;
  }

  Future<void> setWifiOnly(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('wifi_only_downloads', value);
  }

  Future<void> _checkConnectionPolicy() async {
    final results = await Connectivity().checkConnectivity();

    if (results.every((item) => item == ConnectivityResult.none)) {
      throw AppException.offline;
    }

    if (await wifiOnly() &&
        !results.contains(ConnectivityResult.wifi) &&
        !results.contains(ConnectivityResult.ethernet)) {
      throw const AppException(
        AppErrorKind.wifiOnly,
        'Wi-Fi only downloads are on. Connect to Wi-Fi or change this in Settings.',
      );
    }
  }

  Future<Directory> _downloadsDir() async {
    final directory = await getApplicationDocumentsDirectory();
    final dir = Directory(p.join(directory.path, 'offline_downloads'));
    if (!await dir.exists()) await dir.create(recursive: true);
    return dir;
  }

  static String lessonFileKey(int lessonId, dynamic fileId) =>
      'lesson_${lessonId}_file_$fileId';

  /// Downloads an authenticated API file ([apiPath] is the `download_path`
  /// from the API, e.g. `/lessons/1/download`) and records it for offline use.
  ///
  /// [fileName] (e.g. the lesson's `file_name`) gives the saved file its
  /// extension; otherwise it comes from the response headers.
  Future<String> download({
    required String key,
    required String apiPath,
    required String title,
    String? fileName,
    bool downloadable = true,
    void Function(double? progress)? onProgress,
    CancelToken? cancelToken,
  }) async {
    if (!downloadable) {
      throw const AppException(AppErrorKind.forbidden, viewOnlyMessage, statusCode: 403);
    }
    await _checkConnectionPolicy();

    final dir = await _downloadsDir();
    final tempPath = p.join(dir.path, '.$key.part');

    try {
      onProgress?.call(null);

      final response = await ApiService.instance.downloadApiFile(
        path: apiPath,
        savePath: tempPath,
        cancelToken: cancelToken,
        onProgress: (received, total) {
          onProgress?.call(total > 0 ? received / total : null);
        },
      );

      // Files need a real extension so the OS can pick an app to open them.
      final extension = _extensionFor(response, fileName);
      final safeBase = title
          .replaceAll(RegExp(r'[^A-Za-z0-9._-]+'), '_')
          .replaceAll(RegExp(r'_+'), '_');
      final trimmed = safeBase.length > 80 ? safeBase.substring(0, 80) : safeBase;
      final savedName = '${key}_$trimmed$extension';
      final finalPath = p.join(dir.path, savedName);

      final existing = File(finalPath);
      if (await existing.exists()) await existing.delete();
      await File(tempPath).rename(finalPath);

      await _db.saveDownload(
        downloadKey: key,
        localPath: finalPath,
        sourceUrl: apiPath,
        fileName: fileName?.trim().isNotEmpty == true ? fileName!.trim() : '$title$extension',
      );

      return finalPath;
    } catch (error) {
      final temp = File(tempPath);
      if (await temp.exists()) await temp.delete();
      final mapped = AppException.from(error);
      // The server refuses to hand out view-only files as downloads.
      if (mapped.statusCode == 403 && isCourseMaterialKey(key)) {
        throw const AppException(AppErrorKind.forbidden, viewOnlyMessage, statusCode: 403);
      }
      throw mapped;
    }
  }

  /// Deletes offline copies of course files that are view-only now: every
  /// key in [viewOnlyKeys] (from the server's flags) and any lesson or
  /// assignment file whose type isn't downloadable (copies saved before
  /// the policy existed). Returns how many copies were removed.
  Future<int> purgeViewOnlyCopies({Iterable<String> viewOnlyKeys = const []}) async {
    final flagged = viewOnlyKeys.toSet();
    var removed = 0;
    try {
      for (final row in await _db.downloads()) {
        final key = row['download_key']?.toString() ?? '';
        if (!isCourseMaterialKey(key)) continue;
        final name = row['file_name']?.toString();
        final path = row['local_path']?.toString();
        final allowed = isDownloadableFileName(name) || isDownloadableFileName(path);
        if (flagged.contains(key) || !allowed) {
          await remove(key);
          removed++;
        }
      }
    } catch (error) {
      appLog('Purging view-only offline copies failed', error);
    }
    return removed;
  }

  String _extensionFor(Response<dynamic> response, String? fileName) {
    final fromName = p.extension(fileName ?? '');
    if (fromName.isNotEmpty && fromName.length <= 6) return fromName.toLowerCase();

    final disposition = response.headers.value('content-disposition') ?? '';
    final nameMatch =
        RegExp(r'''filename\*?=(?:UTF-8'')?"?([^";]+)"?''', caseSensitive: false)
            .firstMatch(disposition);
    if (nameMatch != null) {
      final ext = p.extension(Uri.decodeComponent(nameMatch.group(1)!));
      if (ext.isNotEmpty) return ext.toLowerCase();
    }


    final type = (response.headers.value('content-type') ?? '').toLowerCase();
    const byType = {
      'application/pdf': '.pdf',
      'application/msword': '.doc',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
          '.docx',
      'application/vnd.ms-powerpoint': '.ppt',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation':
          '.pptx',
      'application/vnd.ms-excel': '.xls',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
          '.xlsx',
      'application/zip': '.zip',
      'image/jpeg': '.jpg',
      'image/png': '.png',
      'video/mp4': '.mp4',
      'audio/mpeg': '.mp3',
      'text/plain': '.txt',
    };
    for (final entry in byType.entries) {
      if (type.startsWith(entry.key)) return entry.value;
    }
    return '';
  }

  /// Returns the local path if a downloaded copy still exists on disk.
  Future<String?> localPath(String key) async {
    final path = await _db.downloadPath(key);
    if (path == null) return null;
    if (await File(path).exists()) return path;
    await _db.removeDownload(key);
    return null;
  }

  Future<void> open(String key) async {
    final path = await localPath(key);
    if (path == null) {
      throw const AppException(
        AppErrorKind.notFound,
        'The offline copy is no longer on this device. Download it again.',
      );
    }

    final result = await OpenFilex.open(path);
    if (result.type != ResultType.done) {
      appLog('OpenFilex failed: ${result.type} ${result.message}');
      throw AppException(
        AppErrorKind.unknown,
        result.type == ResultType.noAppToOpen
            ? 'No app on this device can open this file type.'
            : "The file couldn't be opened.",
      );
    }
  }

  Future<void> remove(String key) async {
    final path = await _db.downloadPath(key);
    if (path != null) {
      final file = File(path);
      if (await file.exists()) await file.delete();
    }
    await _db.removeDownload(key);
  }

  /// Deletes every offline file (used on sign-out).
  Future<void> removeAll() async {
    try {
      final dir = await _downloadsDir();
      if (await dir.exists()) await dir.delete(recursive: true);
    } catch (error) {
      appLog('Failed to clear downloads', error);
    }
  }
}
