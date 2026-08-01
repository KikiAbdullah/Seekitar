import 'package:freezed_annotation/freezed_annotation.dart';
import '../core/constants.dart';
part 'user.freezed.dart';
part 'user.g.dart';

@freezed
class User with _$User {
  const factory User({
    required String id,
    String? name,
    required String phone,
    String? email,
    @JsonKey(name: 'avatar_url') String? avatarUrl,
    String? address,
    required String status,
    double? latitude,
    double? longitude,
    @JsonKey(name: 'verified_at') DateTime? verifiedAt,
    String? initials,
  }) = _User;

  factory User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);
}

extension UserX on User {
  bool get isVerified => verifiedAt != null;
  bool get isBlocked => status == 'diblokir';
  bool get isProfileComplete => name != null && name!.isNotEmpty && latitude != null;
  String get displayAvatar => initials ?? (name?.isNotEmpty == true ? name!.substring(0, 1).toUpperCase() : '?');
}
