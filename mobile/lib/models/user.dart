import 'city.dart';

class Profile {
  const Profile({
    required this.firstName,
    required this.lastName,
    required this.fullName,
    this.email,
    this.city,
    this.avatarUrl,
    this.bio,
  });

  final String firstName;
  final String lastName;
  final String fullName;
  final String? email;
  final City? city;
  final String? avatarUrl;
  final String? bio;

  factory Profile.fromJson(Map<String, dynamic> json) => Profile(
    firstName: json['first_name'] as String? ?? '',
    lastName: json['last_name'] as String? ?? '',
    fullName: json['full_name'] as String? ?? '',
    email: json['email'] as String?,
    city: json['city'] != null && (json['city'] as Map).isNotEmpty
        ? City.fromJson(json['city'] as Map<String, dynamic>)
        : null,
    avatarUrl: json['avatar_url'] as String?,
    bio: json['bio'] as String?,
  );
}

class SellerProfile {
  const SellerProfile({
    this.shopName,
    this.description,
    required this.sellerType,
    required this.isPro,
    this.proExpiresAt,
    required this.badgeVerified,
    required this.ratingAverage,
    required this.ratingCount,
    this.activeProductsLimit,
  });

  final String? shopName;
  final String? description;
  final String sellerType;
  final bool isPro;
  final DateTime? proExpiresAt;
  final bool badgeVerified;
  final double ratingAverage;
  final int ratingCount;
  final int? activeProductsLimit;

  factory SellerProfile.fromJson(Map<String, dynamic> json) => SellerProfile(
    shopName: json['shop_name'] as String?,
    description: json['description'] as String?,
    sellerType: json['seller_type'] as String? ?? 'standard',
    isPro: json['is_pro'] as bool? ?? false,
    proExpiresAt: json['pro_expires_at'] != null
        ? DateTime.tryParse(json['pro_expires_at'] as String)
        : null,
    badgeVerified: json['badge_verified'] as bool? ?? false,
    ratingAverage: (json['rating_average'] as num?)?.toDouble() ?? 0,
    ratingCount: json['rating_count'] as int? ?? 0,
    activeProductsLimit: json['active_products_limit'] as int?,
  );
}

class AppUser {
  const AppUser({
    required this.id,
    required this.phone,
    required this.phoneVerified,
    required this.role,
    this.status,
    this.profile,
    this.sellerProfile,
  });

  final int id;
  final String phone;
  final bool phoneVerified;
  final String role;
  final String? status;
  final Profile? profile;
  final SellerProfile? sellerProfile;

  bool get isAdmin => role == 'admin';
  bool get isModerator => role == 'moderator' || role == 'admin';

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
    id: json['id'] as int,
    phone: json['phone'] as String,
    phoneVerified: json['phone_verified'] as bool? ?? false,
    role: json['role'] as String? ?? 'client',
    status: json['status'] as String?,
    profile: json['profile'] != null && (json['profile'] as Map).isNotEmpty
        ? Profile.fromJson(json['profile'] as Map<String, dynamic>)
        : null,
    sellerProfile:
        json['seller_profile'] != null &&
            (json['seller_profile'] as Map).isNotEmpty
        ? SellerProfile.fromJson(json['seller_profile'] as Map<String, dynamic>)
        : null,
  );
}

class SellerSummary {
  const SellerSummary({
    required this.id,
    this.name,
    this.avatarUrl,
    this.city,
    required this.isPro,
    required this.badgeVerified,
    required this.ratingAverage,
    required this.ratingCount,
    this.memberSince,
  });

  final int id;
  final String? name;
  final String? avatarUrl;
  final City? city;
  final bool isPro;
  final bool badgeVerified;
  final double ratingAverage;
  final int ratingCount;
  final DateTime? memberSince;

  factory SellerSummary.fromJson(Map<String, dynamic> json) => SellerSummary(
    id: json['id'] as int,
    name: json['name'] as String?,
    avatarUrl: json['avatar_url'] as String?,
    city: json['city'] != null && (json['city'] as Map).isNotEmpty
        ? City.fromJson(json['city'] as Map<String, dynamic>)
        : null,
    isPro: json['is_pro'] as bool? ?? false,
    badgeVerified: json['badge_verified'] as bool? ?? false,
    ratingAverage: (json['rating_average'] as num?)?.toDouble() ?? 0,
    ratingCount: json['rating_count'] as int? ?? 0,
    memberSince: json['member_since'] != null
        ? DateTime.tryParse(json['member_since'] as String)
        : null,
  );
}
