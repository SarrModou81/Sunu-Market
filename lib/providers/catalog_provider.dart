import 'package:flutter/foundation.dart' hide Category;

import '../models/category.dart';
import '../models/city.dart';
import '../services/catalog_service.dart';

/// Charge et met en cache les catégories et villes (peu changeantes) pour
/// toute l'application (filtres, formulaire de publication...).
class CatalogProvider extends ChangeNotifier {
  CatalogProvider(this._service);

  final CatalogService _service;

  List<Category> categories = [];
  List<City> cities = [];
  bool isLoading = false;
  String? error;

  Future<void> load() async {
    if (categories.isNotEmpty && cities.isNotEmpty) return;

    isLoading = true;
    error = null;
    notifyListeners();

    try {
      final results = await Future.wait([
        _service.categories(),
        _service.cities(),
      ]);
      categories = results[0] as List<Category>;
      cities = results[1] as List<City>;
    } catch (e) {
      error = 'Impossible de charger les catégories et villes.';
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
