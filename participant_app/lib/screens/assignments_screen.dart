import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../core/app_config.dart';
import '../core/assignment_info.dart';
import '../core/formatters.dart';
import '../core/learning_file.dart' show fileSizeLabel;
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/learning_file_tile.dart';
import '../widgets/state_views.dart';

/// Result of the submission sheet.
enum _SubmitOutcome { submitted, queued, overdue }

class AssignmentsScreen extends StatefulWidget {
  const AssignmentsScreen({super.key, this.embedded = false});

  /// True inside the home shell (no own Scaffold/AppBar).
  final bool embedded;

  @override
  State<AssignmentsScreen> createState() => _AssignmentsScreenState();
}

class _AssignmentsScreenState extends State<AssignmentsScreen>
    with CachedDataMixin<AssignmentsScreen, List<AssignmentInfo>> {

  @override
  Future<List<AssignmentInfo>> readCache() async {
    final db = LocalDatabase.instance;
    final rejected = {
      for (final r in await db.readCollection(SyncService.rejectedSubmissionsCollection))
        r['assessment_id']?.toString(),
    };
    final items = (await db.readCollection('assignments')).map((raw) {
      if (rejected.contains(raw['id']?.toString())) {
        raw['queued_submission_rejected'] = 'overdue';
      }
      return AssignmentInfo(raw);
    }).toList();
    // Offline copies of instructions files that are view-only now are
    // deleted from the device.
    await DownloadService.instance.purgeViewOnlyCopies(viewOnlyKeys: [
      for (final a in items)
        for (final file in a.attachments)
          if (file.viewOnly) a.attachmentDownloadKey(file),
    ]);
    // Overdue and soonest-due first; submitted work after; no due date last.
    items.sort((a, b) {
      final rank = a.sortRank.compareTo(b.sortRank);
      if (rank != 0) return rank;
      final ad = a.effectiveDue, bd = b.effectiveDue;
      if (ad == null && bd == null) return 0;
      if (ad == null) return 1;
      if (bd == null) return -1;
      return ad.compareTo(bd);
    });
    return items;
  }

  Future<void> _submit(AssignmentInfo a) async {
    // Client-side guard; the server still has the final say.
    if (!a.canSubmit) {
      showAppSnackBar(
        context,
        a.blockedByDueDate
            ? 'The due date has passed. You can ask your instructor for more time.'
            : "You've used all your attempts for this assignment.",
      );
      return;
    }
    final outcome = await showModalBottomSheet<_SubmitOutcome>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _SubmissionSheet(assignment: a),
    );
    if (!mounted || outcome == null) return;
    switch (outcome) {
      case _SubmitOutcome.submitted:
        showAppSnackBar(context, 'Your work has been submitted. Well done!');
        await SyncService.instance.syncNow().catchError((_) {});
      case _SubmitOutcome.queued:
        showAppSnackBar(
          context,
          "Saved on this device. It will be submitted when you're back online.",
        );
      case _SubmitOutcome.overdue:
        final id = a.id;
        if (id != null) await SyncService.instance.markAssignmentOverdue(id);
        if (mounted) {
          showAppSnackBar(
            context,
            'The due date has passed. You can ask your instructor for more time.',
          );
        }
    }
  }

  Future<void> _requestExtension(AssignmentInfo a) async {
    final message = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      builder: (_) => ExtensionRequestSheet(assignment: a),
    );
    if (message != null && mounted) showAppSnackBar(context, message);
  }

  @override
  Widget build(BuildContext context) {
    final body = SafeArea(top: false, child: _content(context));
    if (widget.embedded) return body;
    return Scaffold(appBar: AppBar(title: const Text('Assignments')), body: body);
  }

  Widget _content(BuildContext context) {
    if (loading) return const LoadingSkeleton(itemHeight: 120);
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final items = data ?? const <AssignmentInfo>[];
    final counts = AssignmentCounts.of(items);

    return RefreshIndicator(
      onRefresh: refreshFromServer,
      child: items.isEmpty
          ? const EmptyState(
              icon: Icons.assignment_outlined,
              title: 'No assignments yet',
              message: 'Assignments, quizzes and exams for your courses will appear '
                  "here. You're all caught up for now!",
            )
          : ListView.separated(
              padding: AppSpacing.listPadding,
              itemCount: items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                if (index == 0) return _CountsHeader(counts: counts);
                final a = items[index - 1];
                return AssignmentCard(
                  assignment: a,
                  onSubmit: () => _submit(a),
                  onRequestExtension: () => _requestExtension(a),
                );
              },
            ),
    );
  }
}

