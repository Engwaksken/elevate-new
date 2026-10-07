import 'package:connectivity_plus/connectivity_plus.dart';

import '../core/assignment_info.dart';
import '../core/formatters.dart';
import '../core/lesson_info.dart';
import '../core/logger.dart';
import 'api_service.dart';
import 'download_service.dart';
import 'local_database.dart';

/// Keeps participant reading material available locally after a Wi-Fi sync.
/// Automatic copies use private app storage and are opened only by app readers.
class OfflineContentService {
  static const _courseCacheVersion = '2';

  OfflineContentService._();

  static final OfflineContentService instance = OfflineContentService._();

  final _api = ApiService.instance;
  final _db = LocalDatabase.instance;
  final _downloads = DownloadService.instance;

  Future<bool> get _onWifi async {
    final results = await Connectivity().checkConnectivity();
    return results.contains(ConnectivityResult.wifi) ||
        results.contains(ConnectivityResult.ethernet);
  }

  /// Older app versions cached lesson text without attachment metadata.
  /// Refresh each course tree once on Wi-Fi after this cache format ships.
  Future<bool> get shouldRefreshCourseTrees async {
    if (!await _onWifi) return false;
    return await _db.getMeta('offline_course_cache_version') !=
        _courseCacheVersion;
  }

  Future<void> syncOnWifi({bool courseTreesRefreshed = false}) async {
    try {
      if (!await _onWifi) return;
    } catch (error) {
      appLog('Could not determine connection for offline cache sync', error);
      return;
    }

    await _cacheCertificates();
    await _cacheCareerDocuments();
    try {
      await _cacheLearningFiles();
      await _db.setMeta('offline_content_synced_at',
          DateTime.now().toUtc().toIso8601String());
      if (courseTreesRefreshed) {
        await _db.setMeta('offline_course_cache_version', _courseCacheVersion);
      }
    } catch (error) {
      appLog('Wi-Fi learning content cache sync failed', error);
    }
  }

  Future<void> _cacheLearningFiles() async {
    for (final course in await _db.readCollection('course_details')) {
      final courseMap = Map<String, dynamic>.from(course);
      final modules = courseMap['modules'];
      if (modules is! List) continue;
      for (final module in modules.whereType<Map>()) {
        if (isTruthy(module['is_locked'])) continue;
        final lessons = module['lessons'];
        if (lessons is! List) continue;
        for (final rawLesson in lessons.whereType<Map>()) {
          final lesson = LessonInfo(Map<String, dynamic>.from(rawLesson));
          final lessonId = lesson.id;
          if (lessonId == null || lesson.isLocked) continue;
          for (final (index, file) in lesson.files.indexed) {
            final key =
                DownloadService.lessonCacheKey(lessonId, file.id ?? index);
            await _cacheFile(
                key, file.readerPath, file.name, _readerName(file.name));
          }
        }
      }
    }

    for (final raw in await _db.readCollection('assignments')) {
      final assignment = AssignmentInfo(raw);
      final id = assignment.id;
      if (id == null) continue;
      for (final file in assignment.attachments) {
        await _cacheFile(
          DownloadService.assignmentCacheKey(id, file.id),
          file.readerPath,
          file.name,
          _readerName(file.name),
        );
      }
    }
  }

  Future<void> _cacheCertificates() async {
    try {
      final items = <dynamic>[];
      var page = 1;
      while (page <= 20) {
        final data = await _api.certificates(page: page);
        final batch = data['data'];
        if (batch is! List) break;
        items.addAll(batch);
        final last = int.tryParse(data['last_page']?.toString() ?? '') ?? page;
        if (page >= last) break;
        page++;
      }
      final uniqueItems = <Map<String, dynamic>>[];
      for (final raw in items.whereType<Map>()) {
        final item = Map<String, dynamic>.from(raw);
        final id = item['id']?.toString();
        final type = item['type']?.toString();
        if (id == null || type == null) continue;
        uniqueItems
            .add({...item, '_certificate_cache_id': id, 'id': '${type}_$id'});
        await _cacheFile(
          '${DownloadService.offlineCachePrefix}certificate_${type}_$id',
          '/certificates/$type/$id/download',
          item['title']?.toString() ?? 'Certificate',
          'certificate-${item['number'] ?? id}.pdf',
        );
      }
      await _db.replaceCollection('offline_certificates', uniqueItems);
    } catch (error) {
      appLog('Wi-Fi certificate cache sync failed', error);
    }
  }

  Future<void> _cacheCareerDocuments() async {
    try {
      final data = await _api.careerDocuments();
      await _db.cacheItem(
        collection: 'offline_documents',
        itemId: 'career',
        payload: data,
      );

      for (final raw in data['resumes'] as List? ?? const []) {
        if (raw is! Map) continue;
        final resume = Map<String, dynamic>.from(raw);
        final id = resume['id']?.toString();
        if (id == null) continue;
        await _cacheFile(
          '${DownloadService.offlineCachePrefix}resume_$id',
          '/career/resumes/$id/download',
          resume['title']?.toString() ?? 'Resume',
          'resume-$id.pdf',
        );
      }
      for (final raw in data['cover_letters'] as List? ?? const []) {
        if (raw is! Map) continue;
        final letter = Map<String, dynamic>.from(raw);
        final id = letter['id']?.toString();
        if (id == null) continue;
        await _cacheFile(
          '${DownloadService.offlineCachePrefix}cover_letter_$id',
          '/career/cover-letters/$id/download',
          letter['title']?.toString() ?? 'Cover letter',
          'cover-letter-$id.pdf',
        );
      }
    } catch (error) {
      appLog('Wi-Fi career document cache sync failed', error);
    }
  }

  Future<void> _cacheFile(
    String key,
    String? apiPath,
    String title,
    String? fileName,
  ) async {
    try {
      if (apiPath == null || await _downloads.localPath(key) != null) return;
      await _downloads.download(
        key: key,
        apiPath: apiPath,
        title: title,
        fileName: fileName,
        downloadable: true,
        offlineCache: true,
      );
    } catch (error) {
      appLog('Could not cache offline reading file $key', error);
    }
  }

  static String? _readerName(String name) =>
      const {'docx', 'odt', 'rtf'}.contains(fileExtension(name))
          ? '${name.split('.').first}.pdf'
          : name;
}
