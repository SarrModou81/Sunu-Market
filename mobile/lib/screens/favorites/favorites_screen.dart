import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../models/product.dart';
import '../../services/product_service.dart';
import '../../widgets/common.dart';
import '../../widgets/product_card.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  List<ProductSummary>? _items;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _error = null);
    try {
      final result = await context.read<ProductService>().favorites();
      if (mounted) setState(() => _items = result.items);
    } catch (_) {
      if (mounted)
        setState(() => _error = 'Impossible de charger vos favoris.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Favoris')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_items == null) return const LoadingView();
    if (_items!.isEmpty) {
      return const EmptyStateView(
        icon: Icons.favorite_border,
        message: 'Aucun favori pour le moment.',
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: GridView.builder(
        padding: const EdgeInsets.all(12),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 0.68,
        ),
        itemCount: _items!.length,
        itemBuilder: (context, index) {
          final product = _items![index];
          return ProductCard(
            product: product,
            onTap: () async {
              await context.push('/product/${product.id}');
              _load();
            },
          );
        },
      ),
    );
  }
}
