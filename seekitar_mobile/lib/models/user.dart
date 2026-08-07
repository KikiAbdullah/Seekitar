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
  final DateTime? ktpSubmittedAt;
  final int verificationLevel;
  final String? initials;

  User({
    required this.id,
    this.name,
    required this.phone,
    this.email,
    this.avatarUrl,
    this.address,
    required this.status,
    this.latitude,
    this.longitude,
    this.verifiedAt,
    this.ktpSubmittedAt,
    this.verificationLevel = 1,
    this.initials,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    final loc = json['location'];
    double? lat, lng;
    if (loc is List && loc.length >= 2) {
      lng = (loc[0] as num?)?.toDouble();
      lat = (loc[1] as num?)?.toDouble();
    } else {
      lat = (json['latitude'] as num?)?.toDouble();
      lng = (json['longitude'] as num?)?.toDouble();
    }
    return User(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString(),
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString(),
      avatarUrl: json['avatar_url']?.toString(),
      address: json['address']?.toString(),
      status: json['status']?.toString() ?? 'menunggu',
      latitude: lat,
      longitude: lng,
      verifiedAt: json['verified_at'] != null ? DateTime.tryParse(json['verified_at'].toString()) : null,
      ktpSubmittedAt: json['ktp_submitted_at'] != null ? DateTime.tryParse(json['ktp_submitted_at'].toString()) : null,
      verificationLevel: (json['verification_level'] as num?)?.toInt() ?? 1,
      initials: json['initials']?.toString(),
    );
  }

  bool get isVerified => verifiedAt != null || verificationLevel >= 2;
  bool get isKtpPending => !isVerified && ktpSubmittedAt != null;
  bool get needsVerification => !isVerified && ktpSubmittedAt == null;
  bool get isBlocked => status == 'diblokir';
  bool get isProfileComplete => name != null && name!.isNotEmpty && latitude != null;
  String get displayAvatar => initials ?? (name?.isNotEmpty == true ? name!.substring(0, 1).toUpperCase() : '?');
}
