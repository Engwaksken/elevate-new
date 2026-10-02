import 'dart:io';

import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../services/api_service.dart';
import '../widgets/feedback.dart';

class CareerDocumentsScreen extends StatefulWidget {
  const CareerDocumentsScreen({super.key});

  @override
  State<CareerDocumentsScreen> createState() => _CareerDocumentsScreenState();
}

class _CareerDocumentsScreenState extends State<CareerDocumentsScreen> {
  Map<String, dynamic>? _data;
  Object? _error;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final data = await ApiService.instance.careerDocuments();
      if (mounted) {
        setState(() {
          _data = data;
          _error = null;
        });
      }
    } catch (error) {
      if (mounted) setState(() => _error = error);
    }
  }

  Future<void> _edit(bool resume, [Map<String, dynamic>? document]) async {
    final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
      builder: (_) => _DocumentEditor(
          resume: resume,
          document: document,
          templates: List<String>.from(_data?['templates'] ?? ['classic'])),
    ));
    if (saved == true) await _load();
  }

  Future<void> _action(
      bool resume, Map<String, dynamic> document, String action) async {
    if (_busy) return;
    final id = (document['id'] as num).toInt();
    if (action == 'delete') {
      final confirmed = await confirmDialog(context,
          title: 'Delete document?',
          message: 'This removes the document and its shared links.',
          confirmLabel: 'Delete',
          destructive: true);
      if (!confirmed || !mounted) return;
    }
    setState(() => _busy = true);
    try {
      final api = ApiService.instance;
      if (action == 'delete') {
        await api.deleteCareerDocument(resume: resume, id: id);
        await _load();
      } else if (action == 'share') {
        final link = await api.shareCareerDocument(resume: resume, id: id);
        if (!mounted) return;
        final box = context.findRenderObject() as RenderBox?;
        await Share.share(
            '${document['title']}\n${link['url']}\nLink expires in seven days.',
            sharePositionOrigin:
                box == null ? null : box.localToGlobal(Offset.zero) & box.size);
      } else {
        final directory = await getTemporaryDirectory();
        final file = File(
            '${directory.path}/career-${resume ? 'resume' : 'letter'}-$id.pdf');
        await api.downloadApiFile(
            path:
                '/career/${resume ? 'resumes' : 'cover-letters'}/$id/download',
            savePath: file.path);
        final result = await OpenFilex.open(file.path);
        if (result.type != ResultType.done && mounted) {
          showAppSnackBar(context, result.message);
        }
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _list(bool resume) {
    final items = (_data?[resume ? 'resumes' : 'cover_letters'] as List? ?? [])
        .map((item) => Map<String, dynamic>.from(item as Map))
        .toList();
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            Text(
                'Your documents are shared with the Laravel career centre. Internet access is required.',
                style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 12),
            FilledButton.icon(
                onPressed: _busy ? null : () => _edit(resume),
                icon: const Icon(Icons.add),
                label: Text(resume ? 'Create resume' : 'Create cover letter')),
            if (items.isEmpty)
              const Padding(
                  padding: EdgeInsets.all(32),
                  child: Text('No documents yet. Create your first one.')),
            for (final item in items)
              Card(
                  child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item['title'].toString(),
                                style: Theme.of(context).textTheme.titleMedium),
                            Text((resume
                                        ? item['template']
                                        : item['employer_name'])
                                    ?.toString() ??
                                'General'),
                            Wrap(spacing: 4, children: [
                              TextButton.icon(
                                  onPressed:
                                      _busy ? null : () => _edit(resume, item),
                                  icon: const Icon(Icons.edit_outlined),
                                  label: const Text('Edit')),
                              TextButton.icon(
                                  onPressed: _busy
                                      ? null
                                      : () => _action(resume, item, 'download'),
                                  icon:
                                      const Icon(Icons.picture_as_pdf_outlined),
                                  label: const Text('PDF')),
                              TextButton.icon(
                                  onPressed: _busy
                                      ? null
                                      : () => _action(resume, item, 'share'),
                                  icon: const Icon(Icons.share_outlined),
                                  label: const Text('Share')),
                              IconButton(
                                  tooltip: 'Delete',
                                  onPressed: _busy
                                      ? null
                                      : () => _action(resume, item, 'delete'),
                                  icon: const Icon(Icons.delete_outline)),
                            ]),
                          ]))),
          ]),
    );
  }

  @override
  Widget build(BuildContext context) => DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
            title: const Text('Career documents'),
            bottom: const TabBar(
                tabs: [Tab(text: 'Resumes'), Tab(text: 'Cover letters')])),
        body: _data == null
            ? (_error == null
                ? const Center(child: CircularProgressIndicator())
                : Center(
                    child: FilledButton(
                        onPressed: _load,
                        child: const Text('Unable to load. Retry'))))
            : Column(children: [
                if (_busy) const LinearProgressIndicator(),
                if (_error != null)
                  MaterialBanner(
                      content: const Text('Could not refresh documents.'),
                      actions: [
                        TextButton(onPressed: _load, child: const Text('Retry'))
                      ]),
                Expanded(
                    child: TabBarView(children: [_list(true), _list(false)])),
              ]),
      ));
}

