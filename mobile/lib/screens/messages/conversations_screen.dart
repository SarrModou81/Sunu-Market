import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/conversation.dart';
import '../../services/conversation_service.dart';
import '../../widgets/common.dart';

class ConversationsScreen extends StatefulWidget {
  const ConversationsScreen({super.key});

  @override
  State<ConversationsScreen> createState() => _ConversationsScreenState();
}

class _ConversationsScreenState extends State<ConversationsScreen> {
  List<Conversation>? _items;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final result = await context.read<ConversationService>().list();
      if (mounted) setState(() => _items = result.items);
    } catch (_) {
      if (mounted)
        setState(() => _error = 'Impossible de charger vos messages.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_items == null) return const LoadingView();
    if (_items!.isEmpty) {
      return const EmptyStateView(
        icon: Icons.chat_bubble_outline,
        message: 'Aucune conversation pour le moment.',
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        itemCount: _items!.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (context, index) {
          final conversation = _items![index];
          final other = conversation.otherUser;

          return ListTile(
            leading: CircleAvatar(
              backgroundImage: other?.avatarUrl != null
                  ? CachedNetworkImageProvider(other!.avatarUrl!)
                  : null,
              child: other?.avatarUrl == null ? const Icon(Icons.person) : null,
            ),
            title: Text(
              other?.name ?? 'Utilisateur',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            subtitle: Text(
              conversation.lastMessage?.body ??
                  conversation.product?.title ??
                  '',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (conversation.lastMessageAt != null)
                  Text(
                    formatRelativeDate(conversation.lastMessageAt!),
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                if (conversation.unreadCount > 0)
                  Container(
                    margin: const EdgeInsets.only(top: 4),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 7,
                      vertical: 2,
                    ),
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      '${conversation.unreadCount}',
                      style: const TextStyle(color: Colors.white, fontSize: 11),
                    ),
                  ),
              ],
            ),
            onTap: () async {
              await context.push('/messages/${conversation.id}');
              _load();
            },
          );
        },
      ),
    );
  }
}
