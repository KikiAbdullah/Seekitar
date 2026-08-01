class Review {
  final String id;
  final int rating;
  final String? comment;
  final String? reviewerName, reviewerInitials;
  final DateTime createdAt;
  Review({required this.id, required this.rating, this.comment, this.reviewerName, this.reviewerInitials, required this.createdAt});

  factory Review.fromJson(Map<String, dynamic> json) => Review(
    id: json['id']?.toString() ?? '', rating: json['rating'] ?? 0,
    comment: json['comment']?.toString(),
    reviewerName: json['reviewer']?['name']?.toString(), reviewerInitials: json['reviewer']?['initials']?.toString(),
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
  );
}
