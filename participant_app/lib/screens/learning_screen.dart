import 'package:flutter/material.dart';

import '../services/local_database.dart';
import '../services/sync_service.dart';
import 'course_detail_screen.dart';

class LearningScreen extends StatefulWidget {
  const LearningScreen({super.key});

  @override
  State<LearningScreen> createState() => _LearningScreenState();
}

class _LearningScreenState extends State<LearningScreen> {
  String _search = '';

  Future<List<Map<String, dynamic>>> _courses() =>
      LocalDatabase.instance.readCollection('courses');

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _courses(),
      builder: (context, snapshot) {
        var courses = snapshot.data ?? [];

        if (_search.isNotEmpty) {
          final term = _search.toLowerCase();
          courses = courses
              .where((course) => course.toString().toLowerCase().contains(term))
              .toList();
        }

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: TextField(
                onChanged: (value) => setState(() => _search = value.trim()),
                decoration: const InputDecoration(
                  hintText: 'Search courses',
                  prefixIcon: Icon(Icons.search),
                  border: OutlineInputBorder(),
                ),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async {
                  await SyncService.instance.syncNow();
                  if (mounted) setState(() {});
                },
                child: courses.isEmpty
                    ? ListView(
                        children: const [
                          SizedBox(height: 180),
                          Center(child: Text('No synced courses yet.')),
                        ],
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
                        itemCount: courses.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final course = courses[index];

                          return Card(
                            child: ListTile(
                              leading: const CircleAvatar(
                                child: Icon(Icons.menu_book_outlined),
                              ),
                              title: Text(
                                course['title']?.toString() ?? 'Course',
                              ),
                              subtitle: Text(
                                [
                                  course['code'],
                                  course['status'],
                                  course['delivery_mode'],
                                ]
                                    .where(
                                      (value) =>
                                          value != null &&
                                          value.toString().isNotEmpty,
                                    )
                                    .join(' · '),
                              ),
                              trailing: const Icon(Icons.chevron_right),
                              onTap: () {
                                final id = int.tryParse(
                                  course['id']?.toString() ?? '',
                                );

                                if (id == null) return;

                                Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => CourseDetailScreen(
                                      courseId: id,
                                      initialCourse: course,
                                    ),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      ),
              ),
            ),
          ],
        );
      },
    );
  }
}
