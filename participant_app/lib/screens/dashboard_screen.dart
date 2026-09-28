import 'package:flutter/material.dart';

import '../services/local_database.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Future<Map<String, int>> _counts() async {
    final db = LocalDatabase.instance;
    final values = <String, int>{};
    for (final key in [
      'courses',
      'assignments',
      'mentorship',
      'jobs',
      'events',
      'announcements',
      'notifications',
    ]) {
      values[key] = (await db.readCollection(key)).length;
    }
    return values;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, int>>(
      future: _counts(),
      builder: (context, snapshot) {
        final counts = snapshot.data ?? const <String, int>{};

        final cards = [
          ('Learning', counts['courses'] ?? 0, Icons.menu_book_outlined),
          (
            'Assignments',
            counts['assignments'] ?? 0,
            Icons.assignment_outlined
          ),
          ('Mentorship', counts['mentorship'] ?? 0, Icons.diversity_3_outlined),
          ('Jobs', counts['jobs'] ?? 0, Icons.work_outline),
          ('Events', counts['events'] ?? 0, Icons.event_outlined),
          (
            'Announcements',
            counts['announcements'] ?? 0,
            Icons.campaign_outlined
          ),
          (
            'Notifications',
            counts['notifications'] ?? 0,
            Icons.notifications_outlined
          ),
        ];

        return RefreshIndicator(
          onRefresh: () async => setState(() {}),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const Text(
                'Welcome back',
                style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 6),
              const Text(
                'Your latest synced learning, mentorship and opportunities remain available offline.',
              ),
              const SizedBox(height: 20),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: cards.length,
                gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 220,
                  mainAxisExtent: 125,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                ),
                itemBuilder: (context, index) {
                  final item = cards[index];
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(item.$3, color: const Color(0xFF800000)),
                          const Spacer(),
                          Text(
                            '${item.$2}',
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          Text(item.$1),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }
}
