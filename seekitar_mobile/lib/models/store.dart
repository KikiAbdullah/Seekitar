import 'package:freezed_annotation/freezed_annotation.dart';
part 'store.freezed.dart';
part 'store.g.dart';

@freezed
class Store with _$Store {
  const factory Store({
    required String id,
    required String name,
    String? photo,
    String? address,
    String? regency,
    String? district,
    @Default('pending') String status,
    double? latitude,
    double? longitude,
    @JsonKey(name: 'service_radius_km') double? serviceRadiusKm,
    @JsonKey(name: 'accepts_cod') @Default(true) bool acceptsCod,
    @JsonKey(name: 'is_active') @Default(true) bool isActive,
    @JsonKey(name: 'verified_at') DateTime? verifiedAt,
    @JsonKey(name: 'distance_km') double? distanceKm,
    @JsonKey(name: 'rating_avg') @Default(0) double ratingAvg,
    @JsonKey(name: 'reviews_count') @Default(0) int reviewsCount,
  }) = _Store;

  factory Store.fromJson(Map<String, dynamic> json) => _$StoreFromJson(json);
}

extension StoreX on Store {
  bool get isVerified => verifiedAt != null;
}
