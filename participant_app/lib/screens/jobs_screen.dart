import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/app_config.dart';
import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/api_service.dart';
import '../services/local_database.dart';
import '../services/sync_service.dart';
import '../widgets/cached_data.dart';
import '../widgets/feedback.dart';
import '../widgets/search_field.dart';
import '../widgets/state_views.dart';

class JobsScreen extends StatefulWidget {
  const JobsScreen({super.key});

  @override
  State<JobsScreen> createState() => _JobsScreenState();
}

class _JobsScreenState extends State<JobsScreen>
    with CachedDataMixin<JobsScreen, List<Map<String, dynamic>>> {
  String _search = '';
  bool _savedOnly = false;

  @override
  Future<List<Map<String, dynamic>>> readCache() async {
    final jobs = await LocalDatabase.instance.readCollection('jobs');
    jobs.sort((a, b) => (b['published_at']?.toString() ?? '')
        .compareTo(a['published_at']?.toString() ?? ''));
    return jobs;
  }

  bool _isSaved(Map<String, dynamic> job) => isTruthy(job['is_saved']);

  Future<void> _toggle(Map<String, dynamic> job) async {
    final id = int.tryParse(job['id']?.toString() ?? '');
    if (id == null) return;

    final saved = _isSaved(job);

    Future<void> persist() async {
      job['is_saved'] = saved ? 0 : 1;
      await LocalDatabase.instance.cacheItem(
        collection: 'jobs',
        itemId: id.toString(),
        payload: job,
      );
      if (mounted) setState(() {});
    }

    try {
      if (await SyncService.instance.isOnline()) {
        saved
            ? await ApiService.instance.unsaveJob(id)
            : await ApiService.instance.saveJob(id);
        await persist();
        if (mounted) {
          showAppSnackBar(context, saved ? 'Removed from saved jobs.' : 'Job saved.');
        }
      } else {
        await SyncService.instance.queueAction(
          saved ? 'unsave_job' : 'save_job',
          {'job_id': id},
        );
        await persist();
        if (mounted) {
          showAppSnackBar(context, "Saved on this device. It will sync when you're back online.");
        }
      }
    } catch (error) {
      if (AppException.from(error).isRetryable) {
        await SyncService.instance.queueAction(
          saved ? 'unsave_job' : 'save_job',
          {'job_id': id},
        );
        await persist();
        if (mounted) {
          showAppSnackBar(context, "Saved on this device. It will sync when you're back online.");
        }
      } else if (mounted) {
        showErrorSnackBar(context, error);
      }
    }
  }

  String _meta(Map<String, dynamic> job) => [
        job['company_name'],
        job['location'],
        humanise(job['employment_type']),
        humanise(job['work_arrangement']),
      ].where((v) => v != null && v.toString().trim().isNotEmpty).join(' · ');

  void _showDetail(Map<String, dynamic> job) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final theme = Theme.of(context);
        final deadline = formatDateTime(job['application_deadline'], withTime: false);

        Widget section(String title, dynamic body) {
          final text = plainParagraphs(body?.toString()).join('\n\n');
          if (text.isEmpty) return const SizedBox.shrink();
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SectionHeader(title: title),
              SelectableText(text, style: theme.textTheme.bodyLarge),
            ],
          );
        }

        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.75,
          maxChildSize: 0.95,
          builder: (context, controller) => SafeArea(
            child: ListView(
              controller: controller,
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
              children: [
                Text(tidyTitle(job['title']?.toString(), fallback: 'Job'),
                    style: theme.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.xs),
                Text(_meta(job),
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
                if (deadline != null) ...[
                  const SizedBox(height: AppSpacing.sm),
                  InfoChip(label: 'Apply by $deadline', icon: Icons.event_outlined),
                ],
                section('About the role', job['description']),
                section('Responsibilities', job['responsibilities']),
                section('Requirements', job['requirements']),
                const SizedBox(height: AppSpacing.xl),
                FilledButton.icon(
                  onPressed: () => launchUrl(
                    Uri.parse('${AppConfig.siteUrl}/jobs/${job['id']}'),
                    mode: LaunchMode.externalApplication,
                  ),
                  icon: const Icon(Icons.open_in_new),
                  label: const Text('View and apply on the website'),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const LoadingSkeleton();
    if (loadError != null) return ErrorState(error: loadError!, onRetry: reload);

    final all = data ?? const [];
    final term = _search.toLowerCase();
    final jobs = all.where((job) {
      if (_savedOnly && !_isSaved(job)) return false;
      if (term.isEmpty) return true;
      return '${job['title']} ${_meta(job)} ${job['category'] ?? ''}'
          .toLowerCase()
          .contains(term);
    }).toList();

    return Column(
      children: [
        if (all.isNotEmpty) ...[
          SearchField(
            hint: 'Search jobs',
            onChanged: (value) => setState(() => _search = value.trim()),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.page),
            child: Align(
              alignment: Alignment.centerLeft,
              child: FilterChip(
                label: const Text('Saved only'),
                selected: _savedOnly,
                onSelected: (value) => setState(() => _savedOnly = value),
              ),
            ),
          ),
        ],
        Expanded(
          child: RefreshIndicator(
            onRefresh: refreshFromServer,
            child: jobs.isEmpty
                ? EmptyState(
                    icon: Icons.work_outline,
                    title: all.isEmpty ? 'No opportunities yet' : 'No matching jobs',
                    message: all.isEmpty
                        ? 'New jobs and opportunities will appear here. Pull down to refresh.'
                        : 'Try a different search or filter.',
                  )
                : ListView.separated(
                    padding: AppSpacing.listPadding,
                    itemCount: jobs.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
                    itemBuilder: (context, index) {
                      final job = jobs[index];
                      final saved = _isSaved(job);
                      final title = tidyTitle(job['title']?.toString(), fallback: 'Job');
                      final meta = _meta(job);
                      return Card(
                        child: ListTile(
                          onTap: () => _showDetail(job),
                          leading: const CircleAvatar(child: Icon(Icons.work_outline)),
                          title: Text(title),
                          subtitle: meta.isEmpty ? null : Text(meta),
                          trailing: IconButton(
                            tooltip: saved ? 'Remove $title from saved jobs' : 'Save $title',
                            isSelected: saved,
                            onPressed: () => _toggle(job),
                            icon: const Icon(Icons.bookmark_border),
                            selectedIcon: const Icon(Icons.bookmark),
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ),
      ],
    );
  }
}
