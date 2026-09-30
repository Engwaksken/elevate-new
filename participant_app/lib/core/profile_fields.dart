import 'formatters.dart';

enum ProfileFieldType { text, multiline, email, phone, number, date, url, select, boolean, list }

/// One editable profile field, built from GET /profile `editable_fields`.
/// Each entry may be a plain field name (`"phone"`) or an object such as
/// `{"name":"phone","label":"Phone","type":"tel","required":false,
///   "max":30,"options":[...]}`. Types are inferred from the name when the
/// server doesn't say.
class ProfileField {
  const ProfileField({
    required this.name,
    required this.label,
    required this.type,
    this.required = false,
    this.maxLength,
    this.options = const [],
    this.helper,
    this.sensitive = false,
    this.countsTowardCompletion = true,
  });

  /// Known fields from the API contract (GET /profile `editable_fields`,
  /// which is a list of names). Unknown names are inferred.
  static const Map<String, ProfileField> known = {
    'name': ProfileField(
        name: 'name', label: 'Full name', type: ProfileFieldType.text,
        required: true, maxLength: 255),
    'given_name': ProfileField(
        name: 'given_name', label: 'Given name', type: ProfileFieldType.text, maxLength: 120),
    'surname': ProfileField(
        name: 'surname', label: 'Surname', type: ProfileFieldType.text, maxLength: 120),
    'other_name': ProfileField(
        name: 'other_name', label: 'Other name', type: ProfileFieldType.text,
        maxLength: 120, countsTowardCompletion: false),
    'phone': ProfileField(
        name: 'phone', label: 'Phone number', type: ProfileFieldType.phone, maxLength: 30),
    'gender': ProfileField(
      name: 'gender',
      label: 'Gender',
      type: ProfileFieldType.select,
      options: [
        ProfileOption('female', 'Female'),
        ProfileOption('male', 'Male'),
        ProfileOption('other', 'Other'),
        ProfileOption('prefer_not_to_say', 'Prefer not to say'),
      ],
    ),
    'date_of_birth': ProfileField(
        name: 'date_of_birth', label: 'Date of birth', type: ProfileFieldType.date),
    'country': ProfileField(
        name: 'country', label: 'Country', type: ProfileFieldType.text, maxLength: 190),
    'district': ProfileField(
        name: 'district', label: 'District', type: ProfileFieldType.text, maxLength: 190),
    'location': ProfileField(
        name: 'location', label: 'Town or village', type: ProfileFieldType.text, maxLength: 190),
    'education_level': ProfileField(
        name: 'education_level', label: 'Education level', type: ProfileFieldType.text,
        maxLength: 190),
    'employment_status': ProfileField(
        name: 'employment_status', label: 'Employment status', type: ProfileFieldType.text,
        maxLength: 190),
    'career_interests': ProfileField(
      name: 'career_interests',
      label: 'Career interests',
      type: ProfileFieldType.multiline,
      maxLength: 2000,
      helper: 'What would you love to learn or work on?',
    ),
    'preferred_language': ProfileField(
        name: 'preferred_language', label: 'Preferred language', type: ProfileFieldType.text,
        maxLength: 50),
    'is_pwd': ProfileField(
      name: 'is_pwd',
      label: 'Do you live with a disability?',
      type: ProfileFieldType.boolean,
      sensitive: true,
      countsTowardCompletion: false,
    ),
    'disability_types': ProfileField(
      name: 'disability_types',
      label: 'Type of disability',
      type: ProfileFieldType.list,
      sensitive: true,
      countsTowardCompletion: false,
      options: [
        ProfileOption('Visual', 'Visual'),
        ProfileOption('Hearing', 'Hearing'),
        ProfileOption('Physical', 'Physical or mobility'),
        ProfileOption('Speech', 'Speech'),
        ProfileOption('Learning', 'Learning or cognitive'),
        ProfileOption('Psychosocial', 'Psychosocial'),
      ],
    ),
    'disability_other': ProfileField(
      name: 'disability_other',
      label: 'Anything else you would like us to know',
      type: ProfileFieldType.text,
      maxLength: 255,
      sensitive: true,
      countsTowardCompletion: false,
    ),
  };

