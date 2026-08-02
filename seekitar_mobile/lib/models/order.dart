import '../core/constants.dart';

class Order {
  final String id;
  final String? orderNumber;
  final String orderType, status;
  final int quantity;
  final double totalAmount;
  final double? discountAmount;
  final String? buyerId, storeId, storeOwnerId, listingId, listingTitle;
  final List<String>? listingImages;
  final String? buyerName, sellerName, storeName, paymentMethod, deliveryMethod, notes;
  final String? shippingAddress, paymentProofUrl, cancelReason, statusLabel;
  final String? bankAccount, bankAccountName;
  final bool canReview;
  final bool paymentConfirmed;
  final DateTime? createdAt, completedAt, cancelledAt, paymentConfirmedAt;

  Order({
    required this.id,
    this.orderNumber,
    required this.orderType,
    required this.status,
    this.quantity = 1,
    required this.totalAmount,
    this.discountAmount,
    this.buyerId,
    this.storeId,
    this.storeOwnerId,
    this.listingId,
    this.listingTitle,
    this.listingImages,
    this.buyerName,
    this.sellerName,
    this.storeName,
    this.paymentMethod,
    this.deliveryMethod,
    this.notes,
    this.shippingAddress,
    this.paymentProofUrl,
    this.cancelReason,
    this.statusLabel,
    this.bankAccount,
    this.bankAccountName,
    this.canReview = false,
    this.paymentConfirmed = false,
    this.createdAt,
    this.completedAt,
    this.cancelledAt,
    this.paymentConfirmedAt,
  });

  factory Order.fromJson(Map<String, dynamic> json) => Order(
    id: json['id']?.toString() ?? '',
    orderNumber: json['order_number']?.toString(),
    orderType: json['order_type']?.toString() ?? 'product',
    status: json['status']?.toString() ?? 'menunggu_konfirmasi',
    quantity: json['quantity'] ?? 1,
    totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
    discountAmount: (json['discount_amount'] as num?)?.toDouble(),
    buyerId: json['buyer_id']?.toString(),
    storeId: json['store_id']?.toString() ?? json['store']?['id']?.toString(),
    storeOwnerId: json['store']?['owner_id']?.toString(),
    listingId: json['listing_id']?.toString() ?? json['listing']?['id']?.toString(),
    listingTitle: json['listing']?['title']?.toString(),
    listingImages: (json['listing']?['images'] as List<dynamic>?)?.map((e) => e.toString()).toList(),
    buyerName: json['buyer']?['name']?.toString(),
    sellerName: json['store']?['name']?.toString(),
    storeName: json['store']?['name']?.toString(),
    paymentMethod: json['payment_method']?.toString(),
    deliveryMethod: json['delivery_method']?.toString(),
    notes: json['notes']?.toString(),
    shippingAddress: json['shipping_address']?.toString(),
    paymentProofUrl: json['payment_proof_url']?.toString(),
    cancelReason: json['cancel_reason']?.toString(),
    statusLabel: json['status_label']?.toString(),
    bankAccount: json['bank_account']?.toString(),
    bankAccountName: json['bank_account_name']?.toString(),
    canReview: json['can_review'] ?? false,
    paymentConfirmed: json['payment_confirmed_at'] != null,
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    completedAt: json['completed_at'] != null ? DateTime.tryParse(json['completed_at'].toString()) : null,
    cancelledAt: json['cancelled_at'] != null ? DateTime.tryParse(json['cancelled_at'].toString()) : null,
    paymentConfirmedAt: json['payment_confirmed_at'] != null ? DateTime.tryParse(json['payment_confirmed_at'].toString()) : null,
  );

  String get displayStatusLabel => statusLabel ?? AppConstants.orderStatusLabel(status);
  String get priceDisplay => AppConstants.formatRupiah(totalAmount);
  bool get isTransfer => paymentMethod == 'transfer';
  bool get isDelivery => deliveryMethod == 'delivery';
  bool get isFinal => status == 'selesai' || status == 'dibatalkan';
  bool get requiresPaymentProof => isTransfer && paymentProofUrl == null && !paymentConfirmed;
  String get paymentLabel => AppConstants.paymentMethodLabel(paymentMethod);
  String get deliveryLabel => AppConstants.deliveryMethodLabel(deliveryMethod);
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
