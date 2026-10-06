import 'formatters.dart';
import 'learning_file.dart';

export 'learning_file.dart';

/// Typed view over the lesson JSON from GET /courses/{id} and
/// GET /lessons/{id} (see docs/participant_api_contract.md).
class LessonInfo {
  LessonInfo(this.raw, {this.moduleLocked = false});

  final Map<String, dynamic> raw;
  final bool moduleLocked;

  int? get id => int.tryParse(raw['id']?.toString() ?? '');

  String get title => tidyTitle(raw['title']?.toString(), fallback: 'Lesson');

  LessonKind get kind => LessonKind.fromLesson(raw);

  String? get durationLabel =>
      minutesLabel(raw['duration_minutes'] ?? raw['estimated_minutes']);

  bool get isLocked => moduleLocked || isTruthy(raw['is_locked']);

  String? get body {
    final value = (raw['body'] ?? raw['content'])?.toString().trim() ?? '';
    return value.isEmpty ? null : value;
  }

  String? _url(String key) {
    final value = raw[key]?.toString().trim() ?? '';
    final uri = Uri.tryParse(value);
    if (value.isEmpty || uri == null || !uri.hasScheme) return null;
    return value;
  }

  String? get videoUrl => _url('video_url');

  String? get externalUrl => _url('external_url');

  /// True only when the server says a file really exists. Older payloads
  /// without `has_file` fall back to a non-null `resource_url`.
  bool get hasFile {
    if (raw.containsKey('has_file')) {
      return isTruthy(raw['has_file']) && downloadPath != null;
    }
    return downloadPath != null;
  }

  /// Authenticated download path, relative to the participant API base.
  String? get downloadPath {
    final path = raw['download_path']?.toString().trim() ?? '';
    if (path.isNotEmpty) return path;

    final url = (raw['download_url'] ?? raw['file_url'] ?? raw['resource_url'])
            ?.toString()
            .trim() ??
        '';
    // Never use legacy public /storage/ URLs: they are unauthenticated and
    // were the cause of the 404 on file lessons.
    if (url.isEmpty || url.contains('/storage/')) return null;
    return url;
  }

  String? get fileName {
    final value = raw['file_name']?.toString().trim() ?? '';
    return value.isEmpty ? null : value;
  }

  String? get fileMimeType => raw['file_mime_type']?.toString();

  int? get fileSizeBytes => int.tryParse(raw['file_size_bytes']?.toString() ?? '');

  /// Whether the lesson's primary file may be saved on the device. Server
  /// `file_downloadable` wins; older payloads use the extension policy.
  bool get fileDownloadable {
    if (raw['file_downloadable'] != null) return isTruthy(raw['file_downloadable']);
    return isDownloadableFileName(fileName);
  }

  /// Every material file of the lesson (`files[]`, in upload order; the
  /// first one is the primary file). Payloads without `files[]` fall back
  /// to the single primary file.
  List<LearningFileInfo> get files {
    final list = raw['files'];
    final parsed = list is List
        ? list
            .whereType<Map>()
            .map((f) => LearningFileInfo(Map<String, dynamic>.from(f)))
            .where((f) => f.downloadPath != null)
            .toList()
        : <LearningFileInfo>[];
    if (parsed.isNotEmpty || !hasFile) return parsed;

    return [
      LearningFileInfo({
        'id': null,
        'name': fileName ?? title,
        'mime_type': fileMimeType,
        'size_bytes': fileSizeBytes,
        'downloadable': fileDownloadable,
        'download_path': downloadPath,
      }),
    ];
  }

  /// Offline key for one of [files]: the primary file keeps the lesson key
  /// (shown as "Available offline" in the course outline).
  String? fileDownloadKey(LearningFileInfo file, int index) {
    final lessonId = id;
    if (lessonId == null) return null;
    if (index == 0 || file.id == null) return 'lesson_$lessonId';
    return 'lesson_${lessonId}_file_${file.id}';
  }

  bool get completedOnServer {
    final progress = raw['progress'];
    if (progress is Map) {
      return isTruthy(progress['completed']) || progress['completed_at'] != null;
    }
    return isTruthy(raw['completed']);
  }

  /// "Document · 30 min"
  String get subtitle =>
      [kind.label, durationLabel].whereType<String>().join(' · ');
}

/// Older name for a lesson file.
typedef LessonFile = LearningFileInfo;
