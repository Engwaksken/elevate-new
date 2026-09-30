import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/formatters.dart';
import '../core/network/app_exception.dart';
import '../core/profile_fields.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/sync_service.dart';
import '../widgets/state_views.dart';

/// Form built from GET /profile `editable_fields`. Sends only changed
/// fields (PUT /profile is a partial update) and shows 422 errors inline.
/// Pops with `true` when saved.
class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key, required this.profile, required this.fields});

  final Map<String, dynamic> profile;
  final List<ProfileField> fields;

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _text = {};
  final Map<String, String?> _select = {};

  /// is_pwd: null = prefer not to say.
  bool? _isPwd;
  final Set<String> _disabilityTypes = {};

  Map<String, String> _serverErrors = {};
  String? _generalError;
  bool _saving = false;

  bool _isTextual(ProfileField f) =>
      f.type != ProfileFieldType.select &&
      f.type != ProfileFieldType.boolean &&
      f.type != ProfileFieldType.list;

  @override
  void initState() {
    super.initState();
    for (final f in widget.fields) {
      if (_isTextual(f)) {
        _text[f.name] = TextEditingController(text: profileValue(widget.profile, f.name));
      } else if (f.type == ProfileFieldType.select) {
        final value = profileValue(widget.profile, f.name);
        _select[f.name] = value.isEmpty ? null : value;
      }
    }
    final pwd = widget.profile['is_pwd'];
    _isPwd = pwd == null ? null : isTruthy(pwd);
    final types = widget.profile['disability_types'];
    if (types is List) _disabilityTypes.addAll(types.map((t) => t.toString()));
  }

  @override
  void dispose() {
    for (final c in _text.values) {
      c.dispose();
    }
    super.dispose();
  }

  /// Laravel keys like `disability_types.0` map to their base field.
  String? _errorFor(String name) {
    for (final entry in _serverErrors.entries) {
      if (entry.key == name || entry.key.startsWith('$name.')) return entry.value;
    }
    return null;
  }

  Map<String, dynamic> _changes() {
    final changes = <String, dynamic>{};
    for (final f in widget.fields) {
      final before = widget.profile[f.name];
      switch (f.type) {
        case ProfileFieldType.boolean:
          // "Prefer not to say" leaves the stored answer untouched.
          if (f.name == 'is_pwd' && _isPwd != null && _isPwd != (before == null ? null : isTruthy(before))) {
            changes[f.name] = _isPwd;
          }
        case ProfileFieldType.list:
          final beforeList = before is List ? before.map((e) => e.toString()).toSet() : <String>{};
          if (f.name == 'disability_types') {
            final after = _isPwd == false ? <String>{} : _disabilityTypes;
            if (after.length != beforeList.length || !after.containsAll(beforeList)) {
              changes[f.name] = after.toList();
            }
          }
        case ProfileFieldType.select:
          final value = _select[f.name];
          if ((value ?? '') != (before?.toString() ?? '')) changes[f.name] = value;
        default:
          final value = _text[f.name]?.text.trim() ?? '';
          final old = before?.toString().trim() ?? '';
          if (value != old) {
            if (f.name == 'disability_other' && _isPwd == false) {
              if (old.isNotEmpty) changes[f.name] = null;
            } else {
              changes[f.name] = value.isEmpty ? null : value;
            }
          }
      }
    }
    return changes;
  }

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _serverErrors = {};
      _generalError = null;
    });
    if (!_formKey.currentState!.validate()) return;

    final changes = _changes();
    if (changes.isEmpty) {
      Navigator.pop(context, false);
      return;
    }

    if (!await SyncService.instance.isOnline()) {
      if (mounted) {
        setState(() => _generalError =
            "You're offline. Your changes are still here; save them once you're back online.");
      }
      return;
    }

    setState(() => _saving = true);
    try {
      await ParticipantDataService.instance.updateProfile(changes);
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      final mapped = AppException.from(error);
      if (!mounted) return;
      setState(() {
        _serverErrors = Map.of(mapped.fieldErrors);
        final known = mapped.fieldErrors.keys.any(
          (k) => widget.fields.any((f) => k == f.name || k.startsWith('${f.name}.')),
        );
        _generalError = known ? 'Please check the highlighted details.' : mapped.message;
      });
      // Validate after the rebuild so the fields see the server errors.
      WidgetsBinding.instance.addPostFrameCallback((_) => _formKey.currentState?.validate());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _pickDate(ProfileField f) async {
    final controller = _text[f.name]!;
    final now = DateTime.now();
    final isBirth = f.name == 'date_of_birth';
    final current = DateTime.tryParse(controller.text);
    final last = isBirth ? DateTime(now.year, now.month, now.day - 1) : DateTime(now.year + 5);
    final picked = await showDatePicker(
      context: context,
      initialDate: current ?? (isBirth ? DateTime(now.year - 25) : now),
      firstDate: DateTime(1900, 1, 2),
      lastDate: last,
      helpText: f.label,
    );
    if (picked == null) return;
    final y = picked.year.toString().padLeft(4, '0');
    final m = picked.month.toString().padLeft(2, '0');
    final d = picked.day.toString().padLeft(2, '0');
    setState(() => controller.text = '$y-$m-$d');
  }

  Widget _fieldWidget(ProfileField f) {
    final serverError = _errorFor(f.name);
    String? validator(String? v) => serverError ?? f.validate(v);

    switch (f.type) {
      case ProfileFieldType.select:
        final value = _select[f.name];
        final options = f.options;
        final hasValue = options.any((o) => o.value == value);
        return DropdownButtonFormField<String>(
          initialValue: hasValue ? value : null,
          isExpanded: true,
          decoration: InputDecoration(labelText: f.label, errorText: serverError),
          items: [
            for (final o in options)
              DropdownMenuItem(value: o.value, child: Text(o.label, overflow: TextOverflow.ellipsis)),
          ],
          validator: (v) => serverError ?? (f.required && v == null ? 'Please choose one' : null),
          onChanged: _saving ? null : (v) => setState(() => _select[f.name] = v),
        );
      case ProfileFieldType.boolean:
        return DropdownButtonFormField<bool?>(
          initialValue: _isPwd,
          isExpanded: true,
          decoration: InputDecoration(labelText: f.label, errorText: serverError),
          items: const [
            DropdownMenuItem<bool?>(value: null, child: Text('Prefer not to say')),
            DropdownMenuItem<bool?>(value: true, child: Text('Yes')),
            DropdownMenuItem<bool?>(value: false, child: Text('No')),
          ],
          onChanged: _saving ? null : (v) => setState(() => _isPwd = v),
        );
      case ProfileFieldType.list:
        return _ChipsField(
          label: f.label,
          options: f.options,
          selected: _disabilityTypes,
          error: serverError,
          enabled: !_saving,
          onChanged: (value, on) => setState(() {
            on ? _disabilityTypes.add(value) : _disabilityTypes.remove(value);
          }),
        );
      case ProfileFieldType.date:
        return TextFormField(
          controller: _text[f.name],
          readOnly: true,
          enabled: !_saving,
          decoration: InputDecoration(
            labelText: f.label,
            hintText: 'Choose a date',
            suffixIcon: const Icon(Icons.calendar_month_outlined),
          ),
          onTap: () => _pickDate(f),
          validator: validator,
        );
      default:
        final (keyboard, inputFormatters, autofill, capitalization) = switch (f.type) {
          ProfileFieldType.email => (TextInputType.emailAddress, <TextInputFormatter>[], AutofillHints.email, TextCapitalization.none),
          ProfileFieldType.phone => (
              TextInputType.phone,
              <TextInputFormatter>[FilteringTextInputFormatter.allow(RegExp(r'[0-9+() -]'))],
              AutofillHints.telephoneNumber,
              TextCapitalization.none,
            ),
          ProfileFieldType.number => (
              const TextInputType.numberWithOptions(decimal: true),
              <TextInputFormatter>[FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
              null,
              TextCapitalization.none,
            ),
          ProfileFieldType.url => (TextInputType.url, <TextInputFormatter>[], AutofillHints.url, TextCapitalization.none),
          ProfileFieldType.multiline => (TextInputType.multiline, <TextInputFormatter>[], null, TextCapitalization.sentences),
          _ => (TextInputType.text, <TextInputFormatter>[], _autofillFor(f.name), TextCapitalization.words),
        };
        final multiline = f.type == ProfileFieldType.multiline;
        return TextFormField(
          controller: _text[f.name],
          enabled: !_saving,
          keyboardType: keyboard,
          inputFormatters: inputFormatters,
          textCapitalization: capitalization,
          autofillHints: autofill == null ? null : [autofill],
          minLines: multiline ? 3 : 1,
          maxLines: multiline ? 6 : 1,
          maxLength: multiline ? f.maxLength : null,
          textInputAction: multiline ? TextInputAction.newline : TextInputAction.next,
          decoration: InputDecoration(
            labelText: f.required ? '${f.label} *' : f.label,
            helperText: f.helper,
            helperMaxLines: 2,
            alignLabelWithHint: multiline,
          ),
          onChanged: (_) {
            if (serverError != null) setState(() => _serverErrors.remove(f.name));
          },
          validator: validator,
        );
    }
  }

  static String? _autofillFor(String name) => switch (name) {
        'name' => AutofillHints.name,
        'given_name' => AutofillHints.givenName,
        'surname' => AutofillHints.familyName,
        'other_name' => AutofillHints.middleName,
        'country' => AutofillHints.countryName,
        'location' => AutofillHints.addressCity,
        _ => null,
      };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final general = widget.fields.where((f) => !f.sensitive).toList();
    final private = widget.fields.where((f) => f.sensitive).toList();
    final showDisabilityDetails = _isPwd == true;

    return Scaffold(
      appBar: AppBar(title: const Text('Edit profile')),
      body: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: AutofillGroup(
            child: ListView(
              padding: AppSpacing.listPadding,
              children: [
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Keep your details up to date so we can support you well.',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                if (_generalError != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Semantics(
                    liveRegion: true,
                    child: NoticeCard(
                      icon: Icons.error_outline,
                      tone: PillTone.danger,
                      message: _generalError!,
                    ),
                  ),
                ],
                if (widget.fields.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: AppSpacing.xl),
                    child: Text('There is nothing you can edit here yet.'),
                  ),
                for (final f in general) ...[
                  const SizedBox(height: AppSpacing.lg),
                  _fieldWidget(f),
                ],
                if (private.isNotEmpty) ...[
                  const SectionHeader(title: 'Private and optional'),
                  const NoticeCard(
                    icon: Icons.lock_outline,
                    tone: PillTone.rose,
                    message: 'You can skip this section. If you choose to share, only '
                        'you and the programme team will see it, so we can offer the '
                        'right support.',
                  ),
                  for (final f in private)
                    if (f.type == ProfileFieldType.boolean || showDisabilityDetails) ...[
                      const SizedBox(height: AppSpacing.lg),
                      _fieldWidget(f),
                    ],
                ],
                const SizedBox(height: AppSpacing.xl),
                FilledButton.icon(
                  onPressed: _saving ? null : _save,
                  icon: _saving
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.check_rounded),
                  label: Text(_saving ? 'Saving…' : 'Save changes'),
                ),
                const SizedBox(height: AppSpacing.sm),
                TextButton(
                  onPressed: _saving ? null : () => Navigator.pop(context, false),
                  child: const Text('Cancel'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Multi-select chips for list fields (e.g. disability types).
class _ChipsField extends StatelessWidget {
  const _ChipsField({
    required this.label,
    required this.options,
    required this.selected,
    required this.onChanged,
    required this.enabled,
    this.error,
  });

  final String label;
  final List<ProfileOption> options;
  final Set<String> selected;
  final void Function(String value, bool selected) onChanged;
  final bool enabled;
  final String? error;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    // Keep values she already had even if they aren't suggested options.
    final all = [
      ...options,
      for (final v in selected)
        if (!options.any((o) => o.value == v)) ProfileOption(v, v),
    ];
    return Semantics(
      container: true,
      label: label,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              for (final o in all)
                FilterChip(
                  label: Text(o.label),
                  selected: selected.contains(o.value),
                  onSelected: enabled ? (on) => onChanged(o.value, on) : null,
                ),
            ],
          ),
          if (error != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs),
              child: Text(error!, style: TextStyle(color: theme.colorScheme.error)),
            ),
        ],
      ),
    );
  }
}
