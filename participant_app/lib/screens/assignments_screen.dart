import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

class AssignmentsScreen extends StatefulWidget {
  const AssignmentsScreen({super.key});

  @override
  State<AssignmentsScreen> createState() => _AssignmentsScreenState();
}

class _AssignmentsScreenState extends State<AssignmentsScreen>
    with CachedDataMixin<AssignmentsScreen, List<Map<String, dynamic>>> {
  final Set<String> _downloading = {};

  @override
  Future<List<Map<String, dynamic>>> readCache() async {
    final items = await LocalDatabase.instance.readCollection('assignments');
    // Soonest due first; items without a due date last.
    items.sort((a, b) => (a['due_at']?.toString() ?? '9999')
        .compareTo(b['due_at']?.toString() ?? '9999'));
    return items;
  }

  /// Authenticated attachment path per the API contract. Legacy public
  /// /storage URLs are ignored.
  String? _attachmentPath(Map<String, dynamic> item) {
    final path = item['attachment_download_path']?.toString().trim() ?? '';
    if (path.isNotEmpty) return path;
    final url = item['attachment_url']?.toString().trim() ?? '';
    if (url.isEmpty || url.contains('/storage/')) return null;
    return url;
  }

  Future<void> _attachment(Map<String, dynamic> item) async {
    final id = item['id']?.toString() ?? '';
    final path = _attachmentPath(item);
    if (path == null) return;

    final key = DownloadService.assessmentKey(id);

    try {
      if (await DownloadService.instance.localPath(key) == null) {
        setState(() => _downloading.add(key));
        await DownloadService.instance.download(
          key: key,
          apiPath: path,
          title: tidyTitle(item['title']?.toString(), fallback: 'Attachment'),
          fileName: item['attachment_name']?.toString(),
        );
      }
      await DownloadService.instance.open(key);
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error, onRetry: () => _attachment(item));
    } finally {
      if (mounted) setState(() => _downloading.remove(key));
    }
  }

  Future<void> _submit(Map<String, dynamic> item) async {
    final message = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _SubmissionSheet(assignment: item),
    );
    if (message != null && mounted) showAppSnackBar(context, message);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Assignments')),
      body: SafeArea(top: false, child: _content(context)),
    );
  }

  Widget _content(BuildContext context) {
    if (loading) return const LoadingSkeleton(itemHeight: 120);
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final items = data ?? const [];
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: items.isEmpty
          ? const EmptyState(
              icon: Icons.assignment_outlined,
              title: 'No assignments yet',
              message: 'Assignments, quizzes and exams for your courses will appear here.',
            )
          : ListView.separated(
              padding: AppSpacing.listPadding,
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final item = items[index];
                final id = item['id']?.toString() ?? '';
                final due = DateTime.tryParse(item['due_at']?.toString() ?? '');
                final overdue = due != null && due.isBefore(DateTime.now());
                final course = item['course'];
                final hasAttachment = _attachmentPath(item) != null;
                final busy = _downloading.contains(DownloadService.assessmentKey(id));

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Wrap(
                          spacing: AppSpacing.sm,
                          runSpacing: AppSpacing.xs,
                          children: [
                            InfoChip(
                              label: humanise(item['type']).isEmpty
                                  ? 'Assignment'
                                  : humanise(item['type']),
                              icon: Icons.assignment_outlined,
                              emphasis: true,
                            ),
                            if (due != null)
                              InfoChip(
                                label: '${overdue ? 'Closed' : 'Due'} '
                                    '${formatDateTime(item['due_at'])}',
                                icon: overdue ? Icons.event_busy : Icons.event,
                              ),
                          ],
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          tidyTitle(item['title']?.toString(), fallback: 'Assignment'),
                          style: theme.textTheme.titleMedium,
                        ),
                        if (course is Map && course['title'] != null)
                          Text(
                            course['title'].toString(),
                            style: theme.textTheme.bodySmall
                                ?.copyWith(color: scheme.onSurfaceVariant),
                          ),
                        const SizedBox(height: AppSpacing.md),
                        Wrap(
                          spacing: AppSpacing.sm,
                          runSpacing: AppSpacing.sm,
                          children: [
                            FilledButton.icon(
                              onPressed: () => _submit(item),
                              icon: const Icon(Icons.upload_outlined),
                              label: const Text('Submit work'),
                            ),
                            if (hasAttachment)
                              OutlinedButton.icon(
                                onPressed: busy ? null : () => _attachment(item),
                                icon: busy
                                    ? const SizedBox.square(
                                        dimension: 18,
                                        child: CircularProgressIndicator(strokeWidth: 2),
                                      )
                                    : const Icon(Icons.attach_file),
                                label: const Text('Instructions file'),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }
}

/// Submission form. Pops with a confirmation message on success and shows
/// server validation errors (422) inline.
class _SubmissionSheet extends StatefulWidget {
  const _SubmissionSheet({required this.assignment});

  final Map<String, dynamic> assignment;

  @override
  State<_SubmissionSheet> createState() => _SubmissionSheetState();
}

class _SubmissionSheetState extends State<_SubmissionSheet> {
  final _text = TextEditingController();
  String? _filePath;
  bool _sending = false;
  AppException? _error;

  @override
  void dispose() {
    _text.dispose();
    super.dispose();
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(allowMultiple: false);
    final path = result?.files.single.path;
    if (path != null) setState(() => _filePath = path);
  }

  Future<void> _send() async {
    if (_text.text.trim().isEmpty && _filePath == null) {
      setState(() => _error = const AppException(
            AppErrorKind.validation,
            'Add a response or attach a file before submitting.',
          ));
      return;
    }

    final id = int.tryParse(widget.assignment['id']?.toString() ?? '');
    if (id == null) return;

    setState(() {
      _sending = true;
      _error = null;
    });

    Future<String> queue() async {
      await SyncService.instance.queueAssignmentSubmission(
        assessmentId: id,
        text: _text.text,
        localFilePath: _filePath,
      );
      return "Saved on this device. It will be submitted when you're back online.";
    }

    try {
      String message;
      if (await SyncService.instance.isOnline()) {
        try {
          await ApiService.instance.submitAssignment(
            assessmentId: id,
            text: _text.text,
            localFilePath: _filePath,
          );
          message = 'Your work has been submitted.';
        } catch (error) {
          final mapped = AppException.from(error);
          if (!mapped.isRetryable) rethrow;
          message = await queue();
        }
      } else {
        message = await queue();
      }
      if (mounted) Navigator.pop(context, message);
    } catch (error) {
      if (mounted) setState(() => _error = AppException.from(error));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final instructions =
        plainParagraphs(widget.assignment['instructions']?.toString()).join('\n\n');
    final textError = _error?.fieldError('submission_text');
    final fileError = _error?.fieldError('submission_file');
    final generalError =
        _error != null && textError == null && fileError == null ? _error!.message : null;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                tidyTitle(widget.assignment['title']?.toString(), fallback: 'Assignment'),
                style: theme.textTheme.titleLarge,
              ),
              if (instructions.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(instructions, style: theme.textTheme.bodyMedium),
              ],
              const SizedBox(height: AppSpacing.lg),
              TextField(
                controller: _text,
                enabled: !_sending,
                minLines: 4,
                maxLines: 8,
                keyboardType: TextInputType.multiline,
                textCapitalization: TextCapitalization.sentences,
                decoration: InputDecoration(
                  labelText: 'Your response',
                  alignLabelWithHint: true,
                  errorText: textError,
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              OutlinedButton.icon(
                onPressed: _sending ? null : _pickFile,
                icon: const Icon(Icons.attach_file),
                label: Text(
                  _filePath == null
                      ? 'Attach a file'
                      : _filePath!.split(RegExp(r'[\\/]')).last,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (fileError != null)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.xs),
                  child: Text(fileError,
                      style: TextStyle(color: theme.colorScheme.error)),
                ),
              if (generalError != null) ...[
                const SizedBox(height: AppSpacing.md),
                Text(generalError, style: TextStyle(color: theme.colorScheme.error)),
              ],
              const SizedBox(height: AppSpacing.lg),
              FilledButton.icon(
                onPressed: _sending ? null : _send,
                icon: _sending
                    ? const SizedBox.square(
                        dimension: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send),
                label: Text(_sending ? 'Submitting…' : 'Submit'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
