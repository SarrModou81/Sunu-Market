import '../core/api/api_client.dart';
import '../models/product.dart' show PaginatedResult;
import '../models/review.dart';
import 'pagination.dart';

class ReviewService {
  ReviewService(this._client);

  final ApiClient _client;

  Future<PaginatedResult<Review>> forSeller(
    int sellerId, {
    int page = 1,
  }) async {
    final response = await _client.get(
      '/sellers/$sellerId/reviews',
      query: {'page': page},
    );
    return parsePaginated(
      response.data as Map<String, dynamic>,
      Review.fromJson,
    );
  }

  Future<Review> create(
    int sellerId, {
    required int rating,
    String? comment,
    int? productId,
  }) async {
    final response = await _client.post(
      '/sellers/$sellerId/reviews',
      data: {
        'rating': rating,
        if (comment != null && comment.isNotEmpty) 'comment': comment,
        if (productId != null) 'product_id': productId,
      },
    );
    return Review.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}