  factory ProfileField.fromJson(dynamic raw) {
    if (raw is Map) {
      final map = Map<String, dynamic>.from(raw);
      final name = (map['name'] ?? map['key'] ?? map['field'] ?? '').toString();
      final options = _options(map['options'] ?? map['choices']);
      final type = _typeFrom(map['type']?.toString(), name, hasOptions: options.isNotEmpty);
      return ProfileField(
        name: name,
        label: (map['label']?.toString().trim().isNotEmpty ?? false)
            ? map['label'].toString().trim()
            : labelFor(name),
        type: type,
        required: isTruthy(map['required']),
        maxLength: asInt(map['max'] ?? map['max_length'] ?? map['maxlength']),
        options: options,
        helper: map['help']?.toString() ?? map['hint']?.toString(),
      );
    }
    final name = raw?.toString() ?? '';
    return known[name] ??
        ProfileField(name: name, label: labelFor(name), type: _typeFrom(null, name));
  }

  final String name;
  final String label;
  final ProfileFieldType type;
  final bool required;
  final int? maxLength;
  final List<ProfileOption> options;
  final String? helper;

  /// Optional, private details (disability): grouped separately with a
  /// "you can skip this" note, and never counted for completion.
  final bool sensitive;

  final bool countsTowardCompletion;

  static List<ProfileOption> _options(dynamic raw) {
    if (raw is List) {
      return raw.map((o) {
        if (o is Map) {
          final value = (o['value'] ?? o['id'] ?? o['key'] ?? '').toString();
          return ProfileOption(value, (o['label'] ?? o['name'] ?? value).toString());
        }
        return ProfileOption(o.toString(), humanise(o));
      }).where((o) => o.value.isNotEmpty).toList();
    }
    if (raw is Map) {
      return raw.entries
          .map((e) => ProfileOption(e.key.toString(), e.value.toString()))
          .toList();
    }
    return const [];
  }

  static ProfileFieldType _typeFrom(String? type, String name, {bool hasOptions = false}) {
    switch (type?.toLowerCase()) {
      case 'email':
        return ProfileFieldType.email;
      case 'tel':
      case 'phone':
        return ProfileFieldType.phone;
      case 'number':
      case 'integer':
      case 'numeric':
        return ProfileFieldType.number;
      case 'date':
        return ProfileFieldType.date;
      case 'url':
        return ProfileFieldType.url;
      case 'textarea':
      case 'multiline':
      case 'longtext':
        return ProfileFieldType.multiline;
      case 'select':
      case 'enum':
      case 'radio':
        return ProfileFieldType.select;
      case 'boolean':
      case 'bool':
      case 'checkbox':
        return ProfileFieldType.boolean;
      case 'array':
      case 'list':
      case 'tags':
        return ProfileFieldType.list;
      case 'text':
      case 'string':
        return hasOptions ? ProfileFieldType.select : ProfileFieldType.text;
    }
    if (hasOptions) return ProfileFieldType.select;

    final n = name.toLowerCase();
    if (n.startsWith('is_') || n.startsWith('has_')) return ProfileFieldType.boolean;
    if (n.endsWith('_types') || n.endsWith('_list') || n.endsWith('_tags')) {
      return ProfileFieldType.list;
    }
    if (n.contains('email')) return ProfileFieldType.email;
    if (n.contains('phone') || n.contains('mobile') || n.contains('whatsapp')) {
      return ProfileFieldType.phone;
    }
    if (n.endsWith('_date') || n.contains('date_of') || n == 'dob' || n.endsWith('_on')) {
      return ProfileFieldType.date;
    }
    if (n.contains('url') || n.contains('website') || n.contains('linkedin')) {
      return ProfileFieldType.url;
    }
    if (n.contains('bio') || n.contains('about') || n.contains('address') ||
        n.contains('description') || n.contains('goals') || n.contains('notes')) {
      return ProfileFieldType.multiline;
    }
    if (n.startsWith('year') || n.endsWith('_year') || n.startsWith('age') ||
        n.startsWith('number_of') || n.endsWith('_count')) {
      return ProfileFieldType.number;
    }
    return ProfileFieldType.text;
  }