class _CountsHeader extends StatelessWidget {
  const _CountsHeader({required this.counts});

  final AssignmentCounts counts;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    Widget cell(String value, String label, Color color) => Expanded(
          child: Column(
            children: [
              Text(value, style: theme.textTheme.titleLarge?.copyWith(color: color)),
              Text(
                label,
                textAlign: TextAlign.center,
                style: theme.textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ],
          ),
        );

    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.sm),
      child: Card(
        child: Semantics(
          container: true,
          label: '${counts.submitted} of ${counts.total} submitted, '
              '${counts.overdue} overdue, ${counts.graded} graded',
          excludeSemantics: true,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                cell('${counts.submitted}/${counts.total}', 'Submitted', scheme.primary),
                cell('${counts.overdue}', 'Overdue', counts.overdue > 0 ? scheme.error : scheme.primary),
                cell('${counts.graded}', 'Graded', scheme.primary),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// One assignment with its due date, status chips and actions.
class AssignmentCard extends StatelessWidget {
  const AssignmentCard({
    super.key,
    required this.assignment,
    required this.onSubmit,
    required this.onRequestExtension,
  });

  final AssignmentInfo assignment;
  final VoidCallback onSubmit;
  final VoidCallback onRequestExtension;

  static (PillTone, IconData) statusStyle(AssignmentStatus s) => switch (s) {
        AssignmentStatus.notSubmitted => (PillTone.neutral, Icons.radio_button_unchecked),
        AssignmentStatus.submitted => (PillTone.brand, Icons.upload_file_outlined),
        AssignmentStatus.graded => (PillTone.success, Icons.grade_outlined),
        AssignmentStatus.overdue => (PillTone.danger, Icons.event_busy_outlined),
      };

  static (PillTone, IconData) extensionStyle(ExtensionStatus s) => switch (s) {
        ExtensionStatus.pending => (PillTone.warning, Icons.hourglass_top_rounded),
        ExtensionStatus.approved => (PillTone.success, Icons.more_time_rounded),
        ExtensionStatus.rejected => (PillTone.rose, Icons.do_not_disturb_on_outlined),
        ExtensionStatus.none => (PillTone.neutral, Icons.more_time_rounded),
      };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final a = assignment;
    final (tone, icon) = statusStyle(a.status);
    final due = a.effectiveDue;
    final relative = a.relativeDue;
    final ext = a.extensionRequest;
    final attemptsLeft = a.attemptsRemaining;
    final attachments = a.attachments;
    final submitted = a.submittedFiles;

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
                StatusPill(label: a.statusLabel, tone: tone, icon: icon),
                if (a.extensionLabel != null)
                  StatusPill(
                    label: a.extensionLabel!,
                    tone: extensionStyle(a.extensionStatus).$1,
                    icon: extensionStyle(a.extensionStatus).$2,
                  ),
                InfoChip(label: a.typeLabel, icon: Icons.assignment_outlined),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(a.title, style: theme.textTheme.titleMedium),
            if (a.courseTitle != null)
              Text(
                a.courseTitle!,
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            if (due != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    a.status == AssignmentStatus.overdue ? Icons.event_busy : Icons.event,
                    size: 18,
                    color: a.status == AssignmentStatus.overdue ? scheme.error : scheme.secondary,
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text.rich(
                      TextSpan(children: [
                        if (relative != null)
                          TextSpan(
                            text: '$relative\n',
                            style: theme.textTheme.labelLarge?.copyWith(
                              color: a.status == AssignmentStatus.overdue
                                  ? scheme.error
                                  : scheme.onSurface,
                            ),
                          ),
                        TextSpan(
                          text: '${a.dueWasExtended ? 'New due date' : 'Due'} '
                              '${formatDateTime(due.toIso8601String())}',
                          style: theme.textTheme.bodySmall
                              ?.copyWith(color: scheme.onSurfaceVariant),
                        ),
                      ]),
                    ),
                  ),
                ],
              ),
            ],
            if (a.submissionsCount > 0 || attemptsLeft != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                [
                  if (a.submissionsCount > 0)
                    'Submitted ${a.submissionsCount} time${a.submissionsCount == 1 ? '' : 's'}',
                  if (attemptsLeft != null)
                    '$attemptsLeft attempt${attemptsLeft == 1 ? '' : 's'} left',
                ].join(' · '),
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ],
            if (a.queuedSubmissionRejected && !a.canSubmit) ...[
              const SizedBox(height: AppSpacing.md),
              const NoticeCard(
                icon: Icons.cloud_off_outlined,
                tone: PillTone.warning,
                message: "The work you saved offline couldn't be sent because the "
                    'due date passed before your phone reconnected.',
              ),
            ],
            if (ext != null && a.extensionStatus != ExtensionStatus.none) ...[
              const SizedBox(height: AppSpacing.md),
              _ExtensionState(assignment: a),
            ],
            if (attachments.isNotEmpty)
              _FilesSection(
                title: attachments.length == 1 ? 'Instructions file' : 'Instructions files',
                children: [
                  for (final file in attachments)
                    LearningFileTile(
                      key: ValueKey('attachment-${a.attachmentDownloadKey(file)}'),
                      file: file,
                      downloadKey: a.attachmentDownloadKey(file),
                      offlineCacheKey: a.id == null
                          ? null
                          : DownloadService.assignmentCacheKey(a.id!, file.id),
                      websiteUrl: a.id == null
                          ? null
                          : '${AppConfig.siteUrl}/learning/assessments/${a.id}',
                      compact: true,
                    ),
                ],
              ),
            if (submitted.isNotEmpty)
              _FilesSection(
                title: 'Your submitted files',
                children: [
                  for (final file in submitted)
                    LearningFileTile(
                      key: ValueKey('submitted-${file.id}'),
                      file: file,
                      downloadKey: DownloadService.submissionFileKey(file.id),
                      compact: true,
                    ),
                ],
              ),
            const SizedBox(height: AppSpacing.md),
            if (a.canSubmit)
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  FilledButton.icon(
                    onPressed: onSubmit,
                    icon: const Icon(Icons.upload_outlined),
                    label: Text(a.hasSubmitted ? 'Submit again' : 'Submit work'),
                  ),
                  if (a.canRequestExtension)
                    TextButton.icon(
                      onPressed: onRequestExtension,
                      icon: const Icon(Icons.more_time_rounded),
                      label: const Text('Need more time?'),
                    ),
                ],
              )
            else ...[
              if (a.blockedByDueDate && a.hasSubmitted)
                // is_overdue is also true after she submitted: celebrate the
                // work that's in, and only offer more time for another try.
                NoticeCard(
                  icon: Icons.task_alt_rounded,
                  tone: PillTone.success,
                  message: 'Your work is in. The due date has passed, so no more '
                      'attempts can be sent.',
                  actions: [
                    if (a.canRequestExtension)
                      TextButton.icon(
                        onPressed: onRequestExtension,
                        icon: const Icon(Icons.more_time_rounded),
                        label: const Text('Request extension'),
                      ),
                  ],
                )
              else if (a.blockedByDueDate && a.extensionStatus != ExtensionStatus.pending)
                OverdueCard(
                  canRequest: a.canRequestExtension,
                  onRequestExtension: onRequestExtension,
                )
              else if (!a.blockedByDueDate && a.attemptsUsedUp)
                const NoticeCard(
                  icon: Icons.task_alt_rounded,
                  tone: PillTone.success,
                  message: "You've used all your attempts for this assignment. Great effort!",
                ),
            ],
          ],
        ),
      ),
    );
  }

}

