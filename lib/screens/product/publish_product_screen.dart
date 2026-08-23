import 'dart:io';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../core/utils/formatters.dart';
import '../../models/category.dart';
import '../../models/city.dart';
import '../../providers/catalog_provider.dart';
import '../../services/product_service.dart';

class PublishProductScreen extends StatefulWidget {
  const PublishProductScreen({super.key});

  @override
  State<PublishProductScreen> createState() => _PublishProductScreenState();
}

class _PublishProductScreenState extends State<PublishProductScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _brandController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController();
  final _quantityController = TextEditingController(text: '1');

  Category? _category;
  Subcategory? _subcategory;
  City? _city;
  Neighborhood? _neighborhood;
  String _condition = 'bon';
  bool _delivery = false;
  final List<XFile> _images = [];
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<CatalogProvider>().load(),
    );
  }

  @override
  void dispose() {
    _titleController.dispose();
    _brandController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    _quantityController.dispose();
    super.dispose();
  }

  Future<void> _pickImages() async {
    if (_images.length >= 8) return;
    final picked = await ImagePicker().pickMultiImage(
      limit: 8 - _images.length,
    );
    setState(() => _images.addAll(picked));
  }

  Future<void> _submit({required bool isDraft}) async {
    if (!isDraft && !_formKey.currentState!.validate()) return;
    if (_category == null || _city == null) {
      setState(() => _error = 'Veuillez choisir une catégorie et une ville.');
      return;
    }
    if (!isDraft && _images.isEmpty) {
      setState(
        () =>
            _error = 'Ajoutez au moins une photo, ou enregistrez en brouillon.',
      );
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final product = await context.read<ProductService>().create(
        title: _titleController.text,
        description: _descriptionController.text,
        categoryId: _category!.id,
        subcategoryId: _subcategory?.id,
        price: int.tryParse(_priceController.text) ?? 0,
        quantity: int.tryParse(_quantityController.text) ?? 1,
        condition: _condition,
        cityId: _city!.id,
        neighborhoodId: _neighborhood?.id,
        deliveryAvailable: _delivery,
        brand: _brandController.text,
        isDraft: isDraft,
        images: _images,
      );
      if (!mounted) return;
      context.pushReplacement('/product/${product.id}');
    } on ApiException catch (e) {
      setState(() => _error = e.fieldErrors?['quota']?.first ?? e.message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final catalog = context.watch<CatalogProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Publier une annonce')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 12),
            ],
            _PhotosPicker(
              images: _images,
              onAdd: _pickImages,
              onRemove: (i) => setState(() => _images.removeAt(i)),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _titleController,
              decoration: const InputDecoration(
                labelText: 'Titre de l\'annonce',
              ),
              validator: (v) =>
                  (v == null || v.isEmpty) ? 'Champ requis' : null,
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<Category>(
              value: _category,
              decoration: const InputDecoration(labelText: 'Catégorie'),
              items: catalog.categories
                  .map((c) => DropdownMenuItem(value: c, child: Text(c.name)))
                  .toList(),
              onChanged: (value) => setState(() {
                _category = value;
                _subcategory = null;
              }),
            ),
            if (_category != null && _category!.subcategories.isNotEmpty) ...[
              const SizedBox(height: 12),
              DropdownButtonFormField<Subcategory>(
                value: _subcategory,
                decoration: const InputDecoration(
                  labelText: 'Sous-catégorie (facultatif)',
                ),
                items: _category!.subcategories
                    .map((s) => DropdownMenuItem(value: s, child: Text(s.name)))
                    .toList(),
                onChanged: (value) => setState(() => _subcategory = value),
              ),
            ],
            const SizedBox(height: 12),
            TextFormField(
              controller: _brandController,
              decoration: const InputDecoration(
                labelText: 'Marque (facultatif)',
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _descriptionController,
              maxLines: 4,
              decoration: const InputDecoration(labelText: 'Description'),
              validator: (v) =>
                  (v == null || v.isEmpty) ? 'Champ requis' : null,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _priceController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Prix (FCFA)'),
                    validator: (v) => (v == null || int.tryParse(v) == null)
                        ? 'Prix invalide'
                        : null,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    controller: _quantityController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Quantité'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _condition,
              decoration: const InputDecoration(labelText: 'État'),
              items: productConditionLabels.entries
                  .map(
                    (e) => DropdownMenuItem(value: e.key, child: Text(e.value)),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _condition = value!),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<City>(
              value: _city,
              decoration: const InputDecoration(labelText: 'Ville'),
              items: catalog.cities
                  .map((c) => DropdownMenuItem(value: c, child: Text(c.name)))
                  .toList(),
              onChanged: (value) => setState(() {
                _city = value;
                _neighborhood = null;
              }),
            ),
            if (_city != null && _city!.neighborhoods.isNotEmpty) ...[
              const SizedBox(height: 12),
              DropdownButtonFormField<Neighborhood>(
                value: _neighborhood,
                decoration: const InputDecoration(
                  labelText: 'Quartier (facultatif)',
                ),
                items: _city!.neighborhoods
                    .map((n) => DropdownMenuItem(value: n, child: Text(n.name)))
                    .toList(),
                onChanged: (value) => setState(() => _neighborhood = value),
              ),
            ],
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Livraison disponible'),
              value: _delivery,
              onChanged: (value) => setState(() => _delivery = value),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _submitting ? null : () => _submit(isDraft: false),
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Publier l\'annonce'),
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: _submitting ? null : () => _submit(isDraft: true),
              child: const Text('Enregistrer comme brouillon'),
            ),
          ],
        ),
      ),
    );
  }
}

class _PhotosPicker extends StatelessWidget {
  const _PhotosPicker({
    required this.images,
    required this.onAdd,
    required this.onRemove,
  });

  final List<XFile> images;
  final VoidCallback onAdd;
  final ValueChanged<int> onRemove;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 90,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          ...images.asMap().entries.map(
            (entry) => Padding(
              padding: const EdgeInsets.only(right: 8),
              child: Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.file(
                      File(entry.value.path),
                      width: 90,
                      height: 90,
                      fit: BoxFit.cover,
                    ),
                  ),
                  Positioned(
                    top: 2,
                    right: 2,
                    child: GestureDetector(
                      onTap: () => onRemove(entry.key),
                      child: const CircleAvatar(
                        radius: 10,
                        backgroundColor: Colors.black54,
                        child: Icon(Icons.close, size: 14, color: Colors.white),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (images.length < 8)
            InkWell(
              onTap: onAdd,
              child: Container(
                width: 90,
                height: 90,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade400),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.add_a_photo_outlined),
              ),
            ),
        ],
      ),
    );
  }
}
