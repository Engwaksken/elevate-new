import 'dart:io';

import 'package:flutter/material.dart';
import 'package:printing/printing.dart';
import 'package:share_plus/share_plus.dart';

import '../core/formatters.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../services/local_database.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

class CertificatesScreen extends StatefulWidget {
  const CertificatesScreen({super.key, this.courseId});
  final int? courseId;

  @override
  State<CertificatesScreen> createState() => _CertificatesScreenState();
}

class _CertificatesScreenState extends State<CertificatesScreen> {
  List<Map<String, dynamic>>? _items;
  Object? _error;
  bool _busy = false;
  bool _more = false;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool next = false}) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      if (!next) {
        List<Map<String, dynamic>> cached = [];
        try {
          cached = await LocalDatabase.instance
              .readCollection('offline_certificates');
        } catch (_) {
          // The server fetch still works if the local cache is unavailable.
        }
        if (cached.isNotEmpty && mounted) {
          setState(() => _items = cached);
        }
      }
      final page = next ? _page + 1 : 1;
      final data = await ApiService.instance
          .certificates(page: page, courseId: widget.courseId);
      final items = (data['data'] as List? ?? [])
          .whereType<Map>()
          .map(Map<String, dynamic>.from)
          .toList();
      if (mounted) {
        setState(() {
          _items = next ? [...?_items, ...items] : items;
          _page = page;
          _more = page < (data['last_page'] as num? ?? 1);
          _error = null;
        });
      }
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<String> _file(Map<String, dynamic> item) async {
    final id = _certificateId(item);
    final key = 'certificate_${item['type']}_$id';
    final downloads = DownloadService.instance;
    final cached =
        await downloads.localPath('${DownloadService.offlineCachePrefix}$key');
    if (cached != null) return cached;
    final existing = await downloads.localPath(key);
    if (existing != null) return existing;
    return downloads.download(
        key: key,
        apiPath: item['download_path']?.toString() ??
            '/certificates/${item['type']}/$id/download',
        title: item['title'].toString(),
        fileName: 'certificate-${item['number']}.pdf');
  }

  Future<void> _act(Map<String, dynamic> item, String action) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      if (action == 'link') {
        final link = await ApiService.instance.shareCertificate(
            type: item['type'].toString(), id: _certificateId(item));
        if (!mounted) return;
        final box = context.findRenderObject() as RenderBox?;
        await Share.share(
            '${item['title']}\n${link['url']}\nLink expires in seven days.',
            sharePositionOrigin:
                box == null ? null : box.localToGlobal(Offset.zero) & box.size);
      } else {
        final path = await _file(item);
        if (!mounted) return;
        if (action == 'preview') {
          await Navigator.of(context).push(MaterialPageRoute(
              builder: (_) => CertificatePreviewScreen(
                  path: path, title: item['title'].toString())));
        } else if (action == 'share') {
          final box = context.findRenderObject() as RenderBox?;
          await Share.shareXFiles([XFile(path, mimeType: 'application/pdf')],
              subject: item['title'].toString(),
              sharePositionOrigin: box == null
                  ? null
                  : box.localToGlobal(Offset.zero) & box.size);
        } else {
          showAppSnackBar(
              context, 'Certificate saved in Downloads for offline use.');
        }
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  int _certificateId(Map<String, dynamic> item) =>
      int.parse((item['_certificate_cache_id'] ?? item['id']).toString());

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('My certificates')),
        body: _items == null
            ? (_error != null
                ? ErrorState(error: _error!, onRetry: () => _load())
                : const LoadingSkeleton())
            : RefreshIndicator(
                onRefresh: () => _load(),
                child: ListView(
                    padding: const EdgeInsets.all(16),
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      if (_busy) const LinearProgressIndicator(),
                      const Text(
                          'Preview issued certificates, save PDFs to Downloads, or share a PDF or seven-day link.'),
                      if (_error != null)
                        TextButton(
                            onPressed: () => _load(),
                            child: const Text('Unable to refresh. Retry')),
                      if (_items!.isEmpty)
                        const Padding(
                            padding: EdgeInsets.all(32),
                            child: Text(
                                'No certificates yet. Certificates will appear here once issued.')),
                      for (final item in _items!)
                        CertificateCard(
                            item: item,
                            busy: _busy,
                            onAction: (action) => _act(item, action)),
                      if (_more)
                        TextButton(
                            onPressed: _busy ? null : () => _load(next: true),
                            child: const Text('Load more')),
                    ])),
      );
}

class CertificateCard extends StatelessWidget {
  const CertificateCard(
      {super.key,
      required this.item,
      required this.onAction,
      this.busy = false});
  final Map<String, dynamic> item;
  final ValueChanged<String> onAction;
  final bool busy;

  @override
  Widget build(BuildContext context) => Card(
      child: Padding(
          padding: const EdgeInsets.all(16),
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(item['title']?.toString() ?? 'Certificate',
                style: Theme.of(context).textTheme.titleMedium),
            Text(
                '${item['type'] == 'event' ? 'Event' : 'Course'} certificate · ${formatDateTime(item['issued_at'], withTime: false) ?? ''}'),
            SelectableText(item['number']?.toString() ?? ''),
            Wrap(spacing: 4, children: [
              for (final action in {
                'preview': 'Preview',
                'download': 'Download PDF',
                'share': 'Share PDF',
                'link': 'Share link'
              }.entries)
                TextButton(
                    onPressed: busy ? null : () => onAction(action.key),
                    child: Text(action.value)),
            ]),
          ])));
}

class CertificatePreviewScreen extends StatelessWidget {
  const CertificatePreviewScreen(
      {super.key, required this.path, required this.title});
  final String path;
  final String title;

  Future<void> _share(BuildContext context) async {
    try {
      final box = context.findRenderObject() as RenderBox?;
      await Share.shareXFiles([XFile(path, mimeType: 'application/pdf')],
          subject: title,
          sharePositionOrigin:
              box == null ? null : box.localToGlobal(Offset.zero) & box.size);
    } catch (error) {
      if (context.mounted) showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: Text(title), actions: [
        IconButton(
            tooltip: 'Share PDF',
            onPressed: () => _share(context),
            icon: const Icon(Icons.share_outlined))
      ]),
      body: PdfPreview(
          build: (_) => File(path).readAsBytes(),
          allowPrinting: false,
          allowSharing: false,
          canChangePageFormat: false,
          canChangeOrientation: false,
          canDebug: false));
}
