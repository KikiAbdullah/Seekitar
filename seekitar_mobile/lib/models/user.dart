import '../core/constants.dart';

class User {
  final String id;
  final String? name;
  final String phone;
  final String? email;
  final String? avatarUrl;
  final String? address;
  final String status;
  final double? latitude;
  final double? longitude;
  final DateTime? verifiedAt;
  final String? initials;

  User({required this.id, this.name, required this.phone, this.email, this.avatarUrl, this.address, required this.status, this.latitude, this.longitude, this.verifiedAt, this.initials});

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id']?.toString() ?? '', name: json['name']?.toString(), phone: json['phone']?.toString() ?? '',
    email: json['email']?.toString(), avatarUrl: json['avatar_url']?.toString(), address: json['address']?.toString(),
    status: json['status']?.toString() ?? 'menunggu',
    latitude: (json['latitude'] as num?)?.toDouble(), longitude: (json['longitude'] as num?)?.toDouble(),
    verifiedAt: json['verified_at'] != null ? DateTime.tryParse(json['verified_at'].toString()) : null,
    initials: json['initials']?.toString(),
  );

  bool get isVerified => verifiedAt != null;
  bool get isBlocked => status == 'diblokir';
  bool get isProfileComplete => name != null && name!.isNotEmpty && latitude != null;
  String get displayAvatar => initials ?? (name?.isNotEmpty == true ? name!.substring(0, 1).toUpperCase() : '?');
}
