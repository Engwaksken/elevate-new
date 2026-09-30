import 'package:flutter/material.dart';

/// Lesson content types from the backend (`text`, `video`, `file`, `link`,
/// `mixed`) mapped to user-facing labels and icons.
enum LessonKind {
  reading('Reading', Icons.article_outlined),
  video('Video', Icons.play_circle_outline),
  document('Document', Icons.description_outlined),
  link('Web link', Icons.link),
  mixed('Lesson', Icons.auto_stories_outlined);

  const LessonKind(this.label, this.icon);

  final String label;
  final IconData icon;

  static LessonKind fromLesson(Map<String, dynamic> lesson) {
    final raw = (lesson['content_type'] ?? lesson['type'] ?? '')
        .toString()
        .trim()
        .toLowerCase();

    switch (raw) {
      case 'text':
      case 'reading':
      case 'article':
        return LessonKind.reading;
      case 'video':
        return LessonKind.video;
      case 'file':
      case 'document':
      case 'pdf':
        return LessonKind.document;
      case 'link':
      case 'url':
        return LessonKind.link;
    }

    // Unknown or missing type: infer from what the lesson carries.
    if (_has(lesson, 'video_url')) return LessonKind.video;
    if (isTruthy(lesson['has_file']) ||
        _has(lesson, 'download_path') ||
        _has(lesson, 'file_url') ||
        _has(lesson, 'resource_url')) {
      return LessonKind.document;
    }
    if (_has(lesson, 'external_url')) return LessonKind.link;
    if (_has(lesson, 'content') || _has(lesson, 'body')) {
      return LessonKind.reading;
    }
    return LessonKind.mixed;
  }

  static bool _has(Map<String, dynamic> map, String key) =>
      (map[key]?.toString().trim() ?? '').isNotEmpty;
}

/// Tidies inconsistent data-entry titles for display:
/// "lesson 1" -> "Lesson 1", "Lesson2" -> "Lesson 2", extra spaces removed.
/// Titles that already contain capitals are otherwise left as written.
String tidyTitle(String? raw, {String fallback = 'Untitled'}) {
  var value = (raw ?? '').replaceAll(RegExp(r'\s+'), ' ').trim();
  if (value.isEmpty) return fallback;

  // Insert a space between a word and a trailing number: "Lesson2".
  value = value.replaceAllMapped(
    RegExp(r'^([A-Za-z]{3,})(\d+)\b'),
    (m) => '${m[1]} ${m[2]}',
  );

  return value[0].toUpperCase() + value.substring(1);
}

String? minutesLabel(dynamic minutes) {
  final value = int.tryParse(minutes?.toString() ?? '');
  if (value == null || value <= 0) return null;
  if (value < 60) return '$value min';
  final h = value ~/ 60;
  final m = value % 60;
  return m == 0 ? '$h h' : '$h h $m min';
}

const _months = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
];

/// "12 Mar 2026, 14:30" in device local time; returns null if unparseable.
String? formatDateTime(dynamic value, {bool withTime = true}) {
  final parsed = DateTime.tryParse(value?.toString() ?? '');
  if (parsed == null) return null;
  final d = parsed.toLocal();
  final date = '${d.day} ${_months[d.month - 1]} ${d.year}';
  if (!withTime) return date;
  final hh = d.hour.toString().padLeft(2, '0');
  final mm = d.minute.toString().padLeft(2, '0');
  return '$date, $hh:$mm';
}

/// "Just now", "5 min ago", "3 h ago", otherwise a date.
String relativeTime(dynamic value) {
  final parsed = DateTime.tryParse(value?.toString() ?? '');
  if (parsed == null) return '';
  final diff = DateTime.now().difference(parsed.toLocal());
  if (diff.inMinutes < 1) return 'Just now';
  if (diff.inMinutes < 60) return '${diff.inMinutes} min ago';
  if (diff.inHours < 24) return '${diff.inHours} h ago';
  return formatDateTime(value, withTime: false) ?? '';
}

/// Title-cases backend enum strings: "in_progress" -> "In progress".
String humanise(dynamic value) {
  final text = (value?.toString() ?? '').replaceAll('_', ' ').trim();
  if (text.isEmpty) return '';
  return text[0].toUpperCase() + text.substring(1).toLowerCase();
}

/// Converts the simple HTML or plain text used in lesson bodies into
/// readable paragraphs, without adding an HTML-rendering dependency.
List<String> plainParagraphs(String? raw) {
  if (raw == null || raw.trim().isEmpty) return const [];

  var text = raw
      .replaceAll(RegExp(r'<\s*br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(
        RegExp(r'</\s*(p|div|h[1-6]|ul|ol|blockquote)\s*>',
            caseSensitive: false),
        '\n\n',
      )
      .replaceAll(RegExp(r'<\s*li[^>]*>', caseSensitive: false), '\n• ')
      .replaceAll(RegExp(r'<[^>]+>'), '')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&amp;', '&')
      .replaceAll('&lt;', '<')
      .replaceAll('&gt;', '>')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .replaceAll('\r\n', '\n');

  return text
      .split(RegExp(r'\n\s*\n'))
      .map((p) => p.trim())
      .where((p) => p.isNotEmpty)
      .toList();
}

bool isTruthy(dynamic value) =>
    value == true ||
    value == 1 ||
    value?.toString() == '1' ||
    value?.toString().toLowerCase() == 'true';
