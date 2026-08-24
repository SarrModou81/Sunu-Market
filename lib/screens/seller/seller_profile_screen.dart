import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/product.dart';
import '../../models/product_filters.dart';
import '../../models/review.dart';
import '../../providers/auth_provider.dart';
import '../../services/product_service.dart';
import '../../services/review_service.dart';
import '../../widgets/common.dart';
import '../../widgets/product_card.dart';

class SellerProfileScreen extends StatefulWidget {
  const SellerProfileScreen({super.key, required this.sellerId});

  final int sellerId;

  @override
  State<SellerProfileScreen> createState() => _SellerProfileScreenState();
}

class _SellerProfileScreenState extends State<SellerProfileScreen> {
  List<ProductSummary>? _products;
  List<Review>? _reviews;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final reviews = await context.read<ReviewService>().forSeller(
        widget.sellerId,
      );
      if (mounted) setState(() => _reviews = reviews.items);
    } catch (_) {
      if (mounted) setState(() => _reviews = []);
    }
    try {
      final result = await context.read<ProductService>().search(
        ProductFilters(sellerId: widget.sellerId, sort: 'date'),
        page: 1,
      );
      if (mounted) setState(() => _products = result.items);
    } catch (_) {
      if (mounted) setState(() => _products = []);
    }
  }

  Future<void> _leaveReview() async {
    final result = await showDialog<(int, String)>(
      context: context,
      builder: (context) => const _ReviewDialog(),
    );
    if (result == null) return;

    try {
      await context.read<ReviewService>().create(
        widget.sellerId,
        rating: result.$1,
        comment: result.$2,
      );
      if (mounted)
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Avis publié, merci !')));
      _load();
    } on ApiException catch (e) {
      if (mounted)
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSelf =
        context.watch<AuthProvider>().currentUser?.id == widget.sellerId;

    return Scaffold(
      appBar: AppBar(title: const Text('Profil vendeur')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_reviews == null)
              const LoadingView()
            else ...[
              _Header(
                sellerId: widget.sellerId,
                ratingAverage: _averageRating(),
                ratingCount: _reviews!.length,
              ),
              const SizedBox(height: 16),
              if (!isSelf)
                OutlinedButton.icon(
                  onPressed: _leaveReview,
                  icon: const Icon(Icons.star_outline),
                  label: const Text('Laisser un avis'),
                ),
              const SizedBox(height: 20),
              const Text(
                'Annonces',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              if (_products == null)
                const LoadingView()
              else if (_products!.isEmpty)
                const Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Aucune annonce active.'),
                )
              else
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 12,
                    crossAxisSpacing: 12,
                    childAspectRatio: 0.68,
                  ),
                  itemCount: _products!.length,
                  itemBuilder: (context, index) {
                    final product = _products![index];
                    return ProductCard(
                      product: product,
                      onTap: () => context.push('/product/${product.id}'),
                    );
                  },
                ),
              const SizedBox(height: 20),
              const Text(
                'Avis',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              if (_reviews!.isEmpty)
                const Text(
                  'Aucun avis pour le moment.',
                  style: TextStyle(color: AppColors.textSecondary),
                )
              else
                ..._reviews!.map((review) => _ReviewTile(review: review)),
            ],
          ],
        ),
      ),
    );
  }

  double _averageRating() {
    if (_reviews == null || _reviews!.isEmpty) return 0;
    return _reviews!.map((r) => r.rating).reduce((a, b) => a + b) /
        _reviews!.length;
  }
}

class _Header extends StatelessWidget {
  const _Header({
    required this.sellerId,
    required this.ratingAverage,
    required this.ratingCount,
  });

  final int sellerId;
  final double ratingAverage;
  final int ratingCount;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        const CircleAvatar(radius: 32, child: Icon(Icons.person, size: 32)),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  RatingStars(rating: ratingAverage),
                  const SizedBox(width: 6),
                  Text('($ratingCount avis)'),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _ReviewTile extends StatelessWidget {
  const _ReviewTile({required this.review});

  final Review review;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 16,
            backgroundImage: review.author?.avatarUrl != null
                ? CachedNetworkImageProvider(review.author!.avatarUrl!)
                : null,
            child: review.author?.avatarUrl == null
                ? const Icon(Icons.person, size: 16)
                : null,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      review.author?.name ?? 'Utilisateur',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(width: 8),
                    RatingStars(rating: review.rating.toDouble(), size: 12),
                  ],
                ),
                if (review.comment != null && review.comment!.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(review.comment!),
                ],
                const SizedBox(height: 2),
                Text(
                  formatRelativeDate(review.createdAt),
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ReviewDialog extends StatefulWidget {
  const _ReviewDialog();

  @override
  State<_ReviewDialog> createState() => _ReviewDialogState();
}

class _ReviewDialogState extends State<_ReviewDialog> {
  int _rating = 5;
  final _commentController = TextEditingController();

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Votre avis'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          RatingInput(
            rating: _rating,
            onChanged: (v) => setState(() => _rating = v),
          ),
          TextField(
            controller: _commentController,
            maxLines: 3,
            decoration: const InputDecoration(
              hintText: 'Commentaire (facultatif)',
              border: OutlineInputBorder(),
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Annuler'),
        ),
        ElevatedButton(
          onPressed: () =>
              Navigator.of(context).pop((_rating, _commentController.text)),
          child: const Text('Publier'),
        ),
      ],
    );
  }
}
