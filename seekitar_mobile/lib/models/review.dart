import 'package:freezed_annotation/freezed_annotation.dart';
part 'review.freezed.dart';
part 'review.g.dart';

@freezed
class Review with _$Review {
  const factory Review({
    required String id,
    required int rating,
    String? comment,
    @JsonKey(name: 'reviewer_name') String? reviewerName,
    @JsonKey(name: 'reviewer_initials') String? reviewerInitials,
    @JsonKey(name: 'created_at') required DateTime createdAt,
  }) = _Review;

  factory Review.fromJson(Map<String, dynamic> json) => _$ReviewFromJson(json);
}
