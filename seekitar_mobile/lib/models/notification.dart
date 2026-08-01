class AppNotification {
  final String id, type, title, body;
  final String? dataId, dataType;
  final DateTime? readAt;
  final DateTime createdAt;

  AppNotification({required this.id, required this.type, required this.title, required this.body, this.dataId, this.dataType, this.readAt, required this.createdAt});

  factory AppNotification.fromJson(Map<String, dynamic> json) => AppNotification(
    id: json['id']?.toString() ?? '', type: json['type']?.toString() ?? '',
    title: json['title']?.toString() ?? '', body: json['body']?.toString() ?? '',
    dataId: json['data_id']?.toString(), dataType: json['data_type']?.toString(),
    readAt: json['read_at'] != null ? DateTime.tryParse(json['read_at'].toString()) : null,
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
  );

  bool get isRead => readAt != null;
}

class NotificationPreference {
  final String key, label;
  bool enabled;
  NotificationPreference({required this.key, required this.label, this.enabled = true});

  factory NotificationPreference.fromJson(Map<String, dynamic> json) => NotificationPreference(
    key: json['key']?.toString() ?? '', label: json['label']?.toString() ?? '',
    enabled: json['enabled'] ?? true,
  );
}
