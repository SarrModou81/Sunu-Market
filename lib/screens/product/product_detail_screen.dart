import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/product.dart';
import '../../models/user.dart';
import '../../providers/auth_provider.dart';
import '../../services/conversation_service.dart';
import '../../services/product_service.dart';
import '../../services/report_service.dart';
import '../../widgets/common.dart';
import '../../widgets/report_dialog.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({super.key, required this.productId});

  final int productId;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  Product? _product;
  String? _error;
  bool _favoriteBusy = false;
  final _pageController = PageController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final product = await context.read<ProductService>().show(
        widget.productId,
      );
      if (mounted) setState(() => _product = product);
    } catch (_) {
      if (mounted)
        setState(() => _error = "Cette annonce n'est plus disponible.");
    }
  }

  Future<void> _toggleFavorite() async {
    final product = _product;
    if (product == null) return;
    if (!context.read<AuthProvider>().isAuthenticated) {
      context.push('/login');
      return;
    }

    setState(() => _favoriteBusy = true);
    final newValue = !(product.isFavorited ?? false);
    try {
      await context.read<ProductService>().toggleFavorite(product.id, newValue);
      await _load();
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Action impossible pour le moment.')),
        );
      }
    } finally {
      if (mounted) setState(() => _favoriteBusy = false);
    }
  }

  Future<void> _call() async {
    final product = _product;
    if (product == null) return;
    if (!context.read<AuthProvider>().isAuthenticated) {
      context.push(
        '/login?redirect=${Uri.encodeComponent('/product/${product.id}')}',
      );
      return;
    }

    try {
      final phone = await context.read<ProductService>().revealPhone(
        product.id,
      );
      await launchUrl(Uri.parse('tel:$phone'));
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible de récupérer le numéro.')),
        );
      }
    }
  }

  Future<void> _contactSeller() async {
    final product = _product;
    if (product == null) return;
    if (!context.read<AuthProvider>().isAuthenticated) {
      context.push(
        '/login?redirect=${Uri.encodeComponent('/product/${product.id}')}',
      );
      return;
    }

    final message = await showDialog<String>(
      context: context,
      builder: (context) => _ContactDialog(productTitle: product.title),
    );
    if (message == null || message.trim().isEmpty) return;

    try {
      final conversation = await context.read<ConversationService>().start(
        productId: product.id,
        message: message,
      );
      if (!mounted) return;
      context.push('/messages/${conversation.id}');
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Impossible d'envoyer le message.")),
        );
      }
    }
  }

  Future<void> _report() async {
    final product = _product;
    if (product == null) return;
    if (!context.read<AuthProvider>().isAuthenticated) {
      context.push('/login');
      return;
    }

    final result = await showDialog<ReportDialogResult>(
      context: context,
      builder: (context) => const ReportDialog(),
    );
    if (result == null) return;

    try {
      await context.read<ReportService>().report(
        reportableType: 'product',
        reportableId: product.id,
        reason: result.reason,
        message: result.message,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Signalement envoyé, merci.')),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible d’envoyer le signalement.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Scaffold(
        appBar: AppBar(),
        body: ErrorView(message: _error!, onRetry: _load),
      );
    }
    if (_product == null) {
      return const Scaffold(body: LoadingView());
    }

    final product = _product!;
    final isOwner =
        context.watch<AuthProvider>().currentUser?.id == product.seller?.id;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 320,
            pinned: true,
            actions: [
              IconButton(
                icon: const Icon(Icons.share_outlined),
                onPressed: () {},
              ),
              if (!isOwner)
                IconButton(
                  icon: const Icon(Icons.flag_outlined),
                  onPressed: _report,
                ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: product.images.isEmpty
                  ? Container(
                      color: AppColors.background,
                      child: const Icon(Icons.image_outlined, size: 64),
                    )
                  : PageView.builder(
                      controller: _pageController,
                      itemCount: product.images.length,
                      itemBuilder: (context, index) => CachedNetworkImage(
                        imageUrl: product.images[index].url,
                        fit: BoxFit.cover,
                      ),
                    ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          formatPrice(product.price),
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      if (!isOwner)
                        IconButton(
                          onPressed: _favoriteBusy ? null : _toggleFavorite,
                          icon: Icon(
                            (product.isFavorited ?? false)
                                ? Icons.favorite
                                : Icons.favorite_border,
                            color: (product.isFavorited ?? false)
                                ? AppColors.danger
                                : null,
                          ),
                        ),
                    ],
                  ),
                  if (product.status != 'ACTIVE')
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Chip(label: Text('Statut : ${product.status}')),
                    ),
                  const SizedBox(height: 4),
                  Text(
                    product.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    children: [
                      Chip(
                        label: Text(
                          productConditionLabels[product.condition] ??
                              product.condition,
                        ),
                      ),
                      Chip(label: Text('Qté : ${product.quantity}')),
                      if (product.deliveryAvailable)
                        const Chip(label: Text('Livraison possible')),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(
                        Icons.place_outlined,
                        size: 16,
                        color: AppColors.textSecondary,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        [
                          product.neighborhood?.name,
                          product.city?.name,
                        ].whereType<String>().join(', '),
                        style: const TextStyle(color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                  const Divider(height: 32),
                  const Text(
                    'Description',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  Text(product.description),
                  const Divider(height: 32),
                  if (product.seller != null)
                    _SellerCard(seller: product.seller!),
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: isOwner
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    if (product.seller != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _call,
                          icon: const Icon(Icons.call_outlined),
                          label: const Text('Appeler'),
                        ),
                      ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: ElevatedButton.icon(
                        onPressed: _contactSeller,
                        icon: const Icon(Icons.chat_bubble_outline),
                        label: const Text('Contacter le vendeur'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

class _SellerCard extends StatelessWidget {
  const _SellerCard({required this.seller});

  final SellerSummary seller;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => context.push('/seller/${seller.id}'),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundImage: seller.avatarUrl != null
                    ? CachedNetworkImageProvider(seller.avatarUrl!)
                    : null,
                child: seller.avatarUrl == null
                    ? const Icon(Icons.person)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            seller.name ?? 'Vendeur',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                        ),
                        if (seller.badgeVerified) ...[
                          const SizedBox(width: 4),
                          const VerifiedBadge(size: 14),
                        ],
                        if (seller.isPro) ...[
                          const SizedBox(width: 4),
                          const ProBadge(),
                        ],
                      ],
                    ),
                    Row(
                      children: [
                        RatingStars(rating: seller.ratingAverage, size: 14),
                        const SizedBox(width: 4),
                        Text(
                          '(${seller.ratingCount})',
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}

class _ContactDialog extends StatefulWidget {
  const _ContactDialog({required this.productTitle});

  final String productTitle;

  @override
  State<_ContactDialog> createState() => _ContactDialogState();
}

class _ContactDialogState extends State<_ContactDialog> {
  late final _controller = TextEditingController(
    text:
        'Bonjour, votre annonce "${widget.productTitle}" est-elle toujours disponible ?',
  );

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Contacter le vendeur'),
      content: TextField(
        controller: _controller,
        maxLines: 3,
        decoration: const InputDecoration(border: OutlineInputBorder()),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Annuler'),
        ),
        ElevatedButton(
          onPressed: () => Navigator.of(context).pop(_controller.text),
          child: const Text('Envoyer'),
        ),
      ],
    );
  }
}
