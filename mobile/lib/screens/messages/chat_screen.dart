import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/conversation.dart';
import '../../services/conversation_service.dart';
import '../../widgets/common.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key, required this.conversationId});

  final int conversationId;

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  Conversation? _conversation;
  final List<Message> _messages = [];
  final _textController = TextEditingController();
  String? _error;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final (conversation, messages) = await context
          .read<ConversationService>()
          .show(widget.conversationId);
      if (!mounted) return;
      setState(() {
        _conversation = conversation;
        _messages
          ..clear()
          ..addAll(messages);
      });
    } catch (_) {
      if (mounted)
        setState(() => _error = 'Impossible de charger la conversation.');
    }
  }

  Future<void> _send({XFile? attachment}) async {
    final body = _textController.text.trim();
    if (body.isEmpty && attachment == null) return;

    setState(() => _sending = true);
    try {
      final message = await context.read<ConversationService>().sendMessage(
        widget.conversationId,
        body: body.isEmpty ? null : body,
        attachment: attachment,
      );
      setState(() {
        _messages.add(message);
        _textController.clear();
      });
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Message non envoyé.')));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _toggleBlock() async {
    try {
      final updated = await context.read<ConversationService>().toggleBlock(
        widget.conversationId,
      );
      setState(() => _conversation = updated);
    } catch (_) {
      // silencieux
    }
  }

  @override
  Widget build(BuildContext context) {
    final conversation = _conversation;

    return Scaffold(
      appBar: AppBar(
        title: Text(conversation?.otherUser?.name ?? 'Conversation'),
        actions: [
          if (conversation != null)
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'block') _toggleBlock();
                if (value == 'seller' && conversation.otherUser != null) {
                  context.push('/seller/${conversation.otherUser!.id}');
                }
              },
              itemBuilder: (context) => [
                const PopupMenuItem(
                  value: 'seller',
                  child: Text('Voir le profil'),
                ),
                PopupMenuItem(
                  value: 'block',
                  child: Text(conversation.isBlocked ? 'Débloquer' : 'Bloquer'),
                ),
              ],
            ),
        ],
      ),
      body: Column(
        children: [
          if (conversation?.product != null)
            Material(
              color: AppColors.background,
              child: InkWell(
                onTap: () =>
                    context.push('/product/${conversation.product!.id}'),
                child: Padding(
                  padding: const EdgeInsets.all(8),
                  child: Row(
                    children: [
                      if (conversation!.product!.coverImageUrl != null)
                        ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: Image.network(
                            conversation.product!.coverImageUrl!,
                            width: 36,
                            height: 36,
                            fit: BoxFit.cover,
                          ),
                        ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${conversation.product!.title} · ${formatPrice(conversation.product!.price)}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          Expanded(child: _buildMessages()),
          if (conversation?.isBlocked ?? false)
            const Padding(
              padding: EdgeInsets.all(12),
              child: Text(
                'Cette conversation est bloquée.',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            )
          else
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.photo_camera_outlined),
                      onPressed: () async {
                        final file = await ImagePicker().pickImage(
                          source: ImageSource.gallery,
                        );
                        if (file != null) _send(attachment: file);
                      },
                    ),
                    Expanded(
                      child: TextField(
                        controller: _textController,
                        decoration: const InputDecoration(
                          hintText: 'Votre message...',
                        ),
                        minLines: 1,
                        maxLines: 4,
                      ),
                    ),
                    IconButton(
                      icon: _sending
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send, color: AppColors.primary),
                      onPressed: _sending ? null : () => _send(),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildMessages() {
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_conversation == null) return const LoadingView();

    return ListView.builder(
      reverse: true,
      padding: const EdgeInsets.all(12),
      itemCount: _messages.length,
      itemBuilder: (context, index) {
        final message = _messages[_messages.length - 1 - index];
        return Align(
          alignment: message.isMine
              ? Alignment.centerRight
              : Alignment.centerLeft,
          child: Container(
            margin: const EdgeInsets.symmetric(vertical: 4),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.7,
            ),
            decoration: BoxDecoration(
              color: message.isMine ? AppColors.primary : AppColors.surface,
              borderRadius: BorderRadius.circular(12),
              border: message.isMine
                  ? null
                  : Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (message.attachmentUrl != null)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.network(message.attachmentUrl!, width: 180),
                  ),
                if (message.body != null && message.body!.isNotEmpty)
                  Text(
                    message.body!,
                    style: TextStyle(
                      color: message.isMine
                          ? Colors.white
                          : AppColors.textPrimary,
                    ),
                  ),
                const SizedBox(height: 2),
                Text(
                  formatRelativeDate(message.createdAt),
                  style: TextStyle(
                    fontSize: 10,
                    color: message.isMine
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
