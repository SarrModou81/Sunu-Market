/// Filtres de recherche d'annonces (section 7 du cahier des charges).
class ProductFilters {
  const ProductFilters({
    this.query,
    this.sellerId,
    this.categoryId,
    this.subcategoryId,
    this.condition,
    this.cityId,
    this.neighborhoodId,
    this.deliveryAvailable,
    this.minPrice,
    this.maxPrice,
    this.sort = 'relevance',
  });

  final String? query;
  final int? sellerId;
  final int? categoryId;
  final int? subcategoryId;
  final String? condition;
  final int? cityId;
  final int? neighborhoodId;
  final bool? deliveryAvailable;
  final int? minPrice;
  final int? maxPrice;
  final String sort;

  Map<String, dynamic> toQuery() => {
    if (query != null && query!.isNotEmpty) 'q': query,
    if (sellerId != null) 'seller_id': sellerId,
    if (categoryId != null) 'category_id': categoryId,
    if (subcategoryId != null) 'subcategory_id': subcategoryId,
    if (condition != null) 'condition': condition,
    if (cityId != null) 'city_id': cityId,
    if (neighborhoodId != null) 'neighborhood_id': neighborhoodId,
    if (deliveryAvailable != null) 'delivery_available': deliveryAvailable,
    if (minPrice != null) 'min_price': minPrice,
    if (maxPrice != null) 'max_price': maxPrice,
    'sort': sort,
  };

  ProductFilters copyWith({
    String? query,
    int? categoryId,
    int? subcategoryId,
    String? condition,
    int? cityId,
    int? neighborhoodId,
    bool? deliveryAvailable,
    int? minPrice,
    int? maxPrice,
    String? sort,
    bool clearCategory = false,
    bool clearCondition = false,
    bool clearCity = false,
    bool clearPriceRange = false,
  }) {
    return ProductFilters(
      query: query ?? this.query,
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      subcategoryId: clearCategory
          ? null
          : (subcategoryId ?? this.subcategoryId),
      condition: clearCondition ? null : (condition ?? this.condition),
      cityId: clearCity ? null : (cityId ?? this.cityId),
      neighborhoodId: clearCity
          ? null
          : (neighborhoodId ?? this.neighborhoodId),
      deliveryAvailable: deliveryAvailable ?? this.deliveryAvailable,
      minPrice: clearPriceRange ? null : (minPrice ?? this.minPrice),
      maxPrice: clearPriceRange ? null : (maxPrice ?? this.maxPrice),
      sort: sort ?? this.sort,
    );
  }

  bool get hasActiveFilters =>
      categoryId != null ||
      condition != null ||
      cityId != null ||
      deliveryAvailable != null ||
      minPrice != null ||
      maxPrice != null;
}
