import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

import '../core/api/api_client.dart';
import '../models/conversation.dart';
import '../models/product.dart';
import 'pagination.dart';

class ConversationService {
  ConversationService(this._client);

  final ApiClient _client;

  Future<PaginatedResult<Conversation>> list({int page = 1}) async {
    final response = await _client.get('/conversations', query: {'page': page});
    return parsePaginated(
      response.data as Map<String, dynamic>,
      Conversation.fromJson,
    );
  }

  Future<(Conversation, List<Message>)> show(int id) async {
    final response = await _client.get('/conversations/$id');
    final conversation = Conversation.fromJson(
      response.data['data'] as Map<String, dynamic>,
    );
    final messages = (response.data['messages'] as List<dynamic>)
        .map((e) => Message.fromJson(e as Map<String, dynamic>))
        .toList();
    return (conversation, messages);
  }

  Future<Conversation> start({
    required int productId,
    required String message,
  }) async {
    final response = await _client.post(
      '/conversations',
      data: {'product_id': productId, 'message': message},
    );
    return Conversation.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Message> sendMessage(
    int conversationId, {
    String? body,
    XFile? attachment,
  }) async {
    final data = attachment != null
        ? FormData.fromMap({
            if (body != null && body.isNotEmpty) 'body': body,
            'attachment': await MultipartFile.fromFile(
              attachment.path,
              filename: attachment.name,
            ),
          })
        : {'body': body};

    final response = await _client.post(
      '/conversations/$conversationId/messages',
      data: data,
    );
    return Message.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Conversation> toggleBlock(int conversationId) async {
    final response = await _client.post('/conversations/$conversationId/block');
    return Conversation.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}
