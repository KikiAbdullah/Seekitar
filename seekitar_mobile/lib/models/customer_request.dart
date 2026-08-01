import 'package:freezed_annotation/freezed_annotation.dart';
part 'customer_request.freezed.dart';
part 'customer_request.g.dart';

@freezed
class CustomerRequest with _$CustomerRequest {
  const factory CustomerRequest({
    required String id,
    required String title,
    String? description,
    @JsonKey(name: 'category_id') required int categoryId,
    @JsonKey(name: 'category_name') String? categoryName,
    required String status,
    required double latitude,
    required double longitude,
    @JsonKey(name: 'radius_km') @Default(15) double radiusKm,
    @JsonKey(name: 'offers_count') @Default(0) int offersCount,
    @JsonKey(name: 'expires_at') DateTime? expiresAt,
    @JsonKey(name: 'created_at') DateTime? createdAt,
    @JsonKey(name: 'user_name') String? userName,
    @JsonKey(name: 'user_initials') String? userInitials,
  }) = _CustomerRequest;

  factory CustomerRequest.fromJson(Map<String, dynamic> json) => _$CustomerRequestFromJson(json);
}

extension CustomerRequestX on CustomerRequest {
  bool get isExpired => expiresAt != null && expiresAt!.isBefore(DateTime.now());
  String get timeLeft {
    if (expiresAt == null) return '';
    final diff = expiresAt!.difference(DateTime.now());
    if (diff.inSeconds <= 0) return 'Kedaluwarsa';
    if (diff.inHours > 0) return '${diff.inHours}j ${diff.inMinutes % 60}m';
    return '${diff.inMinutes}m';
  }
}
