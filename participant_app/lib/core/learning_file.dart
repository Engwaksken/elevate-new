import 'formatters.dart';

/// File types participants may save on their device. Mirrors the server's
/// `elearning.downloadable_extensions`: every other lesson or assignment
/// file is view-only and is only ever shown inside the app.
const Set<String> downloadableExtensions = {'xlsx', 'xls', 'csv', 'zip'};

/// Lower-case extension of [name] without the dot ('' when there is none).
String fileExtension(String? name) {
  final text = (name ?? '').trim();
  final cleaned = text.split(RegExp(r'[?#]')).first;
  final dot = cleaned.lastIndexOf('.');
  if (dot < 0 || dot == cleaned.length - 1) return '';
  final ext = cleaned.substring(dot + 1).toLowerCase();
  return ext.contains(RegExp(r'[\\/]')) ? '' : ext;
}

/// Whether the download policy lets participants keep a file with [name].
bool isDownloadableFileName(String? name) =>
    downloadableExtensions.contains(fileExtension(name));

/// How the in-app viewer shows a file.
enum FileViewKind { pdf, image, text, video, audio, other }

FileViewKind fileViewKindFor(String? name, [String? mimeType]) {
  final ext = fileExtension(name);
  final mime = (mimeType ?? '').toLowerCase();

  if (ext == 'pdf' || mime == 'application/pdf') return FileViewKind.pdf;
  if (const {'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'}.contains(ext) ||
      mime.startsWith('image/')) {
    return FileViewKind.image;
  }
  if (const {'mp4', 'm4v', 'mov', 'webm', '3gp'}.contains(ext) ||
      mime.startsWith('video/')) {
    return FileViewKind.video;
  }
  if (const {'mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga'}.contains(ext) ||
      mime.startsWith('audio/')) {
    return FileViewKind.audio;
  }
  if (const {'txt', 'csv', 'md', 'log', 'json'}.contains(ext) ||
      mime.startsWith('text/')) {
    return FileViewKind.text;
  }
  return FileViewKind.other;
}

/// One lesson file, assignment attachment or submitted file from the API:
/// `{id, name, mime_type, size_bytes, downloadable, view_only,
/// download_path, download_url}`.
class LearningFileInfo {
  LearningFileInfo(this.raw, {this.ownSubmission = false});

  final Map<String, dynamic> raw;

  /// A file the participant submitted herself: always downloadable.
  final bool ownSubmission;

  dynamic get id => raw['id'];

  String get name {
    final value =
        (raw['name'] ?? raw['original_name'])?.toString().trim() ?? '';
    return value.isEmpty ? 'Attachment' : value;
  }

  String? get mimeType {
    final value = raw['mime_type']?.toString().trim() ?? '';
    return value.isEmpty ? null : value;
  }

  int? get sizeBytes => int.tryParse(raw['size_bytes']?.toString() ?? '');

  String get extension => fileExtension(name);

  /// Authenticated API path. Legacy public /storage/ URLs are never used.
  String? get downloadPath {
    final path =
        (raw['download_path'] ?? raw['download_url'])?.toString().trim() ?? '';
    if (path.isEmpty || path.contains('/storage/')) return null;
    return path;
  }

  /// Server flags win (`downloadable`, then `view_only`); payloads without
  /// them fall back to the extension policy.
  bool get downloadable {
    if (ownSubmission) return true;
    if (raw['downloadable'] != null) return isTruthy(raw['downloadable']);
    if (raw['view_only'] != null) return !isTruthy(raw['view_only']);
    return isDownloadableFileName(name);
  }

  bool get viewOnly => !downloadable;

  FileViewKind get viewKind => const {'docx', 'odt', 'rtf'}.contains(extension)
      ? FileViewKind.pdf
      : fileViewKindFor(name, mimeType);

  /// Authenticated API path that asks Laravel to convert supported Word
  /// documents into a PDF for the native in-app reader.
  String? get readerPath {
    final path = downloadPath;
    if (path == null || !const {'docx', 'odt', 'rtf'}.contains(extension)) {
      return path;
    }
    final uri = Uri.tryParse(path);
    return uri?.replace(
        queryParameters: {...uri.queryParameters, 'format': 'pdf'}).toString();
  }
}

/// "1.4 MB" / "320 KB"; null for unknown sizes.
String? fileSizeLabel(int? bytes) {
  if (bytes == null || bytes <= 0) return null;
  if (bytes < 1024) return '$bytes B';
  if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(0)} KB';
  return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
}
