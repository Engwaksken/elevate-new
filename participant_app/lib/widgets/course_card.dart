import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import 'progress_widgets.dart';

/// Course summary card with an accessible progress indicator.
class CourseCard extends StatelessWidget {
  const CourseCard({
    super.key,
    required this.course,
    required this.onTap,
    this.progress,
  });

  final Map<String, dynamic> course;
  final double? progress;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final title = tidyTitle(course['title']?.toString(), fallback: 'Course');
    final percent = (progress ?? 0).clamp(0, 100).toDouble();
    final done = percent >= 100;

    final meta = [
      course['code'],
      humanise(course['delivery_mode']),
    ].where((v) => v != null && v.toString().trim().isNotEmpty).join(' · ');

    final progressLabel = done
        ? 'Completed'
        : percent == 0
            ? 'Not started'
            : '${percent.round()}% complete';

    return Card(
      child: InkWell(
        onTap: onTap,
        child: Semantics(
          button: true,
          label: '$title. $progressLabel',
          excludeSemantics: true,
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [scheme.primaryContainer, scheme.tertiaryContainer],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                  ),
                  child: Icon(
                    done ? Icons.verified_outlined : Icons.menu_book_outlined,
                    color: scheme.onPrimaryContainer,
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: theme.textTheme.titleMedium),
                      if (meta.isNotEmpty) ...[
                        const SizedBox(height: AppSpacing.xxs),
                        Text(
                          meta,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: scheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                      const SizedBox(height: AppSpacing.md),
                      ProgressBar(value: percent / 100),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        progressLabel,
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: scheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: AppSpacing.xs),
                Icon(Icons.chevron_right, color: scheme.onSurfaceVariant),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
