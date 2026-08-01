class StoreDashboard {
  final int totalListings, totalOrders, completedOrders, pendingOrders;
  final double totalRevenue, avgRating;
  final int totalReviews, ordersThisMonth;
  final double revenueThisMonth;
  final bool isVerified, isActive;

  StoreDashboard({this.totalListings = 0, this.totalOrders = 0, this.completedOrders = 0, this.pendingOrders = 0, this.totalRevenue = 0, this.avgRating = 0, this.totalReviews = 0, this.ordersThisMonth = 0, this.revenueThisMonth = 0, this.isVerified = false, this.isActive = true});

  factory StoreDashboard.fromJson(Map<String, dynamic> json) => StoreDashboard(
    totalListings: json['total_listings'] ?? 0, totalOrders: json['total_orders'] ?? 0,
    completedOrders: json['completed_orders'] ?? 0, pendingOrders: json['pending_orders'] ?? 0,
    totalRevenue: (json['total_revenue'] as num?)?.toDouble() ?? 0,
    avgRating: (json['avg_rating'] as num?)?.toDouble() ?? 0,
    totalReviews: json['total_reviews'] ?? 0, ordersThisMonth: json['orders_this_month'] ?? 0,
    revenueThisMonth: (json['revenue_this_month'] as num?)?.toDouble() ?? 0,
    isVerified: json['is_verified'] ?? false, isActive: json['is_active'] ?? true,
  );
}
