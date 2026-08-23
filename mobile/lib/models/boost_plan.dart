class BoostPlan {
  const BoostPlan({
    required this.id,
    required this.code,
    required this.name,
    required this.price,
    required this.durationHours,
  });

  final int id;
  final String code;
  final String name;
  final int price;
  final int durationHours;

  factory BoostPlan.fromJson(Map<String, dynamic> json) => BoostPlan(
    id: json['id'] as int,
    code: json['code'] as String,
    name: json['name'] as String,
    price: json['price'] as int,
    durationHours: json['duration_hours'] as int,
  );
}
