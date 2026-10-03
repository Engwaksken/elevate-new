import 'package:elevateher360_participant/core/goal_info.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('GoalInfo', () {
    test('parses a full goal payload', () {
      final goal = GoalInfo({
        'id': 7,
        'title': 'get an internship',
        'description': 'Apply widely',
        'category': 'career',
        'unit': 'applications',
        'baseline_value': 0,
        'target_value': 10,
        'current_value': 3,
        'progress_percent': 30.0,
        'start_date': '2026-01-01',
        'target_date': '2026-06-30',
        'priority': 'high',
        'status': 'in_progress',
      });

      expect(goal.id, 7);
      expect(goal.title, 'Get an internship');
      expect(goal.categoryLabel, 'Career');
      expect(goal.targetValue, 10);
      expect(goal.currentValue, 3);
      expect(goal.progressPercent, 30);
      expect(goal.progressFraction, closeTo(0.3, 0.0001));
      expect(goal.priority, GoalPriority.high);
      expect(goal.priorityLabel, 'High priority');
      expect(goal.status, 'in_progress');
      expect(goal.isCompleted, isFalse);
      expect(goal.valueLabel, '3 / 10 applications');
      expect(goal.targetDate, DateTime(2026, 6, 30));
      expect(goal.dueLabel, contains('2026'));
    });

    test('handles null and partial payloads defensively', () {
      final goal = GoalInfo({
        'title': null,
        'target_value': null,
        'current_value': null,
        'progress_percent': null,
        'status': null,
        'priority': 'nonsense',
      });

      expect(goal.id, isNull);
      expect(goal.title, 'Goal');
      expect(goal.progressPercent, 0);
      expect(goal.priority, GoalPriority.medium);
      expect(goal.statusLabel, 'Not started');
      expect(goal.valueLabel, isNull);
      expect(goal.dueLabel, isNull);
      expect(goal.isCompleted, isFalse);
    });

    test('treats 100 percent or completed as completed', () {
      expect(GoalInfo({'id': 1, 'progress_percent': 100}).isCompleted, isTrue);
      expect(GoalInfo({'id': 1, 'status': 'completed'}).isCompleted, isTrue);
    });

    test('parses numbers supplied as strings', () {
      final goal = GoalInfo({
        'id': '4',
        'target_value': '5.5',
        'current_value': '1.0',
        'progress_percent': '18.18',
      });
      expect(goal.id, 4);
      expect(goal.targetValue, 5.5);
      expect(goal.valueLabel, '1 / 5.5');
    });
  });

  group('GoalSummary', () {
    test('builds counts from a server summary', () {
      final summary = GoalSummary.fromJson({
        'total': 5,
        'in_progress': 3,
        'completed': 1,
        'cancelled': 1,
        'average_progress': 42.5,
      });
      expect(summary.total, 5);
      expect(summary.inProgress, 3);
      expect(summary.completed, 1);
      expect(summary.averageProgress, 42.5);
    });

    test('falls back to zero for a missing summary', () {
      final summary = GoalSummary.fromJson(null);
      expect(summary.total, 0);
      expect(summary.averageProgress, 0);
    });
  });
}
