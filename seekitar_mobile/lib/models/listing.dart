import '../core/constants.dart';

class Listing {
  final String id, title;
  final String? description;
  final String listingType;
  final double? price;
  final int? stockQty, slot;
  final List<String> images;
  final String status;
  final String? storeId, storeName, storeDistrict, storeOwnerId;
  final bool? storeVerified;
  final double? distanceKm;
  final DateTime? createdAt;
  final bool isFavorited;

  Listing({required this.id, required this.title, this.description, required this.listingType, this.price, this.stockQty, this.slot, this.images = const [], this.status = 'active', this.storeId, this.storeName, this.storeDistrict, this.storeOwnerId, this.storeVerified, this.distanceKm, this.createdAt, this.isFavorited = false});

  factory Listing.fromJson(Map<String, dynamic> json) => Listing(
    id: json['id']?.toString() ?? '', title: json['title']?.toString() ?? '',
    description: json['description']?.toString(), listingType: json['listing_type']?.toString() ?? 'product',
    price: (json['price'] as num?)?.toDouble(), stockQty: json['stock_qty'] as int?, slot: json['slot'] as int?,
    images: (json['images'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
    status: json['status']?.toString() ?? 'active',
    storeId: json['store']?['id']?.toString(), storeName: json['store']?['name']?.toString(),
    storeDistrict: json['store']?['district']?.toString() ?? json['store']?['regency']?.toString(),
    storeOwnerId: json['store']?['owner_id']?.toString(),
    storeVerified: json['store']?['verified_at'] != null,
    distanceKm: (json['distance_km'] as num?)?.toDouble(),
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    isFavorited: json['is_favorited'] ?? false,
  );

  String get priceDisplay => price != null ? AppConstants.formatRupiah(price!) : 'Hubungi Penjual';
  String get typeLabel => AppConstants.typeLabel(listingType);
}