  /// "date_of_birth" -> "Date of birth".
  static String labelFor(String name) {
    final text = humanise(name.replaceAll('.', ' '));
    return text.isEmpty ? 'Field' : text;
  }

  static final _email = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');
  static final _phone = RegExp(r'^\+?[0-9 ()-]{7,20}$');

  /// Client-side check; the server's 422 message still wins.
  String? validate(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return required ? 'Please enter your ${label.toLowerCase()}' : null;
    if (maxLength != null && text.length > maxLength!) {
      return 'Please keep this under $maxLength characters';
    }
    switch (type) {
      case ProfileFieldType.email:
        return _email.hasMatch(text) ? null : 'Enter a valid email address';
      case ProfileFieldType.phone:
        return _phone.hasMatch(text) ? null : 'Enter a valid phone number';
      case ProfileFieldType.number:
        return num.tryParse(text) == null ? 'Enter a number' : null;
      case ProfileFieldType.url:
        final uri = Uri.tryParse(text.contains('://') ? text : 'https://$text');
        return uri == null || uri.host.isEmpty ? 'Enter a valid web address' : null;
      case ProfileFieldType.date:
        final date = DateTime.tryParse(text);
        if (date == null) return 'Pick a date';
        if (name == 'date_of_birth' &&
            (date.isAfter(DateTime.now()) || date.isBefore(DateTime(1900)))) {
          return 'Please choose a date in the past';
        }
        return null;
      case ProfileFieldType.select:
        return options.isEmpty || options.any((o) => o.value == text)
            ? null
            : 'Choose one of the options';
      case ProfileFieldType.text:
      case ProfileFieldType.multiline:
      case ProfileFieldType.boolean:
      case ProfileFieldType.list:
        return null;
    }
  }
}

class ProfileOption {
  const ProfileOption(this.value, this.label);

  final String value;
  final String label;
}

/// Parses `editable_fields`, dropping blanks and `photo` (edited separately).
List<ProfileField> parseEditableFields(dynamic raw) {
  if (raw is! List) return const [];
  final seen = <String>{};
  return raw
      .map(ProfileField.fromJson)
      .where((f) =>
          f.name.isNotEmpty &&
          !f.name.toLowerCase().startsWith('photo') &&
          seen.add(f.name))
      .toList();
}

/// Reads a (possibly nested, "profile.city") value as display text.
String profileValue(Map<String, dynamic> profile, String name) {
  dynamic value = profile[name];
  if (value == null && name.contains('.')) {
    dynamic node = profile;
    for (final part in name.split('.')) {
      node = node is Map ? node[part] : null;
    }
    value = node;
  }
  if (value == null) return '';
  if (value is List) {
    return value.map((v) => v.toString()).where((v) => v.isNotEmpty).join(', ');
  }
  return value.toString().trim();
}

/// Share (0-100) of editable fields that have a value. Optional private
/// fields (disability) and "other name" don't count, so nobody is nudged
/// to share them.
int profileCompletionPercent(Map<String, dynamic> profile, List<ProfileField> fields) {
  final counted = fields.where((f) => f.countsTowardCompletion).toList();
  if (counted.isEmpty) return 0;
  final filled = counted.where((f) => profileValue(profile, f.name).isNotEmpty).length;
  return (filled / counted.length * 100).round();
}

/// Counted fields that are still empty, for the "complete your profile" hint.
List<ProfileField> missingFields(Map<String, dynamic> profile, List<ProfileField> fields) =>
    fields
        .where((f) => f.countsTowardCompletion && profileValue(profile, f.name).isEmpty)
        .toList();

/// Human-readable value for the profile screen: option labels, dates as
/// "12 Apr 1998", booleans as Yes/No.
String displayProfileValue(ProfileField field, Map<String, dynamic> profile) {
  final raw = profileValue(profile, field.name);
  if (raw.isEmpty) return '';
  switch (field.type) {
    case ProfileFieldType.select:
      for (final o in field.options) {
        if (o.value == raw) return o.label;
      }
      return humanise(raw);
    case ProfileFieldType.date:
      return formatDateTime(raw, withTime: false) ?? raw;
    case ProfileFieldType.boolean:
      return isTruthy(profile[field.name]) ? 'Yes' : 'No';
    default:
      return raw;
  }
}