/// "Instructions files" / "Your submitted files" inside an assignment card.
class _FilesSection extends StatelessWidget {
  const _FilesSection({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Semantics(
            header: true,
            child: Text(title, style: theme.textTheme.labelLarge),
          ),
          ...children,
        ],
      ),
    );
  }
}

/// Shown instead of the submit button once the due date has passed.
class OverdueCard extends StatelessWidget {
  const OverdueCard({
    super.key,
    required this.canRequest,
    required this.onRequestExtension,
  });

  final bool canRequest;
  final VoidCallback onRequestExtension;

  @override
  Widget build(BuildContext context) {
    return NoticeCard(
      icon: Icons.event_busy_outlined,
      tone: PillTone.danger,
      title: 'Submissions are closed',
      message: 'The due date has passed. You can ask your instructor for more time.',
      actions: [
        if (canRequest)
          FilledButton.icon(
            onPressed: onRequestExtension,
            icon: const Icon(Icons.more_time_rounded),
            label: const Text('Request extension'),
          ),
      ],
    );
  }
}

class _ExtensionState extends StatelessWidget {
  const _ExtensionState({required this.assignment});

  final AssignmentInfo assignment;

  @override
  Widget build(BuildContext context) {
    final ext = assignment.extensionRequest ?? const {};
    final requested = formatDateTime(ext['requested_due_at'], withTime: false);
    final approved = formatDateTime(ext['approved_due_at']);
    final note = ext['reviewer_note']?.toString().trim() ?? '';

    switch (assignment.extensionStatus) {
      case ExtensionStatus.pending:
        return NoticeCard(
          icon: Icons.hourglass_top_rounded,
          tone: PillTone.warning,
          title: 'Extension request sent',
          message: 'Your instructor will review it soon. '
              '${requested != null ? 'You asked for time until $requested. ' : ''}'
              "We'll let you know as soon as they reply.",
        );
      case ExtensionStatus.approved:
        return NoticeCard(
          icon: Icons.celebration_outlined,
          tone: PillTone.success,
          title: 'Extension approved',
          message: [
            if (approved != null) 'Your new due date is $approved.',
            if (note.isNotEmpty) 'Note from your instructor: $note',
          ].join(' ').ifEmpty('You have more time for this assignment.'),
        );
      case ExtensionStatus.rejected:
        return NoticeCard(
          icon: Icons.info_outline,
          tone: PillTone.rose,
          title: "Your instructor couldn't approve this request",
          message: note.isNotEmpty
              ? 'Note from your instructor: $note'
              : 'You can reach out to your instructor if you have questions.',
        );
      case ExtensionStatus.none:
        return const SizedBox.shrink();
    }
  }
}

