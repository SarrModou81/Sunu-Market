class Subcategory {
  const Subcategory({required this.id, required this.name, required this.slug});

  final int id;
  final String name;
  final String slug;

  factory Subcategory.fromJson(Map<String, dynamic> json) => Subcategory(
    id: json['id'] as int,
    name: json['name'] as String,
    slug: json['slug'] as String,
  );
}

class Category {
  const Category({
    required this.id,
    required this.name,
    required this.slug,
    this.icon,
    this.subcategories = const [],
  });

  final int id;
  final String name;
  final String slug;
  final String? icon;
  final List<Subcategory> subcategories;

  factory Category.fromJson(Map<String, dynamic> json) => Category(
    id: json['id'] as int,
    name: json['name'] as String,
    slug: json['slug'] as String,
    icon: json['icon'] as String?,
    subcategories:
        (json['subcategories'] as List<dynamic>?)
            ?.map((e) => Subcategory.fromJson(e as Map<String, dynamic>))
            .toList() ??
        const [],
  );
}
