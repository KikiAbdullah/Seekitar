import 'package:freezed_annotation/freezed_annotation.dart';
part 'dashboard.freezed.dart';
part 'dashboard.g.dart';

@freezed
class StoreDashboard with _$StoreDashboard {
  const factory StoreDashboard({
    @JsonKey(name: 'total_listings') @Default(0) int totalListings,
    @JsonKey(name: 'total_orders') @Default(0) int totalOrders,
    @JsonKey(name: 'completed_orders') @Default(0) int completedOrders,
    @JsonKey(name: 'pending_orders') @Default(0) int pendingOrders,
    @JsonKey(name: 'total_revenue') @Default(0) double totalRevenue,
    @JsonKey(name: 'avg_rating') @Default(0) double avgRating,
    @JsonKey(name: 'total_reviews') @Default(0) int totalReviews,
    @JsonKey(name: 'orders_this_month') @Default(0) int ordersThisMonth,
    @JsonKey(name: 'revenue_this_month') @Default(0) double revenueThisMonth,
    @JsonKey(name: 'is_verified') @Default(false) bool isVerified,
    @JsonKey(name: 'is_active') @Default(true) bool isActive,
  }) = _StoreDashboard;

  factory StoreDashboard.fromJson(Map<String, dynamic> json) => _$StoreDashboardFromJson(json);
}
