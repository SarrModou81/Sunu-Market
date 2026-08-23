import '../core/api/api_client.dart';
import '../models/app_notification.dart';
import '../models/product.dart' show PaginatedResult;
import 'pagination.dart';

class NotificationService {
  NotificationService(this._client);

  final ApiClient _client;

  Future<(PaginatedResult<AppNotification>, int unreadCount)> list({
    int page = 1,
  }) async {
    final response = await _client.get('/notifications', query: {'page': page});
    final data = response.data as Map<String, dynamic>;
    return (
      parsePaginated(data, AppNotification.fromJson),
      data['unread_count'] as int? ?? 0,
    );
  }

  Future<void> markRead(String id) async {
    await _client.post('/notifications/$id/read');
  }

  Future<void> markAllRead() async {
    await _client.post('/notifications/read-all');
  }
}
