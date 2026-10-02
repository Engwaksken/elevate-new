import 'package:flutter/material.dart';

import '../services/api_service.dart';
import 'feedback.dart';

class CareerAiDialog extends StatefulWidget {
  const CareerAiDialog(
      {super.key,
      required this.resume,
      required this.documentId,
      required this.data});

  final bool resume;
  final int documentId;
  final Map<String, dynamic> data;

  @override
  State<CareerAiDialog> createState() => _CareerAiDialogState();
}

class _CareerAiDialogState extends State<CareerAiDialog> {
  final _form = GlobalKey<FormState>();
  final _job = TextEditingController();
  final _instructions = TextEditingController();
  String _action = 'improve';
  bool _busy = false;
  Map<String, dynamic>? _result;

  @override
  void dispose() {
    _job.dispose();
    _instructions.dispose();
    super.dispose();
  }

  Future<void> _generate() async {
    if (!_form.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _result = null;
    });
    try {
      final result = await ApiService.instance.assistCareerDocument(
          resume: widget.resume,
          id: widget.documentId,
          action: _action,
          data: widget.data,
          jobDescription: _action == 'tailor' ? _job.text.trim() : null,
          instructions: _instructions.text.trim());
      if (mounted) setState(() => _result = result);
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String _preview(Map<String, dynamic> draft) {
    if (!widget.resume) return draft['body']?.toString() ?? '';
    final parts = <String>[draft['professional_summary']?.toString() ?? ''];
    for (final key in ['experiences', 'education', 'skills', 'projects']) {
      parts.add('\n${key[0].toUpperCase()}${key.substring(1)}');
      for (final row in draft[key] as List? ?? []) {
        if (row is Map) {
          parts.add(row.entries
              .where((entry) =>
                  entry.value != null && entry.value.toString().isNotEmpty)
              .map((entry) =>
                  '${entry.key.replaceAll('_', ' ')}: ${entry.value}')
              .join('\n'));
        }
      }
    }
    return parts.join('\n\n');
  }

  @override
  Widget build(BuildContext context) {
    final draft = _result?['draft'];
    return AlertDialog(
      title: const Text('AI Assistant'),
      content: SizedBox(
          width: 500,
          child: SingleChildScrollView(
              child: Form(
                  key: _form,
                  child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                            'Review all suggested changes. AI drafts are applied to the editor only; save the document when you are satisfied.'),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<String>(
                            initialValue: _action,
                            isExpanded: true,
                            decoration:
                                const InputDecoration(labelText: 'Assistance'),
                            items: [
                              const DropdownMenuItem(
                                  value: 'improve',
                                  child: Text('Improve writing')),
                              const DropdownMenuItem(
                                  value: 'tailor',
                                  child: Text('Tailor to a job')),
                              if (widget.resume)
                                const DropdownMenuItem(
                                    value: 'ats',
                                    child: Text('ATS-readiness review')),
                            ],
                            onChanged: _busy
                                ? null
                                : (value) => setState(() {
                                      _action = value!;
                                      _result = null;
                                    })),
                        if (_action == 'tailor')
                          TextFormField(
                              controller: _job,
                              enabled: !_busy,
                              maxLines: 5,
                              maxLength: 15000,
                              decoration: const InputDecoration(
                                  labelText: 'Job description'),
                              validator: (value) =>
                                  (value?.trim().isEmpty ?? true)
                                      ? 'Required'
                                      : null),
                        TextFormField(
                            controller: _instructions,
                            enabled: !_busy,
                            maxLines: 3,
                            maxLength: 2000,
                            decoration: const InputDecoration(
                                labelText: 'Preferences (optional)',
                                hintText:
                                    'e.g. concise, professional, emphasise my existing skills')),
                        const SizedBox(height: 12),
                        FilledButton.icon(
                            onPressed: _busy ? null : _generate,
                            icon: const Icon(Icons.auto_awesome_outlined),
                            label: Text(_busy
                                ? 'Working…'
                                : _action == 'ats'
                                    ? 'Review ATS readiness'
                                    : 'Generate draft')),
                        if (_busy)
                          const Padding(
                              padding: EdgeInsets.only(top: 12),
                              child: LinearProgressIndicator()),
                        if (_result != null) ...[
                          const Divider(height: 32),
                          SelectableText(draft is Map
                              ? _preview(Map<String, dynamic>.from(draft))
                              : _result?['suggestion']?.toString() ?? ''),
                        ],
                      ])))),
      actions: [
        TextButton(
            onPressed: _busy ? null : () => Navigator.pop(context),
            child: const Text('Close')),
        if (draft is Map)
          FilledButton(
              onPressed: _busy
                  ? null
                  : () =>
                      Navigator.pop(context, Map<String, dynamic>.from(draft)),
              child: const Text('Apply draft'))
      ],
    );
  }
}
