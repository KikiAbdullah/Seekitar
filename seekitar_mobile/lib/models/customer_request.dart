import '../core/text_utils.dart';

class CustomerRequest {
  final String id, title;
  final String? description;
  final int categoryId;
  final String? categoryName;
  final String status;
  final double latitude, longitude;
  final double radiusKm;
  final int offersCount;
  final DateTime? expiresAt, createdAt;
  final String? userName, userInitials;

  CustomerRequest({required this.id, required this.title, this.description, required this.categoryId, this.categoryName, required this.status, required this.latitude, required this.longitude, this.radiusKm = 15, this.offersCount = 0, this.expiresAt, this.createdAt, this.userName, this.userInitials});

  factory CustomerRequest.fromJson(Map<String, dynamic> json) {
    // Server mengirim key `buyer` (CustomerRequestResource) — hanya berisi
    // `name` untuk pihak NON-pemilik (feed home). Key `user` dipakai untuk
    // kompatibilitas pemanggilan lain.
    final buyer = (json['buyer'] ?? json['user']) as Map<String, dynamic>?;
    final userName = buyer?['name']?.toString();
    return CustomerRequest(
      id: json['id']?.toString() ?? '', title: json['title']?.toString() ?? '',
      description: json['description']?.toString(), categoryId: json['category_id'] ?? 0,
      categoryName: json['category']?['name']?.toString(),
      status: json['status']?.toString() ?? 'open',
      latitude: (json['latitude'] as num?)?.toDouble() ?? 0, longitude: (json['longitude'] as num?)?.toDouble() ?? 0,
      radiusKm: (json['radius_km'] as num?)?.toDouble() ?? 15, offersCount: json['offers_count'] ?? 0,
      expiresAt: json['expires_at'] != null ? DateTime.tryParse(json['expires_at'].toString()) : null,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      userName: userName,
      userInitials: buyer?['initials']?.toString(),
    );
  }

  /// Inisial untuk avatar — server hanya mengirim nama disingkat
  /// (`displayName()`), jadi dihitung di klien bila `initials` tidak ada.
  String get initials {
    if (userInitials != null && userInitials!.isNotEmpty) return userInitials!;
    final name = userName?.trim();
    if (name == null || name.isEmpty) return '?';
    final parts = name.split(RegExp(r'\s+'));
    if (parts.length == 1) return firstChars(parts.first, 1).toUpperCase();
    return (firstChars(parts.first, 1) + firstChars(parts.last, 1)).toUpperCase();
  }

  bool get isExpired => expiresAt != null && expiresAt!.isBefore(DateTime.now());
  String get timeLeft {
    if (expiresAt == null) return '';
    final diff = expiresAt!.difference(DateTime.now());
    if (diff.inSeconds <= 0) return 'Kedaluwarsa';
    if (diff.inHours > 0) return '${diff.inHours}j ${diff.inMinutes % 60}m';
    return '${diff.inMinutes}m';
  }
}
