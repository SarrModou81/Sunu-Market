import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

import '../core/api/api_client.dart';
import '../models/product.dart';
import '../models/product_filters.dart';
import 'pagination.dart';

class ProductService {
  ProductService(this._client);

  final ApiClient _client;

  Future<PaginatedResult<ProductSummary>> search(
    ProductFilters filters, {
    int page = 1,
  }) async {
    final response = await _client.get(
      '/products',
      query: {...filters.toQuery(), 'page': page},
    );
    return parsePaginated(
      response.data as Map<String, dynamic>,
      ProductSummary.fromJson,
    );
  }

  Future<PaginatedResult<ProductSummary>> myProducts({int page = 1}) async {
    final response = await _client.get('/my/products', query: {'page': page});
    return parsePaginated(
      response.data as Map<String, dynamic>,
      ProductSummary.fromJson,
    );
  }

  Future<PaginatedResult<ProductSummary>> favorites({int page = 1}) async {
    final response = await _client.get('/favorites', query: {'page': page});
    return parsePaginated(
      response.data as Map<String, dynamic>,
      ProductSummary.fromJson,
    );
  }

  Future<Product> show(int id) async {
    final response = await _client.get('/products/$id');
    return Product.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  /// Révèle le numéro de téléphone du vendeur (action "Appeler"). Nécessite
  /// d'être connecté ; incrémente le compteur de contacts côté serveur.
  Future<String> revealPhone(int productId) async {
    final response = await _client.post('/products/$productId/reveal-phone');
    return response.data['data']['phone'] as String;
  }

  Future<Product> create({
    required String title,
    required String description,
    required int categoryId,
    int? subcategoryId,
    required int price,
    required int quantity,
    required String condition,
    required int cityId,
    int? neighborhoodId,
    bool deliveryAvailable = false,
    String? brand,
    bool isDraft = false,
    List<XFile> images = const [],
  }) async {
    final formData = FormData.fromMap({
      'title': title,
      'description': description,
      'category_id': categoryId,
      if (subcategoryId != null) 'subcategory_id': subcategoryId,
      'price': price,
      'quantity': quantity,
      'condition': condition,
      'city_id': cityId,
      if (neighborhoodId != null) 'neighborhood_id': neighborhoodId,
      'delivery_available': deliveryAvailable,
      if (brand != null && brand.isNotEmpty) 'brand': brand,
      'is_draft': isDraft,
      'images': await Future.wait(
        images.map((x) => MultipartFile.fromFile(x.path, filename: x.name)),
      ),
    });

    final response = await _client.post('/products', data: formData);
    return Product.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Product> update(
    int id, {
    String? title,
    String? description,
    int? price,
    int? quantity,
    String? condition,
    String? status,
    bool? deliveryAvailable,
    List<XFile> newImages = const [],
  }) async {
    final formData = FormData.fromMap({
      if (title != null) 'title': title,
      if (description != null) 'description': description,
      if (price != null) 'price': price,
      if (quantity != null) 'quantity': quantity,
      if (condition != null) 'condition': condition,
      if (status != null) 'status': status,
      if (deliveryAvailable != null) 'delivery_available': deliveryAvailable,
      if (newImages.isNotEmpty)
        'new_images': await Future.wait(
          newImages.map(
            (x) => MultipartFile.fromFile(x.path, filename: x.name),
          ),
        ),
    });

    final response = await _client.post('/products/$id', data: formData);
    return Product.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<void> delete(int id) async {
    await _client.delete('/products/$id');
  }

  Future<void> deleteImage(int productId, int imageId) async {
    await _client.delete('/products/$productId/images/$imageId');
  }

  Future<void> setPrimaryImage(int productId, int imageId) async {
    await _client.post('/products/$productId/images/$imageId/primary');
  }

  Future<void> toggleFavorite(int productId, bool addToFavorites) async {
    if (addToFavorites) {
      await _client.post('/favorites', data: {'product_id': productId});
    } else {
      await _client.delete('/favorites/$productId');
    }
  }
}
