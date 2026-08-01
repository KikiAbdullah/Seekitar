class UserAddress {
  final String id, label, address;
  final String? note;
  final double latitude, longitude;
  final bool isDefault;
  UserAddress({required this.id, required this.label, required this.address, this.note, required this.latitude, required this.longitude, this.isDefault = false});

  factory UserAddress.fromJson(Map<String, dynamic> json) => UserAddress(
    id: json['id']?.toString() ?? '', label: json['label']?.toString() ?? '',
    address: json['address']?.toString() ?? '', note: json['note']?.toString(),
    latitude: (json['latitude'] as num?)?.toDouble() ?? 0, longitude: (json['longitude'] as num?)?.toDouble() ?? 0,
    isDefault: json['is_default'] ?? false,
  );
}
