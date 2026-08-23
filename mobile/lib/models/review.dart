import 'user.dart';

class Review {
  const Review({
    required this.id,
    this.author,
    required this.rating,
    this.comment,
    required this.createdAt,
  });

  final int id;
  final SellerSummary? author;
  final int rating;
  final String? comment;
  final DateTime createdAt;

  factory Review.fromJson(Map<String, dynamic> json) => Review(
    id: json['id'] as int,
    author: json['author'] != null && (json['author'] as Map).isNotEmpty
        ? SellerSummary.fromJson(json['author'] as Map<String, dynamic>)
        : null,
    rating: json['rating'] as int,
    comment: json['comment'] as String?,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}