class _DocumentEditor extends StatefulWidget {
  const _DocumentEditor(
      {required this.resume, required this.templates, this.document});
  final bool resume;
  final List<String> templates;
  final Map<String, dynamic>? document;

  @override
  State<_DocumentEditor> createState() => _DocumentEditorState();
}

class _DocumentEditorState extends State<_DocumentEditor> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _body;
  late final TextEditingController _employer;
  late final TextEditingController _job;
  late final TextEditingController _recipient;
  late String _template;
  late List<Map<String, dynamic>> _experiences;
  late List<Map<String, dynamic>> _education;
  late List<Map<String, dynamic>> _skills;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final data = widget.document ?? {};
    _title = TextEditingController(text: data['title']?.toString());
    _body = TextEditingController(
        text:
            data[widget.resume ? 'professional_summary' : 'body']?.toString());
    _employer = TextEditingController(text: data['employer_name']?.toString());
    _job = TextEditingController(text: data['job_title']?.toString());
    _recipient =
        TextEditingController(text: data['recipient_name']?.toString());
    _template = data['template']?.toString() ?? widget.templates.first;
    if (!widget.templates.contains(_template)) {
      _template = widget.templates.first;
    }
    List<Map<String, dynamic>> rows(String key) => (data[key] as List? ?? [])
        .map((row) => Map<String, dynamic>.from(row as Map))
        .toList();
    _experiences = rows('experiences');
    _education = rows('education');
    _skills = rows('skills');
  }

  @override
  void dispose() {
    for (final controller in [_title, _body, _employer, _job, _recipient]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ApiService.instance.saveCareerDocument(
          resume: widget.resume,
          id: (widget.document?['id'] as num?)?.toInt(),
          data: {
            'title': _title.text.trim(),
            if (widget.resume) ...{
              'template': _template,
              'professional_summary': _body.text.trim(),
              'experiences': _experiences,
              'education': _education,
              'skills': _skills,
            } else ...{
              'body': _body.text.trim(),
              'employer_name': _employer.text.trim(),
              'job_title': _job.text.trim(),
              'recipient_name': _recipient.text.trim(),
            },
          });
      if (mounted) Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _section(String name, List<Map<String, dynamic>> rows,
      Map<String, String> fields, Set<String> requiredFields,
      [int? index]) async {
    final original = index == null ? <String, dynamic>{} : rows[index];
    final result = await showDialog<Map<String, dynamic>>(
        context: context,
        builder: (_) => _SectionEditor(
              title: '${index == null ? 'Add' : 'Edit'} $name',
              original: original,
              fields: fields,
              requiredFields: requiredFields,
              experience: name == 'Experience',
            ));
    if (result != null && mounted) {
      setState(() {
        if (index == null) {
          rows.add(result);
        } else {
          rows[index] = result;
        }
      });
    }
  }

  Widget _sectionList(String name, List<Map<String, dynamic>> rows,
          Map<String, String> fields, Set<String> requiredFields) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
              child:
                  Text(name, style: Theme.of(context).textTheme.titleMedium)),
          TextButton.icon(
              onPressed: _saving
                  ? null
                  : () => _section(name, rows, fields, requiredFields),
              icon: const Icon(Icons.add),
              label: const Text('Add'))
        ]),
        for (var i = 0; i < rows.length; i++)
          ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(rows[i][fields.keys.first]?.toString() ?? ''),
              onTap: _saving
                  ? null
                  : () => _section(name, rows, fields, requiredFields, i),
              trailing: IconButton(
                  tooltip: 'Remove',
                  onPressed:
                      _saving ? null : () => setState(() => rows.removeAt(i)),
                  icon: const Icon(Icons.remove_circle_outline))),
      ]);

  Widget _field(TextEditingController controller, String label,
          {bool required = false, int lines = 1, int max = 190}) =>
      Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: TextFormField(
              controller: controller,
              enabled: !_saving,
              maxLines: lines,
              maxLength: max,
              decoration: InputDecoration(labelText: label),
              validator: (value) => required && (value?.trim().isEmpty ?? true)
                  ? 'Required'
                  : null));

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.resume ? 'Resume' : 'Cover letter')),
        body: Form(
            key: _form,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              _field(_title, 'Title', required: true),
              if (widget.resume) ...[
                DropdownButtonFormField<String>(
                    initialValue: _template,
                    decoration: const InputDecoration(labelText: 'Template'),
                    items: widget.templates
                        .map((value) =>
                            DropdownMenuItem(value: value, child: Text(value)))
                        .toList(),
                    onChanged: _saving
                        ? null
                        : (value) => setState(() => _template = value!)),
                const SizedBox(height: 16),
                _field(_body, 'Professional summary', lines: 5, max: 10000),
                _sectionList('Experience', _experiences, {
                  'job_title': 'Job title',
                  'organisation': 'Organisation',
                  'location': 'Location',
                  'start_date': 'Start date (YYYY-MM-DD)',
                  'end_date': 'End date (YYYY-MM-DD)',
                  'description': 'Description'
                }, {
                  'job_title',
                  'organisation'
                }),
                _sectionList('Education', _education, {
                  'institution': 'Institution',
                  'qualification': 'Qualification',
                  'field_of_study': 'Field of study',
                  'start_date': 'Start date (YYYY-MM-DD)',
                  'end_date': 'End date (YYYY-MM-DD)',
                  'description': 'Description'
                }, {
                  'institution',
                  'qualification'
                }),
                _sectionList('Skills', _skills,
                    {'skill': 'Skill', 'level': 'Level'}, {'skill'}),
              ] else ...[
                _field(_employer, 'Employer'),
                _field(_job, 'Job title'),
                _field(_recipient, 'Recipient'),
                _field(_body, 'Cover letter',
                    required: true, lines: 14, max: 50000),
              ],
              const SizedBox(height: 16),
              FilledButton.icon(
                  onPressed: _saving ? null : _save,
                  icon: const Icon(Icons.save_outlined),
                  label: Text(_saving ? 'Saving…' : 'Save document')),
            ])),
      );
}

