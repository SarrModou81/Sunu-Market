import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/product.dart';
import '../../services/product_service.dart';
import '../../widgets/common.dart';

class MyProductsScreen extends StatefulWidget {
  const MyProductsScreen({super.key});

  @override
  State<MyProductsScreen> createState() => _MyProductsScreenState();
}

class _MyProductsScreenState extends State<MyProductsScreen> {
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
      final result = await context.read<ProductService>().myProducts();
      if (mounted) setState(() => _items = result.items);
    } catch (_) {
      if (mounted)
        setState(() => _error = 'Impossible de charger vos annonces.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes annonces')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/publish'),
        icon: const Icon(Icons.add),
        label: const Text('Publier'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_error != null) return ErrorView(message: _error!, onRetry: _load);
    if (_items == null) return const LoadingView();
    if (_items!.isEmpty) {
      return EmptyStateView(
        icon: Icons.inventory_2_outlined,
        message: "Vous n'avez publié aucune annonce.",
        actionLabel: 'Publier une annonce',
        onAction: () => context.push('/publish'),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(12),
        itemCount: _items!.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final product = _items![index];
          return Card(
            margin: EdgeInsets.zero,
            child: ListTile(
              leading: ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: product.coverImageUrl != null
                    ? Image.network(
                        product.coverImageUrl!,
                        width: 56,
                        height: 56,
                        fit: BoxFit.cover,
                      )
                    : Container(
                        width: 56,
                        height: 56,
                        color: AppColors.background,
                        child: const Icon(Icons.image_outlined),
                      ),
              ),
              title: Text(
                product.title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              subtitle: Text(
                '${formatPrice(product.price)} · ${product.status}',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () async {
                await context.push('/product/${product.id}/edit');
                _load();
              },
            ),
          );
        },
      ),
    );
  }
}
