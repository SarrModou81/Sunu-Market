import '../models/product.dart';

/// Construit un [PaginatedResult] à partir d'une réponse `{data: [...], meta: {...}}`.
PaginatedResult<T> parsePaginated<T>(
  Map<String, dynamic> json,
  T Function(Map<String, dynamic>) fromJson,
) {
  final items = (json['data'] as List<dynamic>)
      .map((e) => fromJson(e as Map<String, dynamic>))
      .toList();
  final meta = json['meta'] as Map<String, dynamic>? ?? {};

  return PaginatedResult<T>(
    items: items,
    currentPage: meta['current_page'] as int? ?? 1,
    lastPage: meta['last_page'] as int? ?? 1,
    total: meta['total'] as int?,
  );
}
