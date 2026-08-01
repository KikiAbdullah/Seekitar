import 'package:freezed_annotation/freezed_annotation.dart';
import '../core/constants.dart';
part 'order.freezed.dart';
part 'order.g.dart';

@freezed
class Order with _$Order {
  const factory Order({
    required String id,
    @JsonKey(name: 'order_number') String? orderNumber,
    @JsonKey(name: 'order_type') required String orderType,
    required String status,
    @Default(1) int quantity,
    @JsonKey(name: 'total_amount') required double totalAmount,
    @JsonKey(name: 'discount_amount') double? discountAmount,
    @JsonKey(name: 'store_id') String? storeId,
    @JsonKey(name: 'store_name') String? storeName,
    @JsonKey(name: 'listing_id') String? listingId,
    @JsonKey(name: 'listing_title') String? listingTitle,
    @JsonKey(name: 'listing_images') List<String>? listingImages,
    @JsonKey(name: 'buyer_name') String? buyerName,
    @JsonKey(name: 'payment_method') String? paymentMethod,
    String? notes,
    @JsonKey(name: 'created_at') DateTime? createdAt,
    @JsonKey(name: 'completed_at') DateTime? completedAt,
  }) = _Order;

  factory Order.fromJson(Map<String, dynamic> json) => _$OrderFromJson(json);
}

extension OrderX on Order {
  String get statusLabel => AppConstants.orderStatusLabel(status);
  String get priceDisplay => AppConstants.formatRupiah(totalAmount);
}

@freezed
class Offer with _$Offer {
  const factory Offer({
    required String id,
    required double price,
    @JsonKey(name: 'additional_cost') double? additionalCost,
    @JsonKey(name: 'estimated_hours') int? estimatedHours,
    String? notes,
    required String status,
    @JsonKey(name: 'expires_at') DateTime? expiresAt,
    @JsonKey(name: 'store_id') String? storeId,
    @JsonKey(name: 'store_name') String? storeName,
    @JsonKey(name: 'store_rating') double? storeRating,
  }) = _Offer;

  factory Offer.fromJson(Map<String, dynamic> json) => _$OfferFromJson(json);
}
