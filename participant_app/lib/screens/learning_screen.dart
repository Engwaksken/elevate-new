import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../services/local_database.dart';
import '../widgets/cached_data.dart';
import '../widgets/course_card.dart';
import '../widgets/search_field.dart';
import '../widgets/state_views.dart';
import 'course_detail_screen.dart';

class _LearningData {
  const _LearningData(this.courses, this.progress);

  final List<Map<String, dynamic>> courses;
  final Map<int, double> progress;
}

class LearningScreen extends StatefulWidget {
  const LearningScreen({super.key});

  @override
  State<LearningScreen> createState() => _LearningScreenState();
}

class _LearningScreenState extends State<LearningScreen>
    with CachedDataMixin<LearningScreen, _LearningData> {
  String _search = '';

  @override
  Future<_LearningData> readCache() async {
    final db = LocalDatabase.instance;
    final courses = await db.readCollection('courses');
    courses.sort((a, b) => (a['title']?.toString() ?? '')
        .toLowerCase()
        .compareTo((b['title']?.toString() ?? '').toLowerCase()));
    return _LearningData(courses, await db.courseProgress());
  }

  void _open(Map<String, dynamic> course) {
    final id = int.tryParse(course['id']?.toString() ?? '');
    if (id == null) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => CourseDetailScreen(courseId: id, initialCourse: course),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const LoadingSkeleton(itemHeight: 110);

    if (loadError != null) {
      return ErrorState(error: loadError!, onRetry: reload);
    }

    final all = data?.courses ?? const [];
    final term = _search.toLowerCase();
    final courses = term.isEmpty
        ? all
        : all.where((c) {
            final text = '${c['title'] ?? ''} ${c['code'] ?? ''} ${c['summary'] ?? ''}';
            return text.toLowerCase().contains(term);
          }).toList();

    return Column(
      children: [
        if (all.isNotEmpty)
          SearchField(
            hint: 'Search courses',
            onChanged: (value) => setState(() => _search = value.trim()),
          ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: refreshFromServer,
            child: courses.isEmpty
                ? EmptyState(
                    icon: Icons.menu_book_outlined,
                    title: all.isEmpty ? 'No courses yet' : 'No matching courses',
                    message: all.isEmpty
                        ? 'Courses you are enrolled in will appear here. Pull down to refresh.'
                        : 'Try a different search term.',
                  )
                : ListView.separated(
                    padding: AppSpacing.listPadding,
                    itemCount: courses.length,
                    separatorBuilder: (_, __) =>
                        const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final course = courses[index];
                      final id = int.tryParse(course['id']?.toString() ?? '');
                      return CourseCard(
                        course: course,
                        progress: id == null ? null : data!.progress[id],
                        onTap: () => _open(course),
                      );
                    },
                  ),
          ),
        ),
      ],
    );
  }
}
