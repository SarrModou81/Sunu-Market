import '../core/api/api_client.dart';

class ReportService {
  ReportService(this._client);

  final ApiClient _client;

  Future<void> report({
    required String reportableType, // 'product' ou 'user'
    required int reportableId,
    required String reason,
    String? message,
  }) async {
    await _client.post(
      '/reports',
      data: {
        'reportable_type': reportableType,
        'reportable_id': reportableId,
        'reason': reason,
        if (message != null && message.isNotEmpty) 'message': message,
      },
    );
  }
}
