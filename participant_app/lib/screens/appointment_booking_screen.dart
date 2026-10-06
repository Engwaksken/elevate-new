import 'package:flutter/material.dart';

import '../core/appointment_info.dart';
import '../core/network/app_exception.dart';
import '../core/theme/app_theme.dart';
import '../services/appointments_api.dart';
import '../services/sync_service.dart';
import '../widgets/feedback.dart';
import '../widgets/state_views.dart';

/// "Book an appointment" form. Pops the created [AppointmentInfo].
///
/// Client-side checks mirror the server (future time, max days ahead, no
/// overlap with own open appointments, open-request limit); the server is
/// still the authority and its 422 messages are shown next to the fields.
class AppointmentBookingScreen extends StatefulWidget {
  const AppointmentBookingScreen({super.key, this.existing = const []});

  /// The participant's current upcoming/awaiting appointments, used for the
  /// client-side overlap and open-request checks.
  final List<AppointmentInfo> existing;

  @override
  State<AppointmentBookingScreen> createState() => _AppointmentBookingScreenState();
}

class _AppointmentBookingScreenState extends State<AppointmentBookingScreen> {
  final _formKey = GlobalKey<FormState>();
  final _topic = TextEditingController();
  final _details = TextEditingController();

  AppointmentOptions? _options;
  bool _loading = true;
  Object? _loadError;

  int? _instructorId;
  int? _courseId;
  DateTime? _date;
  TimeOfDay? _time;
  int _duration = 30;
  String _mode = 'online';

