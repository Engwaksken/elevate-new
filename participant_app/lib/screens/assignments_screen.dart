import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';

class AssignmentsScreen extends StatefulWidget {
  const AssignmentsScreen({super.key});

  @override
  State<AssignmentsScreen> createState() => _AssignmentsScreenState();
}

class _AssignmentsScreenState extends State<AssignmentsScreen> {
  Future<List<Map<String, dynamic>>> _load() =>
      LocalDatabase.instance.readCollection('assignments');

  Future<void> _submit(Map<String, dynamic> assignment) async {
    final text = TextEditingController();
    String? filePath;

    final submitted = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Padding(
              padding: EdgeInsets.fromLTRB(
                18,
                8,
                18,
                MediaQuery.of(context).viewInsets.bottom + 18,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    assignment['title']?.toString() ?? 'Assignment',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: text,
                    maxLines: 5,
                    decoration: const InputDecoration(
                      labelText: 'Response / notes',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () async {
                      final result = await FilePicker.platform.pickFiles(
                        allowMultiple: false,
                      );
                      if (result == null || result.files.single.path == null) {
                        return;
                      }
                      setSheetState(() {
                        filePath = result.files.single.path;
                      });
                    },
                    icon: const Icon(Icons.attach_file),
                    label: Text(
                      filePath == null
                          ? 'Attach File'
                          : filePath!.split(RegExp(r'[\\/]')).last,
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: () => Navigator.pop(sheetContext, true),
                      icon: const Icon(Icons.send),
                      label: const Text('Submit'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (submitted != true) return;

    final id = int.tryParse(assignment['id']?.toString() ?? '');
    if (id == null) return;

    final online = await SyncService.instance.isOnline();

    try {
      if (online) {
        await ApiService.instance.submitAssignment(
          assessmentId: id,
          text: text.text,
          localFilePath: filePath,
        );
      } else {
        await SyncService.instance.queueAssignmentSubmission(
          assessmentId: id,
          text: text.text,
          localFilePath: filePath,
        );
      }

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            online
                ? 'Assignment submitted.'
                : 'Submission queued and will send when you reconnect.',
          ),
        ),
      );
      setState(() {});
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Submission could not be completed.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: _load(),
      builder: (context, snapshot) {
        final items = snapshot.data ?? [];

        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (items.isEmpty) {
          return const Center(child: Text('No assignments available.'));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(12),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (context, index) {
            final item = items[index];

            return Card(
              child: ListTile(
                leading: const CircleAvatar(
                  child: Icon(Icons.assignment_outlined),
                ),
                title: Text(item['title']?.toString() ?? 'Assessment'),
                subtitle: Text(
                  [
                    item['type'],
                    if (item['due_at'] != null) 'Due ${item['due_at']}',
                  ].where((value) => value != null).join(' · '),
                ),
                trailing: FilledButton(
                  onPressed: () => _submit(item),
                  child: const Text('Open'),
                ),
              ),
            );
          },
        );
      },
    );
  }
}
