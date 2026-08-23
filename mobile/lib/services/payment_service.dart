import '../core/api/api_client.dart';
import '../models/boost_plan.dart';
import '../models/payment.dart';
import '../models/product.dart' show PaginatedResult;
import 'pagination.dart';

class PaymentService {
  PaymentService(this._client);

  final ApiClient _client;

  Future<List<BoostPlan>> boostPlans() async {
    final response = await _client.get('/boost-plans');
    return (response.data['data'] as List<dynamic>)
        .map((e) => BoostPlan.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<Payment> boostProduct(
    int productId, {
    required int boostPlanId,
    required String provider,
  }) async {
    final response = await _client.post(
      '/products/$productId/boost',
      data: {'boost_plan_id': boostPlanId, 'provider': provider},
    );
    return Payment.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Payment> subscribePro({required String provider}) async {
    final response = await _client.post(
      '/subscriptions/pro',
      data: {'provider': provider},
    );
    return Payment.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Payment> show(int paymentId) async {
    final response = await _client.get('/payments/$paymentId');
    return Payment.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<PaginatedResult<Payment>> history({int page = 1}) async {
    final response = await _client.get('/payments', query: {'page': page});
    return parsePaginated(
      response.data as Map<String, dynamic>,
      Payment.fromJson,
    );
  }
}
