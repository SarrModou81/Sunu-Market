import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../models/product.dart';
import '../../models/product_filters.dart';
import '../../providers/catalog_provider.dart';
import '../../services/product_service.dart';
import '../../widgets/common.dart';
import '../../widgets/product_card.dart';
import 'filters_sheet.dart';

class SearchTab extends StatefulWidget {
  const SearchTab({super.key, this.initialFilters});

  final ProductFilters? initialFilters;

  @override
  State<SearchTab> createState() => _SearchTabState();
}

class _SearchTabState extends State<SearchTab> {
  final _queryController = TextEditingController();
  final _scrollController = ScrollController();

  late ProductFilters _filters =
      widget.initialFilters ?? const ProductFilters();
  final List<ProductSummary> _items = [];
  int _page = 1;
  int _lastPage = 1;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _queryController.text = _filters.query ?? '';
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CatalogProvider>().load();
      _search();
    });
  }

  @override
  void dispose() {
    _queryController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >
        _scrollController.position.maxScrollExtent - 300) {
      _loadMore();
    }
  }

  Future<void> _search() async {
    setState(() {
      _loading = true;
      _error = null;
      _page = 1;
      _items.clear();
    });

    try {
      final result = await context.read<ProductService>().search(
        _filters,
        page: 1,
      );
      setState(() {
        _items.addAll(result.items);
        _lastPage = result.lastPage;
      });
    } catch (_) {
      setState(() => _error = 'Impossible de charger les résultats.');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _loadMore() async {
    if (_loading || _page >= _lastPage) return;
    setState(() => _loading = true);
    try {
      final result = await context.read<ProductService>().search(
        _filters,
        page: _page + 1,
      );
      setState(() {
        _items.addAll(result.items);
        _page += 1;
      });
    } catch (_) {
      // Silencieux : on garde les résultats déjà chargés.
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openFilters() async {
    final catalog = context.read<CatalogProvider>();
    final result = await showModalBottomSheet<ProductFilters>(
      context: context,
      isScrollControlled: true,
      builder: (context) => FiltersSheet(
        filters: _filters,
        categories: catalog.categories,
        cities: catalog.cities,
      ),
    );
    if (result != null) {
      setState(() => _filters = result);
      _search();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: TextField(
          controller: _queryController,
          decoration: const InputDecoration(
            hintText: 'Rechercher...',
            border: InputBorder.none,
            isDense: true,
          ),
          textInputAction: TextInputAction.search,
          onSubmitted: (value) {
            setState(() => _filters = _filters.copyWith(query: value));
            _search();
          },
        ),
        actions: [
          IconButton(
            icon: Badge(
              isLabelVisible: _filters.hasActiveFilters,
              child: const Icon(Icons.tune),
            ),
            onPressed: _openFilters,
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _filters.sort,
                    isDense: true,
                    decoration: const InputDecoration(
                      labelText: 'Trier par',
                      isDense: true,
                    ),
                    items: const [
                      DropdownMenuItem(
                        value: 'relevance',
                        child: Text('Pertinence'),
                      ),
                      DropdownMenuItem(
                        value: 'date',
                        child: Text('Plus récentes'),
                      ),
                      DropdownMenuItem(
                        value: 'price_asc',
                        child: Text('Prix croissant'),
                      ),
                      DropdownMenuItem(
                        value: 'price_desc',
                        child: Text('Prix décroissant'),
                      ),
                      DropdownMenuItem(
                        value: 'popularity',
                        child: Text('Popularité'),
                      ),
                    ],
                    onChanged: (value) {
                      if (value == null) return;
                      setState(() => _filters = _filters.copyWith(sort: value));
                      _search();
                    },
                  ),
                ),
              ],
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading && _items.isEmpty) return const LoadingView();
    if (_error != null && _items.isEmpty)
      return ErrorView(message: _error!, onRetry: _search);
    if (_items.isEmpty) {
      return const EmptyStateView(
        icon: Icons.search_off,
        message: 'Aucune annonce ne correspond à votre recherche.',
      );
    }

    return RefreshIndicator(
      onRefresh: _search,
      child: GridView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.all(12),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 0.68,
        ),
        itemCount: _items.length,
        itemBuilder: (context, index) {
          final product = _items[index];
          return ProductCard(
            product: product,
            onTap: () => context.push('/product/${product.id}'),
          );
        },
      ),
    );
  }
}
