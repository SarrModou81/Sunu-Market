import '../core/api/api_client.dart';
import '../models/category.dart';
import '../models/city.dart';

class CatalogService {
  CatalogService(this._client);

  final ApiClient _client;

  Future<List<Category>> categories() async {
    final response = await _client.get('/categories');
    return (response.data['data'] as List<dynamic>)
        .map((e) => Category.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<City>> cities() async {
    final response = await _client.get('/cities');
    return (response.data['data'] as List<dynamic>)
        .map((e) => City.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
