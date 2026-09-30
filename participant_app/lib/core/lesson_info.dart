import 'formatters.dart';

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

  List<LessonFile> get extraFiles {
    final files = raw['files'];
    if (files is! List) return const [];
    return files
        .whereType<Map>()
        .map((f) => LessonFile(Map<String, dynamic>.from(f)))
        .where((f) => f.downloadPath != null)
        .toList();
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

class LessonFile {
  LessonFile(this.raw);

  final Map<String, dynamic> raw;

  dynamic get id => raw['id'];

  String get name {
    final value = raw['name']?.toString().trim() ?? '';
    return value.isEmpty ? 'Attachment' : value;
  }

  int? get sizeBytes => int.tryParse(raw['size_bytes']?.toString() ?? '');

  String? get downloadPath {
    final path = (raw['download_path'] ?? raw['download_url'])?.toString().trim() ?? '';
    if (path.isEmpty || path.contains('/storage/')) return null;
    return path;
  }
}

String? fileSizeLabel(int? bytes) {
  if (bytes == null || bytes <= 0) return null;
  if (bytes < 1024) return '$bytes B';
  if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(0)} KB';
  return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
}
