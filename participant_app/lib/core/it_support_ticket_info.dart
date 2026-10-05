import 'formatters.dart';

/// Typed view over an IT support ticket from the /api/v1/it-support/tickets
/// endpoints. Parsing is defensive because fields may arrive as strings or
/// be absent entirely.
class ItSupportTicketInfo {
  ItSupportTicketInfo(this.raw);

  final Map<String, dynamic> raw;

  int? get id => asInt(raw['id']);

  String get subject =>
      tidyTitle(raw['subject']?.toString(), fallback: 'IT support request');

  String? get description {
    final text = raw['description']?.toString().trim() ?? '';
    return text.isEmpty ? null : text;
  }

  String get category {
    final value = raw['category']?.toString().trim() ?? '';
    return value.isEmpty ? 'general' : value;
  }

  String get categoryLabel {
    final label = humanise(category);
    return label.isEmpty ? 'General' : label;
  }

  String get priority {
    final value = raw['priority']?.toString().trim() ?? '';
    return value.isEmpty ? 'normal' : value;
  }

  String get priorityLabel {
    final label = humanise(priority);
    return label.isEmpty ? 'Normal' : label;
  }

  String get status {
    final value = raw['status']?.toString().trim() ?? '';
    return value.isEmpty ? 'open' : value;
  }

  String get statusLabel => switch (status) {
        'in_progress' => 'In progress',
        'awaiting_requester' => 'Awaiting your reply',
        'resolved' => 'Resolved',
        _ => 'Open',
      };

  int? get requesterId => asInt(raw['requester_id']);
  int? get assigneeId => asInt(raw['assignee_id']);

  DateTime? get createdAt => _parseDate(raw['created_at']);
  DateTime? get updatedAt => _parseDate(raw['updated_at']);
  DateTime? get statusUpdatedAt => _parseDate(raw['status_updated_at']);

  bool get isResolved => status == 'resolved';

  static DateTime? _parseDate(dynamic value) {
    final parsed = DateTime.tryParse(value?.toString().trim() ?? '');
    return parsed;
  }
}
