import 'package:freezed_annotation/freezed_annotation.dart';
part 'notification.freezed.dart';
part 'notification.g.dart';

@freezed
class AppNotification with _$AppNotification {
  const factory AppNotification({
    required String id,
    required String type,
    required String title,
    required String body,
    @JsonKey(name: 'data_id') String? dataId,
    @JsonKey(name: 'data_type') String? dataType,
    @JsonKey(name: 'read_at') DateTime? readAt,
    @JsonKey(name: 'created_at') required DateTime createdAt,
  }) = _AppNotification;

  factory AppNotification.fromJson(Map<String, dynamic> json) => _$AppNotificationFromJson(json);

  const AppNotification._();

  bool get isRead => readAt != null;
}

@freezed
class NotificationPreference with _$NotificationPreference {
  const factory NotificationPreference({
    required String key,
    required String label,
    @Default(true) bool enabled,
  }) = _NotificationPreference;

  factory NotificationPreference.fromJson(Map<String, dynamic> json) => _$NotificationPreferenceFromJson(json);
}
