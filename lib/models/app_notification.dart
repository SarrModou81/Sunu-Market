class AppNotification {
  const AppNotification({
    required this.id,
    required this.type,
    required this.data,
    this.readAt,
    required this.createdAt,
  });

  /// UUID de la notification Laravel.
  final String id;

  /// Type applicatif (ex: "new_message", "boost_activated"), tiré de `data.type`.
  final String type;

  final Map<String, dynamic> data;
  final DateTime? readAt;
  final DateTime createdAt;

  bool get isRead => readAt != null;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] as Map<String, dynamic>?) ?? {};

    return AppNotification(
      id: json['id'] as String,
      type: data['type'] as String? ?? 'unknown',
      data: data,
      readAt: json['read_at'] != null
          ? DateTime.tryParse(json['read_at'] as String)
          : null,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
