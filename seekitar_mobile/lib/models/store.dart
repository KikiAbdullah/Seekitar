class Store {
  final String id, name;
  final String? photo, address, regency, district;
  final String status;
  final double? latitude, longitude, serviceRadiusKm;
  final bool acceptsCod, isActive;
  final DateTime? verifiedAt;
  final double? distanceKm;
  final double ratingAvg;
  final int reviewsCount;

  Store({required this.id, required this.name, this.photo, this.address, this.regency, this.district, required this.status, this.latitude, this.longitude, this.serviceRadiusKm, this.acceptsCod = true, this.isActive = true, this.verifiedAt, this.distanceKm, this.ratingAvg = 0, this.reviewsCount = 0});

  factory Store.fromJson(Map<String, dynamic> json) => Store(
    id: json['id']?.toString() ?? '', name: json['name']?.toString() ?? '',
    photo: json['photo']?.toString(), address: json['address']?.toString(),
    regency: json['regency']?.toString(), district: json['district']?.toString(),
    status: json['status']?.toString() ?? 'pending',
    latitude: (json['latitude'] as num?)?.toDouble(), longitude: (json['longitude'] as num?)?.toDouble(),
    serviceRadiusKm: (json['service_radius_km'] as num?)?.toDouble(),
    acceptsCod: json['accepts_cod'] ?? true, isActive: json['is_active'] ?? true,
    verifiedAt: json['verified_at'] != null ? DateTime.tryParse(json['verified_at'].toString()) : null,
    distanceKm: (json['distance_km'] as num?)?.toDouble(),
    ratingAvg: (json['rating_avg'] as num?)?.toDouble() ?? 0, reviewsCount: json['reviews_count'] ?? 0,
  );

  bool get isVerified => verifiedAt != null;
}
