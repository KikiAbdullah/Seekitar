import 'package:freezed_annotation/freezed_annotation.dart';
part 'address.freezed.dart';
part 'address.g.dart';

@freezed
class UserAddress with _$UserAddress {
  const factory UserAddress({
    required String id,
    required String label,
    required String address,
    String? note,
    required double latitude,
    required double longitude,
    @JsonKey(name: 'is_default') @Default(false) bool isDefault,
  }) = _UserAddress;

  factory UserAddress.fromJson(Map<String, dynamic> json) => _$UserAddressFromJson(json);
}
