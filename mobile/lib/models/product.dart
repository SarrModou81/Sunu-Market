import 'category.dart';
import 'city.dart';
import 'user.dart';

class ProductImage {
  const ProductImage({
    required this.id,
    required this.url,
    required this.position,
    required this.isPrimary,
  });

  final int id;
  final String url;
  final int position;
  final bool isPrimary;

  factory ProductImage.fromJson(Map<String, dynamic> json) => ProductImage(
    id: json['id'] as int,
    url: json['url'] as String,
    position: json['position'] as int? ?? 0,
    isPrimary: json['is_primary'] as bool? ?? false,
  );
}

/// Représentation allégée d'une annonce (listes, recherche, favoris).
class ProductSummary {
  const ProductSummary({
    required this.id,
    required this.title,
    required this.slug,
    required this.price,
    required this.condition,
    this.city,
    this.coverImageUrl,
    required this.isBoosted,
    required this.deliveryAvailable,
    required this.status,
    required this.viewsCount,
    required this.createdAt,
  });

  final int id;
  final String title;
  final String slug;
  final int price;
  final String condition;
  final City? city;
  final String? coverImageUrl;
  final bool isBoosted;
  final bool deliveryAvailable;
  final String status;
  final int viewsCount;
  final DateTime createdAt;

  factory ProductSummary.fromJson(Map<String, dynamic> json) => ProductSummary(
    id: json['id'] as int,
    title: json['title'] as String,
    slug: json['slug'] as String,
    price: json['price'] as int,
    condition: json['condition'] as String,
    city: json['city'] != null && (json['city'] as Map).isNotEmpty
        ? City.fromJson(json['city'] as Map<String, dynamic>)
        : null,
    coverImageUrl: json['cover_image_url'] as String?,
    isBoosted: json['is_boosted'] as bool? ?? false,
    deliveryAvailable: json['delivery_available'] as bool? ?? false,
    status: json['status'] as String,
    viewsCount: json['views_count'] as int? ?? 0,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}

/// Représentation complète d'une annonce (page produit).
class Product {
  const Product({
    required this.id,
    required this.title,
    required this.slug,
    this.brand,
    required this.description,
    required this.price,
    required this.quantity,
    required this.condition,
    required this.deliveryAvailable,
    required this.status,
    this.rejectionReason,
    this.category,
    this.subcategory,
    this.city,
    this.neighborhood,
    this.images = const [],
    this.seller,
    required this.isBoosted,
    this.isFavorited,
    required this.viewsCount,
    required this.contactsCount,
    this.publishedAt,
    required this.createdAt,
  });

  final int id;
  final String title;
  final String slug;
  final String? brand;
  final String description;
  final int price;
  final int quantity;
  final String condition;
  final bool deliveryAvailable;
  final String status;
  final String? rejectionReason;
  final Category? category;
  final Subcategory? subcategory;
  final City? city;
  final Neighborhood? neighborhood;
  final List<ProductImage> images;
  final SellerSummary? seller;
  final bool isBoosted;
  final bool? isFavorited;
  final int viewsCount;
  final int contactsCount;
  final DateTime? publishedAt;
  final DateTime createdAt;

  String? get coverImageUrl => images.isEmpty ? null : images.first.url;

  factory Product.fromJson(Map<String, dynamic> json) => Product(
    id: json['id'] as int,
    title: json['title'] as String,
    slug: json['slug'] as String,
    brand: json['brand'] as String?,
    description: json['description'] as String? ?? '',
    price: json['price'] as int,
    quantity: json['quantity'] as int? ?? 1,
    condition: json['condition'] as String,
    deliveryAvailable: json['delivery_available'] as bool? ?? false,
    status: json['status'] as String,
    rejectionReason: json['rejection_reason'] as String?,
    category: json['category'] != null && (json['category'] as Map).isNotEmpty
        ? Category.fromJson(json['category'] as Map<String, dynamic>)
        : null,
    subcategory:
        json['subcategory'] != null && (json['subcategory'] as Map).isNotEmpty
        ? Subcategory.fromJson(json['subcategory'] as Map<String, dynamic>)
        : null,
    city: json['city'] != null && (json['city'] as Map).isNotEmpty
        ? City.fromJson(json['city'] as Map<String, dynamic>)
        : null,
    neighborhood:
        json['neighborhood'] != null && (json['neighborhood'] as Map).isNotEmpty
        ? Neighborhood.fromJson(json['neighborhood'] as Map<String, dynamic>)
        : null,
    images:
        (json['images'] as List<dynamic>?)
            ?.map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
            .toList() ??
        const [],
    seller: json['seller'] != null && (json['seller'] as Map).isNotEmpty
        ? SellerSummary.fromJson(json['seller'] as Map<String, dynamic>)
        : null,
    isBoosted: json['is_boosted'] as bool? ?? false,
    isFavorited: json['is_favorited'] as bool?,
    viewsCount: json['views_count'] as int? ?? 0,
    contactsCount: json['contacts_count'] as int? ?? 0,
    publishedAt: json['published_at'] != null
        ? DateTime.tryParse(json['published_at'] as String)
        : null,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}

class PaginatedResult<T> {
  const PaginatedResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    this.total,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int? total;

  bool get hasMore => currentPage < lastPage;
}
