class Store {
  final String id, name;
  final String? photo, address, regency, district, ownerId;
  final String status;
  final double? latitude, longitude, serviceRadiusKm;
  final bool acceptsCod, isActive;
  final DateTime? verifiedAt;
  final double? distanceKm;
  final double ratingAvg;
  final int reviewsCount;

  Store({required this.id, required this.name, this.photo, this.address, this.regency, this.district, this.ownerId, required this.status, this.latitude, this.longitude, this.serviceRadiusKm, this.acceptsCod = true, this.isActive = true, this.verifiedAt, this.distanceKm, this.ratingAvg = 0, this.reviewsCount = 0});

  factory Store.fromJson(Map<String, dynamic> json) {
    // API memakai GeoJSON {type, coordinates:[lng,lat]} (API §12.3).
    final loc = json['location'];
    double? lat, lng;
    if (loc is Map && loc['coordinates'] is List && (loc['coordinates'] as List).length >= 2) {
      final c = loc['coordinates'] as List;
      lng = (c[0] as num?)?.toDouble();
      lat = (c[1] as num?)?.toDouble();
    } else {
      lat = (json['latitude'] as num?)?.toDouble();
      lng = (json['longitude'] as num?)?.toDouble();
    }
    return Store(
      id: json['id']?.toString() ?? '', name: json['name']?.toString() ?? '',
      photo: json['photo']?.toString(), address: json['address']?.toString(),
      regency: json['regency']?.toString(), district: json['district']?.toString(),
      ownerId: json['owner_id']?.toString(),
      status: json['status']?.toString() ?? json['verification_status']?.toString() ?? 'pending',
      latitude: lat, longitude: lng,
      serviceRadiusKm: (json['service_radius_km'] as num?)?.toDouble(),
      acceptsCod: json['accepts_cod'] ?? true, isActive: json['is_active'] ?? true,
      verifiedAt: json['verified_at'] != null ? DateTime.tryParse(json['verified_at'].toString()) : null,
      distanceKm: (json['distance_km'] as num?)?.toDouble(),
      ratingAvg: (json['rating_avg'] as num?)?.toDouble() ?? 0, reviewsCount: json['reviews_count'] ?? 0,
    );
  }

  bool get isVerified => verifiedAt != null || status == 'verified';
}
