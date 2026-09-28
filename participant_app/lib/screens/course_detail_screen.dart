import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';

class CourseDetailScreen extends StatefulWidget {
  const CourseDetailScreen({
    super.key,
    required this.courseId,
    required this.initialCourse,
  });

  final int courseId;
  final Map<String, dynamic> initialCourse;

  @override
  State<CourseDetailScreen> createState() => _CourseDetailScreenState();
}

class _CourseDetailScreenState extends State<CourseDetailScreen> {
  Map<String, dynamic>? _course;
  bool _loading = true;

  String get _cacheKey => 'course_${widget.courseId}';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final cached = await LocalDatabase.instance.readItem(
      'course_details',
      _cacheKey,
    );

    if (cached != null && mounted) {
      setState(() {
        _course = cached;
        _loading = false;
      });
    }

    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        setState(() {
          _course ??= widget.initialCourse;
          _loading = false;
        });
      }
      return;
    }

    try {
      final response = await ApiService.instance.course(widget.courseId);
      final course = Map<String, dynamic>.from(response['course'] as Map);

      await LocalDatabase.instance.cacheItem(
        collection: 'course_details',
        itemId: _cacheKey,
        payload: course,
      );

      if (mounted) {
        setState(() {
          _course = course;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _course ??= widget.initialCourse;
          _loading = false;
        });
      }
    }
  }

  Future<void> _markComplete(int lessonId) async {
    if (await SyncService.instance.isOnline()) {
      await ApiService.instance.markLessonProgress(
        lessonId: lessonId,
        completed: true,
      );
    } else {
      await SyncService.instance.queueAction(
        'lesson_progress',
        {
          'lesson_id': lessonId,
          'completed': true,
          'time_spent_seconds': 0,
        },
      );
    }

    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          await SyncService.instance.isOnline()
              ? 'Lesson marked complete.'
              : 'Saved offline. It will sync when you reconnect.',
        ),
      ),
    );
  }

  Future<void> _resourceAction(Map<String, dynamic> lesson) async {
    final lessonId = lesson['id']?.toString() ?? '';
    final url = lesson['resource_url']?.toString();

    if (url == null || url.isEmpty) return;

    final key = 'lesson_$lessonId';
    final local = await DownloadService.instance.localPath(key);

    if (!mounted) return;

    if (local != null) {
      showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        builder: (_) => SafeArea(
          child: Wrap(
            children: [
              ListTile(
                leading: const Icon(Icons.open_in_new),
                title: const Text('Open offline copy'),
                onTap: () async {
                  Navigator.pop(context);
                  await DownloadService.instance.open(key);
                },
              ),
              ListTile(
                leading: const Icon(Icons.delete_outline),
                title: const Text('Remove offline copy'),
                onTap: () async {
                  Navigator.pop(context);
                  await DownloadService.instance.remove(key);
                  if (mounted) setState(() {});
                },
              ),
            ],
          ),
        ),
      );
      return;
    }

    try {
      await DownloadService.instance.download(
        key: key,
        url: url,
        suggestedName:
            'lesson_${lessonId}_${lesson['title']?.toString() ?? 'resource'}',
      );

      if (mounted) {
        setState(() {});
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Resource saved for offline use.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text(error.toString().replaceFirst('Exception: ', ''))),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final course = _course ?? widget.initialCourse;
    final modules =
        course['modules'] is List ? course['modules'] as List : const [];

    return Scaffold(
      appBar: AppBar(title: Text(course['title']?.toString() ?? 'Course')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(14),
          children: [
            if ((course['summary']?.toString() ?? '').isNotEmpty)
              Text(course['summary'].toString()),
            const SizedBox(height: 14),
            if (modules.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(18),
                  child: Text('No synced modules available yet.'),
                ),
              ),
            for (final rawModule in modules)
              if (rawModule is Map)
                _ModuleCard(
                  module: Map<String, dynamic>.from(rawModule),
                  onComplete: _markComplete,
                  onResource: _resourceAction,
                ),
          ],
        ),
      ),
    );
  }
}

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({
    required this.module,
    required this.onComplete,
    required this.onResource,
  });

  final Map<String, dynamic> module;
  final Future<void> Function(int lessonId) onComplete;
  final Future<void> Function(Map<String, dynamic> lesson) onResource;

  @override
  Widget build(BuildContext context) {
    final lessons =
        module['lessons'] is List ? module['lessons'] as List : const [];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        initiallyExpanded: true,
        title: Text(
          module['title']?.toString() ?? 'Module',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        children: [
          for (final rawLesson in lessons)
            if (rawLesson is Map)
              Builder(
                builder: (context) {
                  final lesson = Map<String, dynamic>.from(rawLesson);
                  final lessonId = int.tryParse(lesson['id']?.toString() ?? '');

                  return ListTile(
                    leading: const Icon(Icons.play_circle_outline),
                    title: Text(lesson['title']?.toString() ?? 'Lesson'),
                    subtitle: Text(
                      [
                        lesson['content_type'],
                        if (lesson['estimated_minutes'] != null)
                          '${lesson['estimated_minutes']} min',
                      ].where((value) => value != null).join(' · '),
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) async {
                        if (value == 'complete' && lessonId != null) {
                          await onComplete(lessonId);
                        } else if (value == 'resource') {
                          await onResource(lesson);
                        } else if (value == 'video') {
                          final url = lesson['video_url']?.toString();
                          if (url != null && url.isNotEmpty) {
                            await launchUrl(
                              Uri.parse(url),
                              mode: LaunchMode.externalApplication,
                            );
                          }
                        }
                      },
                      itemBuilder: (_) => [
                        const PopupMenuItem(
                          value: 'complete',
                          child: Text('Mark complete'),
                        ),
                        if ((lesson['resource_url']?.toString() ?? '')
                            .isNotEmpty)
                          const PopupMenuItem(
                            value: 'resource',
                            child: Text('Offline resource'),
                          ),
                        if ((lesson['video_url']?.toString() ?? '').isNotEmpty)
                          const PopupMenuItem(
                            value: 'video',
                            child: Text('Open video'),
                          ),
                      ],
                    ),
                  );
                },
              ),
        ],
      ),
    );
  }
}