extension on String {
  String ifEmpty(String fallback) => isEmpty ? fallback : this;
}

/// Sheet to ask the instructor for more time (POST extension-requests).
/// Pops with a confirmation message on success.
class ExtensionRequestSheet extends StatefulWidget {
  const ExtensionRequestSheet({super.key, required this.assignment});

  final AssignmentInfo assignment;

  @override
  State<ExtensionRequestSheet> createState() => _ExtensionRequestSheetState();
}

class _ExtensionRequestSheetState extends State<ExtensionRequestSheet> {
  final _formKey = GlobalKey<FormState>();
  final _reason = TextEditingController();
  DateTime? _requested;
  bool _sending = false;
  AppException? _error;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final first = DateTime(now.year, now.month, now.day + 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: _requested ?? first,
      firstDate: first,
      lastDate: DateTime(now.year, now.month + 6, now.day),
      helpText: 'Ask for time until',
    );
    // End of the chosen day, in her local time.
    if (picked != null) {
      setState(() => _requested = DateTime(picked.year, picked.month, picked.day, 23, 59));
    }
  }

  static String _messageFor(AppException e) => switch (e.code) {
        'extension_pending' => 'You already have a request waiting for your instructor.',
        'extension_not_needed' =>
          'Extensions can be requested when an assignment is overdue or due within 48 hours.',
        'max_attempts_reached' => "You've used all your attempts for this assignment.",
        _ => e.message,
      };

  Future<void> _send() async {
    FocusScope.of(context).unfocus();
    setState(() => _error = null);
    if (!_formKey.currentState!.validate()) return;
    final id = widget.assignment.id;
    if (id == null) return;

    if (!await SyncService.instance.isOnline()) {
      setState(() => _error = const AppException(
            AppErrorKind.offline,
            "You're offline. Connect to the internet to send your request.",
          ));
      return;
    }

    setState(() => _sending = true);
    try {
      final response = await ApiService.instance.requestExtension(
        assessmentId: id,
        reason: _reason.text,
        requestedDueAt: _requested,
      );
      final request = response['extension_request'];
      final item = Map<String, dynamic>.from(widget.assignment.raw);
      if (request is Map) item['extension_request'] = Map<String, dynamic>.from(request);
      item['can_request_extension'] = false;
      await LocalDatabase.instance.cacheItem(
        collection: 'assignments',
        itemId: id.toString(),
        payload: item,
      );
      SyncService.instance.notifyLocalChange();
      if (mounted) {
        Navigator.pop(
          context,
          "Your request has been sent. We'll let you know when your instructor replies.",
        );
      }
    } catch (error) {
      if (mounted) setState(() => _error = AppException.from(error));
      WidgetsBinding.instance.addPostFrameCallback((_) => _formKey.currentState?.validate());
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final reasonError = _error?.fieldError('reason');
    final dateError = _error?.fieldError('requested_due_at');
    final general =
        _error != null && reasonError == null && dateError == null ? _messageFor(_error!) : null;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Semantics(
                  header: true,
                  child: Text('Ask for more time', style: theme.textTheme.titleLarge),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  widget.assignment.title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Life happens. Tell your instructor what got in the way, and they will do their best to help.',
                  style: theme.textTheme.bodyMedium,
                ),
                const SizedBox(height: AppSpacing.lg),
                TextFormField(
                  controller: _reason,
                  enabled: !_sending,
                  minLines: 4,
                  maxLines: 8,
                  maxLength: 1000,
                  keyboardType: TextInputType.multiline,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: const InputDecoration(
                    labelText: 'Reason',
                    hintText: 'For example: I was unwell last week and could not finish it.',
                    alignLabelWithHint: true,
                  ),
                  validator: (v) => reasonError ?? validateExtensionReason(v),
                ),
                const SizedBox(height: AppSpacing.sm),
                OutlinedButton.icon(
                  onPressed: _sending ? null : _pickDate,
                  icon: const Icon(Icons.calendar_month_outlined),
                  label: Text(
                    _requested == null
                        ? 'Suggest a new due date (optional)'
                        : 'Until ${formatDateTime(_requested!.toIso8601String(), withTime: false)}',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (_requested != null)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton(
                      onPressed: _sending ? null : () => setState(() => _requested = null),
                      child: const Text('Clear date'),
                    ),
                  ),
                if (dateError != null)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text(dateError, style: TextStyle(color: theme.colorScheme.error)),
                  ),
                if (general != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Semantics(
                    liveRegion: true,
                    child: NoticeCard(
                      icon: Icons.error_outline,
                      tone: PillTone.danger,
                      message: general,
                    ),
                  ),
                ],
                const SizedBox(height: AppSpacing.lg),
                FilledButton.icon(
                  onPressed: _sending ? null : _send,
                  icon: _sending
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.send_rounded),
                  label: Text(_sending ? 'Sending…' : 'Send request'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Submission form. Pops with the outcome on success and shows server
/// validation errors (422) inline.
class _SubmissionSheet extends StatefulWidget {
  const _SubmissionSheet({required this.assignment});

  final AssignmentInfo assignment;

  @override
  State<_SubmissionSheet> createState() => _SubmissionSheetState();
}

class _SubmissionSheetState extends State<_SubmissionSheet> {
  final _text = TextEditingController();
  final List<PlatformFile> _files = [];
  bool _sending = false;
  AppException? _error;

  /// Server limits (config/elearning.php): 10 files, 50 MB each.
  static const int maxFiles = 10;
  static const int maxFileBytes = 50 * 1024 * 1024;

  @override
  void dispose() {
    _text.dispose();
    super.dispose();
  }

  Future<void> _pickFiles() async {
    final result = await FilePicker.platform.pickFiles(allowMultiple: true);
    if (result == null || !mounted) return;

    final known = {for (final f in _files) f.path};
    final picked =
        result.files.where((f) => f.path != null && !known.contains(f.path)).toList();
    final tooBig = [for (final f in picked) if (f.size > maxFileBytes) f.name];
    final accepted = [for (final f in picked) if (f.size <= maxFileBytes) f];
    final room = maxFiles - _files.length;

    setState(() {
      _files.addAll(accepted.take(room < 0 ? 0 : room));
      _error = null;
    });

    final notes = [
      if (tooBig.isNotEmpty)
        '${tooBig.join(', ')} ${tooBig.length == 1 ? 'is' : 'are'} larger than 50 MB.',
      if (accepted.length > room) 'You can attach up to $maxFiles files.',
    ];
    if (notes.isNotEmpty) showAppSnackBar(context, notes.join(' '), error: true);
  }

  void _removeFile(int index) => setState(() {
        _files.removeAt(index);
        // Server errors point at file positions, which just changed.
        _error = null;
      });

  List<String> get _paths => [for (final f in _files) f.path!];

  Future<void> _send() async {
    if (_text.text.trim().isEmpty && _files.isEmpty) {
      setState(() => _error = const AppException(
            AppErrorKind.validation,
            'Add a response or attach a file before submitting.',
          ));
      return;
    }

    final id = widget.assignment.id;
    if (id == null) return;

    setState(() {
      _sending = true;
      _error = null;
    });

    Future<_SubmitOutcome> queue() async {
      await SyncService.instance.queueAssignmentSubmission(
        assessmentId: id,
        text: _text.text,
        localFilePaths: _paths,
      );
      return _SubmitOutcome.queued;
    }

    try {
      _SubmitOutcome outcome;
      if (await SyncService.instance.isOnline()) {
        try {
          await ApiService.instance.submitAssignment(
            assessmentId: id,
            text: _text.text,
            localFilePaths: _paths,
          );
          outcome = _SubmitOutcome.submitted;
        } catch (error) {
          final mapped = AppException.from(error);
          if (mapped.isOverdue) {
            outcome = _SubmitOutcome.overdue;
          } else if (!mapped.isRetryable) {
            rethrow;
          } else {
            outcome = await queue();
          }
        }
      } else {
        outcome = await queue();
      }
      if (mounted) Navigator.pop(context, outcome);
    } catch (error) {
      if (mounted) setState(() => _error = AppException.from(error));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final instructions =
        plainParagraphs(widget.assignment.raw['instructions']?.toString()).join('\n\n');
    final errors = SubmissionFileErrors.of(_error, fileCount: _files.length);
    final relative = widget.assignment.relativeDue;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(widget.assignment.title, style: theme.textTheme.titleLarge),
              if (relative != null)
                Text(
                  relative,
                  style: theme.textTheme.bodyMedium?.copyWith(color: scheme.secondary),
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
                  errorText: errors.text,
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              for (var i = 0; i < _files.length; i++)
                PickedFileRow(
                  name: _files[i].name,
                  sizeBytes: _files[i].size,
                  error: errors.perFile[i],
                  onRemove: _sending ? null : () => _removeFile(i),
                ),
              OutlinedButton.icon(
                onPressed: _sending || _files.length >= maxFiles ? null : _pickFiles,
                icon: const Icon(Icons.attach_file),
                label: Text(_files.isEmpty ? 'Attach files' : 'Add more files'),
              ),
              Padding(
                padding: const EdgeInsets.only(top: AppSpacing.xs),
                child: Text(
                  'Up to $maxFiles files, 50 MB each '
                  '(${_files.length} of $maxFiles attached)',
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ),
              if (errors.files != null)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.xs),
                  child: Text(errors.files!, style: TextStyle(color: scheme.error)),
                ),
              if (errors.general != null) ...[
                const SizedBox(height: AppSpacing.md),
                Semantics(
                  liveRegion: true,
                  child: Text(errors.general!, style: TextStyle(color: scheme.error)),
                ),
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

/// Server (422) errors of a submission, split by where they are shown.
class SubmissionFileErrors {
  const SubmissionFileErrors({
    this.text,
    this.files,
    this.perFile = const [],
    this.general,
  });

  /// [fileCount] picked files map to `submission_files.N`; a single file
  /// is sent as the legacy `submission_file`.
  factory SubmissionFileErrors.of(AppException? error, {required int fileCount}) {
    if (error == null) return SubmissionFileErrors(perFile: List.filled(fileCount, null));

    final text = error.fieldError('submission_text');
    final files = error.fieldError('submission_files');
    final perFile = [
      for (var i = 0; i < fileCount; i++)
        error.fieldError('submission_files.$i') ??
            (fileCount == 1 ? error.fieldError('submission_file') : null),
    ];
    final shown = text != null || files != null || perFile.any((e) => e != null);
    String? general;
    if (!shown) {
      // e.g. an error for a file that has since been removed from the list.
      general = error.fieldErrors.entries
              .where((e) => e.key.startsWith('submission_file'))
              .map((e) => e.value)
              .firstOrNull ??
          error.message;
    }
    return SubmissionFileErrors(text: text, files: files, perFile: perFile, general: general);
  }

  final String? text;
  final String? files;
  final List<String?> perFile;
  final String? general;
}

/// One picked file in the submission sheet, with its server error and a
/// remove (×) button.
class PickedFileRow extends StatelessWidget {
  const PickedFileRow({
    super.key,
    required this.name,
    required this.onRemove,
    this.sizeBytes,
    this.error,
  });

  final String name;
  final int? sizeBytes;
  final VoidCallback? onRemove;
  final String? error;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final size = fileSizeLabel(sizeBytes);

    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.md),
            child: Icon(
              error != null ? Icons.error_outline : Icons.insert_drive_file_outlined,
              color: error != null ? scheme.error : scheme.primary,
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: theme.textTheme.bodyMedium),
                  if (size != null)
                    Text(
                      size,
                      style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                    ),
                  if (error != null)
                    Text(error!, style: theme.textTheme.bodySmall?.copyWith(color: scheme.error)),
                ],
              ),
            ),
          ),
          IconButton(
            tooltip: 'Remove $name',
            onPressed: onRemove,
            icon: const Icon(Icons.close),
          ),
        ],
      ),
    );
  }
}
