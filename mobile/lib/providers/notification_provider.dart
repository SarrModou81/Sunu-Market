import 'package:flutter/foundation.dart';

import '../models/app_notification.dart';
import '../services/notification_service.dart';

class NotificationProvider extends ChangeNotifier {
  NotificationProvider(this._service);

  final NotificationService _service;

  List<AppNotification> items = [];
  int unreadCount = 0;
  bool isLoading = false;

  Future<void> refresh() async {
    isLoading = true;
    notifyListeners();
    try {
      final (result, unread) = await _service.list();
      items = result.items;
      unreadCount = unread;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> markRead(AppNotification notification) async {
    if (notification.isRead) return;
    await _service.markRead(notification.id);
    unreadCount = unreadCount > 0 ? unreadCount - 1 : 0;
    await refresh();
  }

  Future<void> markAllRead() async {
    await _service.markAllRead();
    unreadCount = 0;
    await refresh();
  }
}
