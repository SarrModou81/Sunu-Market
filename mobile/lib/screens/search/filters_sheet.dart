import 'package:flutter/material.dart';

import '../../core/utils/formatters.dart';
import '../../models/category.dart';
import '../../models/city.dart';
import '../../models/product_filters.dart';

class FiltersSheet extends StatefulWidget {
  const FiltersSheet({
    super.key,
    required this.filters,
    required this.categories,
    required this.cities,
  });

  final ProductFilters filters;
  final List<Category> categories;
  final List<City> cities;

  @override
  State<FiltersSheet> createState() => _FiltersSheetState();
}

class _FiltersSheetState extends State<FiltersSheet> {
  late int? _categoryId = widget.filters.categoryId;
  late String? _condition = widget.filters.condition;
  late int? _cityId = widget.filters.cityId;
  late bool? _delivery = widget.filters.deliveryAvailable;
  late final _minPriceController = TextEditingController(
    text: widget.filters.minPrice?.toString() ?? '',
  );
  late final _maxPriceController = TextEditingController(
    text: widget.filters.maxPrice?.toString() ?? '',
  );

  @override
  void dispose() {
    _minPriceController.dispose();
    _maxPriceController.dispose();
    super.dispose();
  }

  void _apply() {
    Navigator.of(context).pop(
      widget.filters.copyWith(
        categoryId: _categoryId,
        clearCategory: _categoryId == null,
        condition: _condition,
        clearCondition: _condition == null,
        cityId: _cityId,
        clearCity: _cityId == null,
        deliveryAvailable: _delivery,
        minPrice: int.tryParse(_minPriceController.text),
        maxPrice: int.tryParse(_maxPriceController.text),
        clearPriceRange:
            _minPriceController.text.isEmpty &&
            _maxPriceController.text.isEmpty,
      ),
    );
  }

  void _reset() {
    setState(() {
      _categoryId = null;
      _condition = null;
      _cityId = null;
      _delivery = null;
      _minPriceController.clear();
      _maxPriceController.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.85,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ),
          child: ListView(
            controller: scrollController,
            padding: const EdgeInsets.all(20),
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Filtres',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  TextButton(
                    onPressed: _reset,
                    child: const Text('Réinitialiser'),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Text(
                'Catégorie',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: widget.categories
                    .map(
                      (c) => ChoiceChip(
                        label: Text(c.name),
                        selected: _categoryId == c.id,
                        onSelected: (selected) => setState(
                          () => _categoryId = selected ? c.id : null,
                        ),
                      ),
                    )
                    .toList(),
              ),
              const SizedBox(height: 20),
              const Text('État', style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                children: productConditionLabels.entries
                    .map(
                      (e) => ChoiceChip(
                        label: Text(e.value),
                        selected: _condition == e.key,
                        onSelected: (selected) => setState(
                          () => _condition = selected ? e.key : null,
                        ),
                      ),
                    )
                    .toList(),
              ),
              const SizedBox(height: 20),
              const Text(
                'Ville',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<int>(
                initialValue: _cityId,
                decoration: const InputDecoration(
                  hintText: 'Toutes les villes',
                ),
                items: widget.cities
                    .map(
                      (c) => DropdownMenuItem(value: c.id, child: Text(c.name)),
                    )
                    .toList(),
                onChanged: (value) => setState(() => _cityId = value),
              ),
              const SizedBox(height: 20),
              const Text(
                'Prix (FCFA)',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _minPriceController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(hintText: 'Min'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _maxPriceController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(hintText: 'Max'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Livraison disponible'),
                value: _delivery ?? false,
                onChanged: (value) =>
                    setState(() => _delivery = value ? true : null),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _apply,
                child: const Text('Appliquer les filtres'),
              ),
            ],
          ),
        );
      },
    );
  }
}
