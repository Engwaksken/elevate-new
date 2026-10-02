import 'dart:io';

import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../services/api_service.dart';
import '../widgets/feedback.dart';
import '../widgets/career_ai_dialog.dart';
import 'career_upload_review_screen.dart';

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

  Future<void> _edit(bool resume,
      [Map<String, dynamic>? document,
      int? uploadId,
      bool startWithAi = false]) async {
    final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
      builder: (_) => _DocumentEditor(
          resume: resume,
          document: document,
          uploadId: uploadId,
          startWithAi: startWithAi,
          templates: List<String>.from(_data?['templates'] ?? ['classic'])),
    ));
    if (saved == true) await _load();
  }

  Future<void> _review(bool resume, Map<String, dynamic> upload) async {
    await Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => CareerUploadReviewScreen(
            resume: resume,
            upload: upload,
            onImport: (draft, id) => _edit(resume, draft, id))));
    if (mounted) await _load();
  }

  Future<void> _upload(bool resume) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final result = await FilePicker.platform.pickFiles(
          type: FileType.custom,
          allowedExtensions: ['pdf', 'doc', 'docx'],
          allowMultiple: false);
      if (result == null || !mounted) return;
      final selected = result.files.single;
      if (selected.size > 10 * 1024 * 1024) {
        showAppSnackBar(context, 'Choose a file no larger than 10 MB.',
            error: true);
        return;
      }
      final path = selected.path;
      if (path == null) {
        showAppSnackBar(
            context, 'Unable to read this file. Please choose it again.',
            error: true);
        return;
      }
      final upload = await ApiService.instance
          .uploadCareerDocument(resume: resume, localPath: path);
      if (mounted) await _review(resume, upload);
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _deleteUpload(bool resume, Map<String, dynamic> upload) async {
    final confirmed = await confirmDialog(context,
        title: 'Delete uploaded file?',
        message:
            'The original file and extracted data will be removed. Imported documents are kept.',
        confirmLabel: 'Delete',
        destructive: true);
    if (!confirmed || !mounted) return;
    setState(() => _busy = true);
    try {
      await ApiService.instance.deleteCareerUpload(
          resume: resume, id: (upload['id'] as num).toInt());
      await _load();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
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
    final uploads =
        (_data?[resume ? 'resume_uploads' : 'cover_letter_uploads'] as List? ??
                [])
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
            const SizedBox(height: 8),
            OutlinedButton.icon(
                onPressed: _busy ? null : () => _upload(resume),
                icon: const Icon(Icons.upload_file_outlined),
                label: Text(resume
                    ? 'Upload existing resume'
                    : 'Upload existing cover letter')),
            const Text(
                'PDF, DOC or DOCX · Maximum 10 MB. Review extracted content before importing and using AI Assistant.'),
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
                                      : () => _edit(resume, item, null, true),
                                  icon: const Icon(Icons.auto_awesome_outlined),
                                  label: const Text('AI Assistant')),
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
            if (uploads.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text('Uploaded files',
                  style: Theme.of(context).textTheme.titleMedium),
              for (final upload in uploads)
                Card(
                    child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(upload['original_name']?.toString() ??
                                  'Uploaded document'),
                              Text(
                                  'Analysis: ${upload['status']}${upload['document_id'] != null ? ' · Imported' : ''}'),
                              Wrap(children: [
                                TextButton.icon(
                                    onPressed: _busy
                                        ? null
                                        : () => _review(resume, upload),
                                    icon: const Icon(Icons.fact_check_outlined),
                                    label: const Text('Review upload')),
                                if (!['uploaded', 'processing']
                                    .contains(upload['status']))
                                  IconButton(
                                      tooltip: 'Delete uploaded file',
                                      onPressed: _busy
                                          ? null
                                          : () => _deleteUpload(resume, upload),
                                      icon: const Icon(Icons.delete_outline)),
                              ]),
                            ]))),
            ],
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
      {required this.resume,
      required this.templates,
      this.document,
      this.uploadId,
      this.startWithAi = false});
  final bool resume;
  final List<String> templates;
  final Map<String, dynamic>? document;
  final int? uploadId;
  final bool startWithAi;

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
  late final TextEditingController _portfolioUrl;
  late String _template;
  late List<Map<String, dynamic>> _experiences;
  late List<Map<String, dynamic>> _education;
  late List<Map<String, dynamic>> _skills;
  late List<Map<String, dynamic>> _projects;
  late List<Map<String, dynamic>> _referees;
  late List<Map<String, dynamic>> _portfolioFiles;
  bool _saving = false;
  int? _documentId;

  @override
  void initState() {
    super.initState();
    final data = widget.document ?? {};
    _documentId = (data['id'] as num?)?.toInt();
    _title = TextEditingController(text: data['title']?.toString());
    _body = TextEditingController(
        text:
            data[widget.resume ? 'professional_summary' : 'body']?.toString());
    _employer = TextEditingController(text: data['employer_name']?.toString());
    _job = TextEditingController(text: data['job_title']?.toString());
    _recipient =
        TextEditingController(text: data['recipient_name']?.toString());
    _portfolioUrl =
        TextEditingController(text: data['portfolio_url']?.toString());
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
    _projects = rows('projects');
    _referees = rows('referees');
    _portfolioFiles = rows('portfolio_files');
    if (widget.startWithAi) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _ai();
      });
    }
  }

  @override
  void dispose() {
    for (final controller in [
      _title,
      _body,
      _employer,
      _job,
      _recipient,
      _portfolioUrl
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Map<String, dynamic> _fields() => {
        'title': _title.text.trim(),
        if (widget.resume) ...{
          'template': _template,
          'professional_summary': _body.text.trim(),
          'experiences': _experiences,
          'education': _education,
          'skills': _skills,
          'projects': _projects,
          'referees': _referees,
          'portfolio_url': _portfolioUrl.text.trim(),
        } else ...{
          'body': _body.text.trim(),
          'employer_name': _employer.text.trim(),
          'job_title': _job.text.trim(),
          'recipient_name': _recipient.text.trim(),
        },
      };

  Future<void> _ai() async {
    if (_documentId == null || _saving || !_form.currentState!.validate()) {
      return;
    }
    final draft = await showDialog<Map<String, dynamic>>(
        context: context,
        barrierDismissible: false,
        builder: (_) => CareerAiDialog(
            resume: widget.resume, documentId: _documentId!, data: _fields()));
    if (draft == null || !mounted) return;
    setState(() {
      _body.text =
          draft[widget.resume ? 'professional_summary' : 'body']?.toString() ??
              '';
      if (widget.resume) {
        List<Map<String, dynamic>> rows(String key) =>
            (draft[key] as List? ?? [])
                .map((row) => Map<String, dynamic>.from(row as Map))
                .toList();
        _experiences = rows('experiences');
        _education = rows('education');
        _skills = rows('skills');
        if (draft['projects'] is List) _projects = rows('projects');
        // Referee identities and contacts are never changed by AI suggestions.
      }
    });
    showAppSnackBar(context,
        'AI draft applied to the editor. Review and save your changes.');
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      if (_documentId == null && widget.uploadId != null) {
        final document = await ApiService.instance.importCareerUpload(
            resume: widget.resume, id: widget.uploadId!, data: _fields());
        if (mounted) {
          setState(() => _documentId = (document['id'] as num).toInt());
          showAppSnackBar(context,
              'Document imported. You can now edit it with AI Assistant.');
        }
      } else {
        await ApiService.instance.saveCareerDocument(
            resume: widget.resume, id: _documentId, data: _fields());
        if (mounted) Navigator.of(context).pop(true);
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _addPortfolioFile() async {
    if (_documentId == null) return;
    setState(() => _saving = true);
    try {
      final picked = await FilePicker.platform.pickFiles(
          type: FileType.custom,
          allowedExtensions: [
            'pdf',
            'doc',
            'docx',
            'jpg',
            'jpeg',
            'png',
            'webp',
            'zip',
            'txt'
          ]);
      if (picked == null || !mounted) return;
      final file = picked.files.single;
      if (file.size > 10 * 1024 * 1024 || file.path == null) {
        showAppSnackBar(context, 'Choose a readable file no larger than 10 MB.',
            error: true);
        return;
      }
      final attachment = await ApiService.instance
          .uploadPortfolioFile(_documentId!, file.path!);
      if (mounted) setState(() => _portfolioFiles.add(attachment));
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _portfolioAction(
      Map<String, dynamic> attachment, bool remove) async {
    if (_documentId == null) return;
    if (remove &&
        !await confirmDialog(context,
            title: 'Remove portfolio file?',
            message: 'This removes the attached file from your resume.',
            confirmLabel: 'Remove',
            destructive: true)) {
      return;
    }
    if (!mounted) return;
    setState(() => _saving = true);
    try {
      final id = (attachment['id'] as num).toInt();
      if (remove) {
        await ApiService.instance.deletePortfolioFile(_documentId!, id);
        if (mounted) {
          setState(
              () => _portfolioFiles.removeWhere((file) => file['id'] == id));
        }
      } else {
        final dir = await getTemporaryDirectory();
        final ext = attachment['original_name']
            .toString()
            .split('.')
            .last
            .toLowerCase();
        final safe = [
          'pdf',
          'doc',
          'docx',
          'jpg',
          'jpeg',
          'png',
          'webp',
          'zip',
          'txt'
        ].contains(ext)
            ? ext
            : 'bin';
        final path = '${dir.path}/portfolio-$_documentId-$id.$safe';
        await ApiService.instance.downloadApiFile(
            path: attachment['download_path'].toString(), savePath: path);
        final result = await OpenFilex.open(path);
        if (mounted && result.type != ResultType.done) {
          showAppSnackBar(context, result.message);
        }
      }
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
              if (_documentId != null)
                OutlinedButton.icon(
                    onPressed: _saving ? null : _ai,
                    icon: const Icon(Icons.auto_awesome_outlined),
                    label: const Text('AI Assistant'))
              else
                Padding(
                    padding: const EdgeInsets.only(bottom: 16),
                    child: Text(widget.uploadId != null
                        ? 'Review the extracted fields, then import this draft to use AI Assistant.'
                        : 'Save your document first to use AI Assistant.')),
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
                _field(_portfolioUrl, 'Portfolio URL', max: 2048),
                _sectionList('Projects', _projects, {
                  'name': 'Project name',
                  'description': 'Description',
                  'url': 'Project / portfolio URL',
                  'start_date': 'Start date (YYYY-MM-DD)',
                  'end_date': 'End date (YYYY-MM-DD)'
                }, {
                  'name'
                }),
                _sectionList('Referees', _referees, {
                  'name': 'Referee name',
                  'job_title': 'Job title',
                  'organisation': 'Organisation',
                  'email': 'Email',
                  'phone': 'Phone',
                  'relationship': 'Relationship'
                }, {
                  'name'
                }),
                const SizedBox(height: 12),
                Text('Portfolio files',
                    style: Theme.of(context).textTheme.titleMedium),
                for (final file in _portfolioFiles)
                  ListTile(
                      contentPadding: EdgeInsets.zero,
                      title:
                          Text(file['label']?.toString() ?? 'Portfolio file'),
                      subtitle: Text(file['original_name']?.toString() ?? ''),
                      onTap:
                          _saving ? null : () => _portfolioAction(file, false),
                      trailing: IconButton(
                          tooltip: 'Remove portfolio file',
                          onPressed: _saving
                              ? null
                              : () => _portfolioAction(file, true),
                          icon: const Icon(Icons.delete_outline))),
                OutlinedButton.icon(
                    onPressed: _saving || _documentId == null
                        ? null
                        : _addPortfolioFile,
                    icon: const Icon(Icons.attach_file),
                    label: const Text('Upload portfolio file')),
                if (_documentId == null)
                  const Text(
                      'Save or import the resume first to attach files.'),
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
                  label: Text(_saving
                      ? 'Saving…'
                      : (_documentId == null && widget.uploadId != null)
                          ? 'Import document'
                          : 'Save document')),
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
    if (key == 'url' && text.isNotEmpty) {
      final uri = Uri.tryParse(text);
      if (uri == null ||
          !['http', 'https'].contains(uri.scheme) ||
          uri.host.isEmpty) {
        return 'Enter a valid http(s) URL';
      }
    }
    if (key == 'email' &&
        text.isNotEmpty &&
        !RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$').hasMatch(text)) {
      return 'Enter a valid email address';
    }
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
                                  : field.key == 'url'
                                      ? 255
                                      : field.key == 'phone'
                                          ? 50
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
