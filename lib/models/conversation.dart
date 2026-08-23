import 'product.dart';
import 'user.dart';

class Message {
  const Message({
    required this.id,
    required this.conversationId,
    required this.senderId,
    required this.isMine,
    this.body,
    this.attachmentUrl,
    this.readAt,
    required this.createdAt,
  });

  final int id;
  final int conversationId;
  final int senderId;
  final bool isMine;
  final String? body;
  final String? attachmentUrl;
  final DateTime? readAt;
  final DateTime createdAt;

  factory Message.fromJson(Map<String, dynamic> json) => Message(
    id: json['id'] as int,
    conversationId: json['conversation_id'] as int,
    senderId: json['sender_id'] as int,
    isMine: json['is_mine'] as bool? ?? false,
    body: json['body'] as String?,
    attachmentUrl: json['attachment_url'] as String?,
    readAt: json['read_at'] != null
        ? DateTime.tryParse(json['read_at'] as String)
        : null,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}

class Conversation {
  const Conversation({
    required this.id,
    this.product,
    this.otherUser,
    required this.isBlocked,
    this.lastMessage,
    required this.unreadCount,
    this.lastMessageAt,
    required this.createdAt,
  });

  final int id;
  final ProductSummary? product;
  final SellerSummary? otherUser;
  final bool isBlocked;
  final Message? lastMessage;
  final int unreadCount;
  final DateTime? lastMessageAt;
  final DateTime createdAt;

  factory Conversation.fromJson(Map<String, dynamic> json) => Conversation(
    id: json['id'] as int,
    product: json['product'] != null && (json['product'] as Map).isNotEmpty
        ? ProductSummary.fromJson(json['product'] as Map<String, dynamic>)
        : null,
    otherUser:
        json['other_user'] != null && (json['other_user'] as Map).isNotEmpty
        ? SellerSummary.fromJson(json['other_user'] as Map<String, dynamic>)
        : null,
    isBlocked: json['is_blocked'] as bool? ?? false,
    lastMessage:
        json['last_message'] != null && (json['last_message'] as Map).isNotEmpty
        ? Message.fromJson(json['last_message'] as Map<String, dynamic>)
        : null,
    unreadCount: json['unread_count'] as int? ?? 0,
    lastMessageAt: json['last_message_at'] != null
        ? DateTime.tryParse(json['last_message_at'] as String)
        : null,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}
