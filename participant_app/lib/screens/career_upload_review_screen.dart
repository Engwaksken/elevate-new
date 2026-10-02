import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../services/api_service.dart';
import '../widgets/feedback.dart';

class CareerUploadReviewScreen extends StatefulWidget {
  const CareerUploadReviewScreen(
      {super.key,
      required this.resume,
      required this.upload,
      required this.onImport});

  final bool resume;
  final Map<String, dynamic> upload;
  final Future<void> Function(Map<String, dynamic> draft, int uploadId)
      onImport;

  @override
  State<CareerUploadReviewScreen> createState() =>
      _CareerUploadReviewScreenState();
}

class _CareerUploadReviewScreenState extends State<CareerUploadReviewScreen>
    with WidgetsBindingObserver {
  late Map<String, dynamic> _upload;
  Timer? _poll;
  bool _loading = false;
  bool _foreground = true;
  Object? _error;
  int get _id => (_upload['id'] as num).toInt();
  bool get _pending => ['uploaded', 'processing'].contains(_upload['status']);

  @override
  void initState() {
    super.initState();
    _upload = widget.upload;
    WidgetsBinding.instance.addObserver(this);
    _refresh();
  }

  @override
  void dispose() {
    _poll?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _foreground = state == AppLifecycleState.resumed;
    _poll?.cancel();
    if (_foreground && _pending) _refresh();
  }

  void _schedule() {
    _poll?.cancel();
    if (_pending && _foreground) {
      _poll = Timer(const Duration(seconds: 5), _refresh);
    }
  }

  Future<void> _refresh() async {
    if (_loading) return;
    setState(() => _loading = true);
    try {
      final upload = await ApiService.instance
          .careerUpload(resume: widget.resume, id: _id);
      if (!mounted) return;
      setState(() {
        _upload = upload;
        _error = null;
      });
      _schedule();
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _retry() async {
    setState(() => _loading = true);
    try {
      final upload = await ApiService.instance
          .retryCareerUpload(resume: widget.resume, id: _id);
      if (!mounted) return;
      setState(() {
        _upload = upload;
        _error = null;
      });
      _schedule();
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _original() async {
    setState(() => _loading = true);
    try {
      final dir = await getTemporaryDirectory();
      final extension = (_upload['original_name']?.toString() ?? '')
          .split('.')
          .last
          .toLowerCase();
      final safeExtension =
          ['pdf', 'doc', 'docx'].contains(extension) ? extension : 'pdf';
      final file = File(
          '${dir.path}/career-upload-${widget.resume ? 'resume' : 'letter'}-$_id.$safeExtension');
      await ApiService.instance.downloadApiFile(
          path: _upload['download_path'].toString(), savePath: file.path);
      final result = await OpenFilex.open(file.path);
      if (result.type != ResultType.done && mounted) {
        showAppSnackBar(context, result.message);
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) {
        setState(() => _loading = false);
        _schedule();
      }
    }
  }

  Future<void> _import() async {
    _poll?.cancel();
    await widget.onImport(
        Map<String, dynamic>.from(_upload['draft'] as Map? ?? {}), _id);
    if (mounted) await _refresh();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Review uploaded document'), actions: [
          IconButton(
              tooltip: 'Refresh status',
              onPressed: _loading ? null : _refresh,
              icon: const Icon(Icons.refresh))
        ]),
        body: ListView(padding: const EdgeInsets.all(16), children: [
          if (_loading) const LinearProgressIndicator(),
          Text(_upload['original_name']?.toString() ?? 'Uploaded document',
              style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          Text('Analysis: ${_upload['status']}'),
          if (_error != null)
            MaterialBanner(
                content: const Text('Unable to refresh analysis status.'),
                actions: [
                  TextButton(
                      onPressed: _loading ? null : _refresh,
                      child: const Text('Retry'))
                ]),
          if (_pending)
            const Padding(
                padding: EdgeInsets.symmetric(vertical: 16),
                child: Text(
                    'Your document is being analysed. This screen checks for updates automatically. You can return later.')),
          if (_upload['status'] == 'failed') ...[
            Text(_upload['parsing_error']?.toString() ??
                'Analysis failed. Please retry or enter your details manually.'),
            FilledButton(
                onPressed: _loading ? null : _retry,
                child: const Text('Retry analysis')),
          ],
          OutlinedButton.icon(
              onPressed: _loading ? null : _original,
              icon: const Icon(Icons.file_open_outlined),
              label: const Text('Open original file')),
          if (_upload['status'] == 'ready') ...[
            const SizedBox(height: 16),
            const Text(
                'Review the extracted text below. Import it as an editable document, then use AI Assistant to improve it.'),
            if (_upload['text_truncated'] == true)
              const Text(
                  'Only the first part of this long document is shown. Open the original to review all content.'),
            const SizedBox(height: 12),
            SelectableText(_upload['extracted_text']?.toString() ?? ''),
            const SizedBox(height: 16),
            if (_upload['document_id'] == null)
              FilledButton.icon(
                  onPressed: _loading ? null : _import,
                  icon: const Icon(Icons.edit_outlined),
                  label: const Text('Review & import editable draft'))
            else
              const Text(
                  'This file has already been imported. Open the document from Resumes or Cover letters to edit it.'),
          ],
        ]),
      );
}
