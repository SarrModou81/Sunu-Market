import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../models/product.dart';
import '../../services/product_service.dart';
import '../../widgets/common.dart';

class EditProductScreen extends StatefulWidget {
  const EditProductScreen({super.key, required this.productId, this.product});

  final int productId;
  final Product? product;

  @override
  State<EditProductScreen> createState() => _EditProductScreenState();
}

class _EditProductScreenState extends State<EditProductScreen> {
  Product? _product;
  late final _titleController = TextEditingController();
  late final _priceController = TextEditingController();
  late final _descriptionController = TextEditingController();
  bool _loading = false;
  bool _busyImages = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _product = widget.product;
    if (_product != null) _fillControllers(_product!);
    _load();
  }

  void _fillControllers(Product product) {
    _titleController.text = product.title;
    _priceController.text = product.price.toString();
    _descriptionController.text = product.description;
  }

  Future<void> _load() async {
    try {
      final product = await context.read<ProductService>().show(
        widget.productId,
      );
      if (!mounted) return;
      setState(() => _product = product);
      _fillControllers(product);
    } catch (_) {
      if (mounted) setState(() => _error = "Impossible de charger l'annonce.");
    }
  }

  Future<void> _save() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final updated = await context.read<ProductService>().update(
        widget.productId,
        title: _titleController.text,
        price: int.tryParse(_priceController.text),
        description: _descriptionController.text,
      );
      if (!mounted) return;
      setState(() => _product = updated);
      ScaffoldMessenger.of(context)
          .showSnackBar(const SnackBar(content: Text('Annonce mise à jour.')));
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _changeStatus(String status) async {
    setState(() => _loading = true);
    try {
      final updated = await context.read<ProductService>().update(
        widget.productId,
        status: status,
      );
      if (mounted) setState(() => _product = updated);
    } on ApiException catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _addPhotos() async {
    final picked = await ImagePicker().pickMultiImage(limit: 8);
    if (picked.isEmpty) return;
    setState(() => _busyImages = true);
    try {
      final updated = await context.read<ProductService>().update(
        widget.productId,
        newImages: picked,
      );
      if (mounted) setState(() => _product = updated);
    } finally {
      if (mounted) setState(() => _busyImages = false);
    }
  }

  Future<void> _deletePhoto(int imageId) async {
    setState(() => _busyImages = true);
    try {
      await context.read<ProductService>().deleteImage(
        widget.productId,
        imageId,
      );
      await _load();
    } finally {
      if (mounted) setState(() => _busyImages = false);
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer cette annonce ?'),
        content: const Text('Cette action est irréversible.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await context.read<ProductService>().delete(widget.productId);
      if (mounted) context.pop();
    } on ApiException catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final product = _product;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Modifier l\'annonce'),
        actions: [
          IconButton(
            icon: const Icon(Icons.delete_outline),
            onPressed: _delete,
          ),
        ],
      ),
      body: product == null
          ? (_error != null
                ? ErrorView(message: _error!, onRetry: _load)
                : const LoadingView())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_error != null) ...[
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                  const SizedBox(height: 12),
                ],
                Wrap(
                  spacing: 8,
                  children: [
                    if (product.status == 'ACTIVE')
                      OutlinedButton(
                        onPressed: () => _changeStatus('VENDUE'),
                        child: const Text('Marquer vendue'),
                      ),
                    if (product.status == 'ACTIVE')
                      OutlinedButton(
                        onPressed: () => _changeStatus('ARCHIVEE'),
                        child: const Text('Archiver'),
                      ),
                    if (product.status == 'ARCHIVEE' ||
                        product.status == 'BROUILLON')
                      OutlinedButton(
                        onPressed: () => _changeStatus('ACTIVE'),
                        child: const Text('Republier'),
                      ),
                    if (product.status == 'ACTIVE')
                      OutlinedButton(
                        onPressed: () => context.push('/boost/${product.id}'),
                        child: const Text('Booster cette annonce'),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                const Text(
                  'Photos',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  height: 90,
                  child: _busyImages
                      ? const LoadingView()
                      : ListView(
                          scrollDirection: Axis.horizontal,
                          children: [
                            ...product.images.map(
                              (img) => Padding(
                                padding: const EdgeInsets.only(right: 8),
                                child: Stack(
                                  children: [
                                    ClipRRect(
                                      borderRadius: BorderRadius.circular(8),
                                      child: Image.network(
                                        img.url,
                                        width: 90,
                                        height: 90,
                                        fit: BoxFit.cover,
                                      ),
                                    ),
                                    Positioned(
                                      top: 2,
                                      right: 2,
                                      child: GestureDetector(
                                        onTap: () => _deletePhoto(img.id),
                                        child: const CircleAvatar(
                                          radius: 10,
                                          backgroundColor: Colors.black54,
                                          child: Icon(
                                            Icons.close,
                                            size: 14,
                                            color: Colors.white,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            InkWell(
                              onTap: _addPhotos,
                              child: Container(
                                width: 90,
                                height: 90,
                                decoration: BoxDecoration(
                                  border: Border.all(
                                    color: Colors.grey.shade400,
                                  ),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.add_a_photo_outlined),
                              ),
                            ),
                          ],
                        ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _titleController,
                  decoration: const InputDecoration(labelText: 'Titre'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _priceController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Prix (FCFA)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _descriptionController,
                  maxLines: 4,
                  decoration: const InputDecoration(labelText: 'Description'),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _loading ? null : _save,
                  child: _loading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Enregistrer'),
                ),
              ],
            ),
    );
  }
}
