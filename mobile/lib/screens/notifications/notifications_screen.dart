import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/app_notification.dart';
import '../../providers/notification_provider.dart';
import '../../widgets/common.dart';

const _notificationLabels = {
  'new_message': 'Nouveau message',
  'product_favorited': 'Votre annonce a été ajoutée aux favoris',
  'new_review': 'Nouvel avis reçu',
  'payment_succeeded': 'Paiement réussi',
  'boost_activated': 'Boost activé',
  'boost_expiring_soon': 'Boost bientôt terminé',
  'product_approved': 'Annonce approuvée',
  'product_refused': 'Annonce refusée',
  'product_blocked': 'Annonce bloquée',
};

const _notificationIcons = {
  'new_message': Icons.chat_bubble_outline,
  'product_favorited': Icons.favorite_border,
  'new_review': Icons.star_border,
  'payment_succeeded': Icons.check_circle_outline,
  'boost_activated': Icons.rocket_launch_outlined,
  'boost_expiring_soon': Icons.timer_outlined,
  'product_approved': Icons.thumb_up_outlined,
  'product_refused': Icons.thumb_down_outlined,
  'product_blocked': Icons.block_outlined,
};

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<NotificationProvider>().refresh(),
    );
  }

  void _open(AppNotification notification) {
    context.read<NotificationProvider>().markRead(notification);

    switch (notification.type) {
      case 'new_message':
        final conversationId = notification.data['conversation_id'];
        if (conversationId != null) context.push('/messages/$conversationId');
        break;
      case 'product_favorited':
      case 'product_approved':
      case 'product_refused':
      case 'product_blocked':
        final productId = notification.data['product_id'];
        if (productId != null) context.push('/product/$productId');
        break;
      default:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<NotificationProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (provider.unreadCount > 0)
            TextButton(
              onPressed: () =>
                  context.read<NotificationProvider>().markAllRead(),
              child: const Text('Tout marquer lu'),
            ),
        ],
      ),
      body: provider.isLoading && provider.items.isEmpty
          ? const LoadingView()
          : provider.items.isEmpty
          ? const EmptyStateView(
              icon: Icons.notifications_none,
              message: 'Aucune notification.',
            )
          : RefreshIndicator(
              onRefresh: provider.refresh,
              child: ListView.separated(
                itemCount: provider.items.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final notification = provider.items[index];
                  return ListTile(
                    leading: CircleAvatar(
                      backgroundColor: notification.isRead
                          ? AppColors.background
                          : AppColors.primary.withValues(alpha: 0.1),
                      child: Icon(
                        _notificationIcons[notification.type] ??
                            Icons.notifications_none,
                        color: AppColors.primary,
                      ),
                    ),
                    title: Text(
                      _notificationLabels[notification.type] ?? 'Notification',
                      style: TextStyle(
                        fontWeight: notification.isRead
                            ? FontWeight.normal
                            : FontWeight.bold,
                      ),
                    ),
                    subtitle: Text(formatRelativeDate(notification.createdAt)),
                    onTap: () => _open(notification),
                  );
                },
              ),
            ),
    );
  }
}
