class Neighborhood {
  const Neighborhood({
    required this.id,
    required this.cityId,
    required this.name,
  });

  final int id;
  final int cityId;
  final String name;

  factory Neighborhood.fromJson(Map<String, dynamic> json) => Neighborhood(
    id: json['id'] as int,
    cityId: json['city_id'] as int,
    name: json['name'] as String,
  );
}

class City {
  const City({
    required this.id,
    required this.name,
    this.region,
    this.neighborhoods = const [],
  });

  final int id;
  final String name;
  final String? region;
  final List<Neighborhood> neighborhoods;

  factory City.fromJson(Map<String, dynamic> json) => City(
    id: json['id'] as int,
    name: json['name'] as String,
    region: json['region'] as String?,
    neighborhoods:
        (json['neighborhoods'] as List<dynamic>?)
            ?.map((e) => Neighborhood.fromJson(e as Map<String, dynamic>))
            .toList() ??
        const [],
  );
}
