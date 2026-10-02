class TimetableReminderInfo {
  const TimetableReminderInfo(this.raw);
  final Map<String, dynamic> raw;

  String get sourceId => raw['source_id']?.toString() ?? '';
  DateTime? get startsAt =>
      DateTime.tryParse(raw['scheduled_at']?.toString() ?? '');
  DateTime? get notifyAt => startsAt?.subtract(const Duration(minutes: 10));

  int get notificationId {
    var hash = 2166136261;
    for (final unit in 'timetable_lesson-$sourceId'.codeUnits) {
      hash = ((hash ^ unit) * 16777619) & 0xffffffff;
    }
    return hash & 0x7fffffff;
  }
}