  bool _submitting = false;
  bool _attempted = false;
  Map<String, String> _serverErrors = const {};

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _topic.dispose();
    _details.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    if (!await SyncService.instance.isOnline()) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _loadError = AppException.offline;
      });
      return;
    }
    try {
      final options = await AppointmentsApi.instance.options();
      if (!mounted) return;
      setState(() {
        _options = options;
        _loading = false;
        if (!options.durations.contains(_duration)) _duration = options.durations.first;
        if (!options.modes.any((m) => m.value == _mode)) _mode = options.modes.first.value;
        // Pre-select when there is only one choice.
        if (options.instructors.length == 1) _selectInstructor(options.instructors.first.id);
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _loadError = error;
      });
    }
  }

  void _selectInstructor(int? id) {
    _instructorId = id;
    final courses = _options?.instructor(id)?.courses ?? const <AppointmentCourse>[];
    if (!courses.any((c) => c.id == _courseId)) {
      _courseId = courses.length == 1 ? courses.first.id : null;
    }
    _clearServerError('instructor_user_id');
    _clearServerError('course_id');
  }

  void _clearServerError(String field) {
    if (!_serverErrors.containsKey(field)) return;
    _serverErrors = Map.of(_serverErrors)..remove(field);
  }

  /// The chosen start as a local DateTime, or null if incomplete.
  DateTime? get _start {
    final d = _date;
    final t = _time;
    if (d == null || t == null) return null;
    return DateTime(d.year, d.month, d.day, t.hour, t.minute);
  }

  String? get _timeError {
    final server = _serverErrors['starts_at'];
    if (server != null) return server;
    if (!_attempted && (_date == null || _time == null)) return null;
    final start = _start;
    final basic = validateAppointmentStart(start, maxDaysAhead: _options?.maxDaysAhead ?? 180);
    if (basic != null) return basic;
    return validateNoOverlap(start!, _duration, widget.existing);
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final last = today.add(Duration(days: _options?.maxDaysAhead ?? 180));
    final picked = await showDatePicker(
      context: context,
      initialDate: _date != null && !_date!.isBefore(today) ? _date! : today,
      firstDate: today,
      lastDate: last,
      helpText: 'Appointment date',
    );
    if (picked == null || !mounted) return;
    setState(() {
      _date = picked;
      _clearServerError('starts_at');
    });
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _time ?? const TimeOfDay(hour: 10, minute: 0),
      helpText: 'Start time',
    );
    if (picked == null || !mounted) return;
    setState(() {
      _time = picked;
      _clearServerError('starts_at');
    });
  }

  Future<void> _submit() async {
    setState(() => _attempted = true);
    final formOk = _formKey.currentState!.validate();
    if (!formOk || _timeError != null || _instructorId == null || _courseId == null) {
      showAppSnackBar(context, 'Please fix the highlighted fields.', error: true);
      return;
    }

    final limit = validateOpenRequests(widget.existing, max: _options?.maxOpenRequests ?? 3);
    if (limit != null) {
      showAppSnackBar(context, limit, error: true);
      return;
    }

    if (!await SyncService.instance.isOnline()) {
      if (mounted) showAppSnackBar(context, "You're offline. Connect to the internet to send your request.");
      return;
    }

    setState(() {
      _submitting = true;
      _serverErrors = const {};
    });
    try {
      final created = await AppointmentsApi.instance.create(
        instructorId: _instructorId!,
        courseId: _courseId!,
        localStart: _start!,
        durationMinutes: _duration,
        mode: _mode,
        topic: _topic.text,
        details: _details.text,
      );
      if (!mounted) return;
      Navigator.pop(context, created);
    } catch (error) {
      if (!mounted) return;
      final mapped = AppException.from(error);
      setState(() {
        _submitting = false;
        // `date`/`start_time` are the web field names; show them on the time.
        final errors = Map.of(mapped.fieldErrors);
        for (final key in ['date', 'start_time']) {
          final v = errors.remove(key);
          if (v != null) errors['starts_at'] ??= v;
        }
        _serverErrors = errors;
      });
      _formKey.currentState?.validate();
      showErrorSnackBar(context, error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Book an appointment')),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading) return const LoadingSkeleton(itemCount: 5, itemHeight: 64);
    final options = _options;
    if (options == null) {
      return ErrorState(
        error: _loadError ?? AppException.offline,
        title: "Booking options couldn't be loaded",
        onRetry: _loadOptions,
      );
    }
    if (options.instructors.isEmpty) {
      return const EmptyState(
        icon: Icons.person_search_outlined,
        title: 'No instructors to book yet',
        message: 'You can book appointments with instructors of the courses you are enrolled in.',
      );
    }

    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final courses = options.instructor(_instructorId)?.courses ?? const <AppointmentCourse>[];
    final timeError = _timeError;
    final start = _start;
    final localizations = MaterialLocalizations.of(context);

    return Form(
      key: _formKey,
      child: ListView(
        padding: AppSpacing.pagePadding,
        children: [
          Text(
            'Request time with one of your course instructors. They will approve it, '
            'decline it or propose another time.',
            style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: AppSpacing.lg),
          DropdownButtonFormField<int>(
            initialValue: _instructorId,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: 'Instructor *',
              errorText: _serverErrors['instructor_user_id'],
            ),
            items: [
              for (final i in options.instructors)
                DropdownMenuItem(value: i.id, child: Text(i.name, overflow: TextOverflow.ellipsis)),
            ],
            validator: (v) => v == null ? 'Choose an instructor' : null,
            onChanged: _submitting ? null : (v) => setState(() => _selectInstructor(v)),
          ),
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<int>(
            // Rebuild when the instructor changes so the selection resets.
            key: ValueKey('course-$_instructorId'),
            initialValue: courses.any((c) => c.id == _courseId) ? _courseId : null,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: 'Course *',
              helperText: _instructorId == null ? 'Choose an instructor first' : null,
              errorText: _serverErrors['course_id'],
            ),
            items: [
              for (final c in courses)
                DropdownMenuItem(value: c.id, child: Text(c.title, overflow: TextOverflow.ellipsis)),
            ],
            validator: (v) => v == null ? 'Choose a course' : null,
            onChanged: _submitting || _instructorId == null
                ? null
                : (v) => setState(() {
                      _courseId = v;
                      _clearServerError('course_id');
                    }),
          ),
          const SizedBox(height: AppSpacing.lg),
          Text('Date and start time *', style: theme.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _submitting ? null : _pickDate,
                  icon: const Icon(Icons.calendar_today_outlined),
                  label: Text(_date == null ? 'Pick date' : formatAppointmentDay(_date!)),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _submitting ? null : _pickTime,
                  icon: const Icon(Icons.schedule_outlined),
                  label: Text(_time == null
                      ? 'Pick time'
                      : localizations.formatTimeOfDay(_time!, alwaysUse24HourFormat: true)),
                ),
              ),
            ],
          ),
          if (timeError != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs, left: AppSpacing.md),
              child: Text(
                timeError,
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.error),
              ),
            )
          else if (start != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs, left: AppSpacing.md),
              child: Text(
                formatAppointmentRange(start, start.add(Duration(minutes: _duration))),
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ),
          const SizedBox(height: AppSpacing.lg),
          Text('Duration', style: theme.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              for (final d in options.durations)
                ChoiceChip(
                  label: Text('$d min'),
                  selected: _duration == d,
                  onSelected: _submitting
                      ? null
                      : (_) => setState(() {
                            _duration = d;
                            _clearServerError('duration_minutes');
                          }),
                ),
            ],
          ),
          if (_serverErrors['duration_minutes'] != null)
            Text(_serverErrors['duration_minutes']!,
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.error)),
          const SizedBox(height: AppSpacing.lg),
          Text('Mode', style: theme.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          SegmentedButton<String>(
            segments: [
              for (final m in options.modes)
                ButtonSegment(
                  value: m.value,
                  label: Text(m.label),
                  icon: Icon(m.value == 'online' ? Icons.videocam_outlined : Icons.place_outlined),
                ),
            ],
            selected: {_mode},
            onSelectionChanged: _submitting
                ? null
                : (s) => setState(() {
                      _mode = s.first;
                      _clearServerError('mode');
                    }),
          ),
          if (_serverErrors['mode'] != null)
            Text(_serverErrors['mode']!, style: theme.textTheme.bodySmall?.copyWith(color: scheme.error)),
          const SizedBox(height: AppSpacing.lg),
          TextFormField(
            controller: _topic,
            enabled: !_submitting,
            maxLength: options.topicMax,
            textCapitalization: TextCapitalization.sentences,
            decoration: InputDecoration(
              labelText: 'Topic *',
              hintText: 'e.g. Help with assignment 2',
              errorText: _serverErrors['topic'],
            ),
            onChanged: (_) {
              if (_serverErrors.containsKey('topic')) setState(() => _clearServerError('topic'));
            },
            validator: (v) => (v?.trim().isEmpty ?? true) ? 'Enter a topic' : null,
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _details,
            enabled: !_submitting,
            maxLines: 4,
            maxLength: options.detailsMax,
            textCapitalization: TextCapitalization.sentences,
            decoration: InputDecoration(
              labelText: 'Details (optional)',
              hintText: 'What would you like to discuss?',
              alignLabelWithHint: true,
              errorText: _serverErrors['details'],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton.icon(
            onPressed: _submitting ? null : _submit,
            icon: _submitting
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.send_rounded),
            label: const Text('Send request'),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            'Times are in your device\'s time zone. You can have up to '
            '${options.maxOpenRequests} requests awaiting a reply at once.',
            style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: AppSpacing.xxl),
        ],
      ),
    );
  }
}
