import 'package:freezed_annotation/freezed_annotation.dart';
import '../core/constants.dart';
part 'wallet.freezed.dart';
part 'wallet.g.dart';

@freezed
class Wallet with _$Wallet {
  const factory Wallet({
    required String id,
    @Default(0) double balance,
    @JsonKey(name: 'total_topup') @Default(0) int totalTopup,
    @JsonKey(name: 'total_withdraw') @Default(0) int totalWithdraw,
  }) = _Wallet;

  factory Wallet.fromJson(Map<String, dynamic> json) => _$WalletFromJson(json);
}

extension WalletX on Wallet {
  String get balanceDisplay => AppConstants.formatRupiah(balance);
}

@freezed
class WalletTransaction with _$WalletTransaction {
  const factory WalletTransaction({
    required String id,
    required String type,
    required double amount,
    String? description,
    required String status,
    @JsonKey(name: 'created_at') required DateTime createdAt,
  }) = _WalletTransaction;

  factory WalletTransaction.fromJson(Map<String, dynamic> json) => _$WalletTransactionFromJson(json);
}

extension WalletTransactionX on WalletTransaction {
  String get amountDisplay {
    final prefix = type == 'withdrawal' ? '- ' : '+ ';
    return '$prefix${AppConstants.formatRupiah(amount.abs())}';
  }
}