class _SectionEditor extends StatefulWidget {
  const _SectionEditor(
      {required this.title,
      required this.original,
      required this.fields,
      required this.requiredFields,
      required this.experience});
  final String title;
  final Map<String, dynamic> original;
  final Map<String, String> fields;
  final Set<String> requiredFields;
  final bool experience;

  @override
  State<_SectionEditor> createState() => _SectionEditorState();
}

class _SectionEditorState extends State<_SectionEditor> {
  final _form = GlobalKey<FormState>();
  late final Map<String, TextEditingController> _controllers;
  late bool _current;

  @override
  void initState() {
    super.initState();
    _current = widget.original['is_current'] == true;
    _controllers = {
      for (final key in widget.fields.keys)
        key: TextEditingController(
            text: key.endsWith('_date')
                ? widget.original[key]?.toString().split('T').first
                : widget.original[key]?.toString())
    };
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  String? _validate(String key, String? value) {
    final text = value?.trim() ?? '';
    if (widget.requiredFields.contains(key) && text.isEmpty) return 'Required';
    if (key.endsWith('_date') && text.isNotEmpty) {
      final date = DateTime.tryParse(text);
      if (!RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(text) ||
          date == null ||
          date.toIso8601String().split('T').first != text) {
        return 'Use a valid date: YYYY-MM-DD';
      }
      final start =
          DateTime.tryParse(_controllers['start_date']?.text.trim() ?? '');
      if (key == 'end_date' && start != null && date.isBefore(start)) {
        return 'Must be on or after the start date';
      }
    }
    return null;
  }

  void _save() {
    if (!_form.currentState!.validate()) return;
    Navigator.pop(context, <String, dynamic>{
      ...widget.original,
      for (final entry in _controllers.entries)
        entry.key:
            entry.key.endsWith('_date') && entry.value.text.trim().isEmpty
                ? null
                : entry.value.text.trim(),
      if (widget.experience) 'is_current': _current,
      if (widget.experience && _current) 'end_date': null,
    });
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
        title: Text(widget.title),
        content: SizedBox(
            width: 420,
            child: SingleChildScrollView(
                child: Form(
                    key: _form,
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      for (final field in widget.fields.entries)
                        Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: TextFormField(
                              controller: _controllers[field.key],
                              maxLines: field.key == 'description' ? 3 : 1,
                              enabled: !(_current &&
                                  widget.experience &&
                                  field.key == 'end_date'),
                              maxLength: field.key == 'description'
                                  ? 10000
                                  : (field.key == 'skill'
                                      ? 100
                                      : field.key == 'level'
                                          ? 50
                                          : 190),
                              decoration: InputDecoration(
                                  labelText:
                                      '${field.value}${widget.requiredFields.contains(field.key) ? ' *' : ''}'),
                              validator: (value) => _current &&
                                      widget.experience &&
                                      field.key == 'end_date'
                                  ? null
                                  : _validate(field.key, value),
                            )),
                      if (widget.experience)
                        CheckboxListTile(
                            contentPadding: EdgeInsets.zero,
                            title: const Text('I currently work here'),
                            value: _current,
                            onChanged: (value) =>
                                setState(() => _current = value ?? false)),
                    ])))),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel')),
          FilledButton(onPressed: _save, child: const Text('Save'))
        ],
      );
}
