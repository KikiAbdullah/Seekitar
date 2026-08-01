import '../core/constants.dart';

class Wallet {
  final String id;
  final double balance;
  final int totalTopup, totalWithdraw;
  Wallet({required this.id, this.balance = 0, this.totalTopup = 0, this.totalWithdraw = 0});

  factory Wallet.fromJson(Map<String, dynamic> json) => Wallet(
    id: json['id']?.toString() ?? '', balance: (json['balance'] as num?)?.toDouble() ?? 0,
    totalTopup: json['total_topup'] ?? 0, totalWithdraw: json['total_withdraw'] ?? 0,
  );
  String get balanceDisplay => AppConstants.formatRupiah(balance);
}

class WalletTransaction {
  final String id, type, status;
  final double amount;
  final String? description;
  final DateTime createdAt;
  WalletTransaction({required this.id, required this.type, required this.amount, this.description, required this.status, required this.createdAt});

  factory WalletTransaction.fromJson(Map<String, dynamic> json) => WalletTransaction(
    id: json['id']?.toString() ?? '', type: json['type']?.toString() ?? '',
    amount: (json['amount'] as num?)?.toDouble() ?? 0,
    description: json['description']?.toString(), status: json['status']?.toString() ?? '',
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
  );

  String get amountDisplay {
    final prefix = type == 'withdrawal' ? '- ' : '+ ';
    return '$prefix${AppConstants.formatRupiah(amount.abs())}';
  }
}
