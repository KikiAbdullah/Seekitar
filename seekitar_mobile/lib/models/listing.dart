import 'package:freezed_annotation/freezed_annotation.dart';
import '../core/constants.dart';
part 'listing.freezed.dart';
part 'listing.g.dart';

@freezed
class Listing with _$Listing {
  const factory Listing({
    required String id,
    required String title,
    String? description,
    @JsonKey(name: 'listing_type') required String listingType,
    double? price,
    @JsonKey(name: 'stock_qty') int? stockQty,
    int? slot,
    @Default([]) List<String> images,
    @Default('active') String status,
    @JsonKey(name: 'store_id') String? storeId,
    @JsonKey(name: 'store_name') String? storeName,
    @JsonKey(name: 'store_verified') bool? storeVerified,
    @JsonKey(name: 'store_district') String? storeDistrict,
    @JsonKey(name: 'distance_km') double? distanceKm,
    @JsonKey(name: 'created_at') DateTime? createdAt,
    @JsonKey(name: 'is_favorited') @Default(false) bool isFavorited,
  }) = _Listing;

  factory Listing.fromJson(Map<String, dynamic> json) => _$ListingFromJson(json);
}

extension ListingX on Listing {
  String get priceDisplay => price != null ? AppConstants.formatRupiah(price!) : 'Hubungi Penjual';
  String get typeLabel => AppConstants.typeLabel(listingType);
}
