import '../core/constants.dart';

class Order {
  final String id;
  final String? orderNumber;
  final String orderType, status;
  final int quantity;
  final double totalAmount;
  final double? discountAmount;
  final String? storeId, storeName, listingId, listingTitle;
  final List<String>? listingImages;
  final String? buyerName, paymentMethod, notes;
  final DateTime? createdAt, completedAt;

  Order({required this.id, this.orderNumber, required this.orderType, required this.status, this.quantity = 1, required this.totalAmount, this.discountAmount, this.storeId, this.storeName, this.listingId, this.listingTitle, this.listingImages, this.buyerName, this.paymentMethod, this.notes, this.createdAt, this.completedAt});

  factory Order.fromJson(Map<String, dynamic> json) => Order(
    id: json['id']?.toString() ?? '', orderNumber: json['order_number']?.toString(),
    orderType: json['order_type']?.toString() ?? 'product', status: json['status']?.toString() ?? 'menunggu_konfirmasi',
    quantity: json['quantity'] ?? 1, totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
    discountAmount: (json['discount_amount'] as num?)?.toDouble(),
    storeId: json['store']?['id']?.toString(), storeName: json['store']?['name']?.toString(),
    listingId: json['listing']?['id']?.toString(), listingTitle: json['listing']?['title']?.toString(),
    listingImages: (json['listing']?['images'] as List<dynamic>?)?.map((e) => e.toString()).toList(),
    buyerName: json['buyer']?['name']?.toString(), paymentMethod: json['payment_method']?.toString(),
    notes: json['notes']?.toString(),
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    completedAt: json['completed_at'] != null ? DateTime.tryParse(json['completed_at'].toString()) : null,
  );

  String get statusLabel => AppConstants.orderStatusLabel(status);
  String get priceDisplay => AppConstants.formatRupiah(totalAmount);
}

class Offer {
  final String id;
  final double price;
  final double? additionalCost;
  final int? estimatedHours;
  final String? notes;
  final String status;
  final DateTime? expiresAt;
  final String? storeId, storeName;
  final double? storeRating;

  Offer({required this.id, required this.price, this.additionalCost, this.estimatedHours, this.notes, required this.status, this.expiresAt, this.storeId, this.storeName, this.storeRating});

  factory Offer.fromJson(Map<String, dynamic> json) => Offer(
    id: json['id']?.toString() ?? '', price: (json['price'] as num?)?.toDouble() ?? 0,
    additionalCost: (json['additional_cost'] as num?)?.toDouble(),
    estimatedHours: json['estimated_hours'] as int?, notes: json['notes']?.toString(),
    status: json['status']?.toString() ?? 'pending',
    expiresAt: json['expires_at'] != null ? DateTime.tryParse(json['expires_at'].toString()) : null,
    storeId: json['store']?['id']?.toString(), storeName: json['store']?['name']?.toString(),
    storeRating: (json['store']?['rating_avg'] as num?)?.toDouble(),
  );
}
