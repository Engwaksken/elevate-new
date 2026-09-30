import '../core/network/app_exception.dart';
import 'api_service.dart';
import 'local_database.dart';
import 'sync_service.dart';

enum ProgressSaveResult { synced, queued }

/// Marks lessons complete/incomplete, offline-first: the change is saved
/// locally immediately, sent via PUT /lessons/{id}/progress when online,
/// or queued as a `lesson_progress` offline action otherwise.
class LessonProgressService {
  LessonProgressService._();

  static final LessonProgressService instance = LessonProgressService._();

  final LocalDatabase _db = LocalDatabase.instance;

  Future<ProgressSaveResult> setCompleted({
    required int lessonId,
    required bool completed,
    int? courseId,
    double? localCoursePercent,
    int timeSpentSeconds = 0,
  }) async {
    final wasCompleted = (await _db.completedLessonIds()).contains(lessonId);
    await _db.setLocalLessonCompletion(lessonId, completed);

    Future<ProgressSaveResult> queue() async {
      await SyncService.instance.queueAction('lesson_progress', {
        'lesson_id': lessonId,
        'completed': completed,
        if (timeSpentSeconds > 0) 'time_spent_seconds': timeSpentSeconds,
      });
      if (courseId != null && localCoursePercent != null) {
        await _db.setCourseProgress(courseId, localCoursePercent);
      }
      SyncService.instance.notifyLocalChange();
      return ProgressSaveResult.queued;
    }

    if (!await SyncService.instance.isOnline()) return queue();

    try {
      final response = await ApiService.instance.markLessonProgress(
        lessonId: lessonId,
        completed: completed,
        seconds: timeSpentSeconds,
      );

      final serverPercent = double.tryParse(
        response['course_progress_percent']?.toString() ?? '',
      );
      final percent = serverPercent ?? localCoursePercent;
      final serverCourseId =
          int.tryParse(response['course_id']?.toString() ?? '') ?? courseId;
      if (serverCourseId != null && percent != null) {
        await _db.setCourseProgress(
          serverCourseId,
          percent,
          status: response['course_status']?.toString(),
        );
      }
      SyncService.instance.notifyLocalChange();
      return ProgressSaveResult.synced;
    } catch (error) {
      final mapped = AppException.from(error);
      if (mapped.isRetryable) return queue();

      // The server rejected the change (e.g. 403): undo the local state.
      await _db.setLocalLessonCompletion(lessonId, wasCompleted);
      SyncService.instance.notifyLocalChange();
      throw mapped;
    }
  }
}
