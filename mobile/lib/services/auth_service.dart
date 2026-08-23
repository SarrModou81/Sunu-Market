import '../core/api/api_client.dart';
import '../models/user.dart';

class AuthService {
  AuthService(this._client);

  final ApiClient _client;

  Future<void> requestOtp({
    required String phone,
    required String purpose,
  }) async {
    await _client.post(
      '/auth/otp/request',
      data: {'phone': phone, 'purpose': purpose},
    );
  }

  /// Retourne le jeton d'authentification et le profil créé.
  Future<(String token, AppUser user)> register({
    required String phone,
    required String code,
    required String password,
    required String passwordConfirmation,
    required String firstName,
    required String lastName,
    String? email,
    int? cityId,
  }) async {
    final response = await _client.post(
      '/auth/register',
      data: {
        'phone': phone,
        'code': code,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'first_name': firstName,
        'last_name': lastName,
        if (email != null && email.isNotEmpty) 'email': email,
        if (cityId != null) 'city_id': cityId,
      },
    );

    return (
      response.data['token'] as String,
      AppUser.fromJson(response.data['user'] as Map<String, dynamic>),
    );
  }

  Future<(String token, AppUser user)> login({
    required String phone,
    required String password,
  }) async {
    final response = await _client.post(
      '/auth/login',
      data: {'phone': phone, 'password': password},
    );

    return (
      response.data['token'] as String,
      AppUser.fromJson(response.data['user'] as Map<String, dynamic>),
    );
  }

  Future<(String token, AppUser user)> loginWithOtp({
    required String phone,
    required String code,
  }) async {
    final response = await _client.post(
      '/auth/otp/login',
      data: {'phone': phone, 'code': code},
    );

    return (
      response.data['token'] as String,
      AppUser.fromJson(response.data['user'] as Map<String, dynamic>),
    );
  }

  Future<void> resetPassword({
    required String phone,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _client.post(
      '/auth/password/reset',
      data: {
        'phone': phone,
        'code': code,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }

  Future<AppUser> me() async {
    final response = await _client.get('/auth/me');
    return AppUser.fromJson(response.data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    await _client.post('/auth/logout');
  }

  Future<void> changePhone({
    required String newPhone,
    required String code,
  }) async {
    await _client.post(
      '/profile/phone/change',
      data: {'new_phone': newPhone, 'code': code},
    );
  }

  Future<void> deleteAccount({required String password}) async {
    await _client.delete('/account', data: {'password': password});
  }
}
