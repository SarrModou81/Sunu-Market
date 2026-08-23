import 'package:flutter/foundation.dart';

import '../core/api/api_exception.dart';
import '../core/storage/token_storage.dart';
import '../models/user.dart';
import '../services/auth_service.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

/// État d'authentification global de l'application.
class AuthProvider extends ChangeNotifier {
  AuthProvider(this._authService, this._tokenStorage);

  final AuthService _authService;
  final TokenStorage _tokenStorage;

  AuthStatus status = AuthStatus.unknown;
  AppUser? currentUser;

  bool get isAuthenticated => status == AuthStatus.authenticated;

  Future<void> bootstrap() async {
    final token = await _tokenStorage.readToken();
    if (token == null) {
      status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      currentUser = await _authService.me();
      status = AuthStatus.authenticated;
    } on ApiException {
      await _tokenStorage.clearToken();
      status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<void> requestOtp({required String phone, required String purpose}) {
    return _authService.requestOtp(phone: phone, purpose: purpose);
  }

  Future<void> register({
    required String phone,
    required String code,
    required String password,
    required String passwordConfirmation,
    required String firstName,
    required String lastName,
    String? email,
    int? cityId,
  }) async {
    final (token, user) = await _authService.register(
      phone: phone,
      code: code,
      password: password,
      passwordConfirmation: passwordConfirmation,
      firstName: firstName,
      lastName: lastName,
      email: email,
      cityId: cityId,
    );
    await _onAuthenticated(token, user);
  }

  Future<void> login({required String phone, required String password}) async {
    final (token, user) = await _authService.login(
      phone: phone,
      password: password,
    );
    await _onAuthenticated(token, user);
  }

  Future<void> loginWithOtp({
    required String phone,
    required String code,
  }) async {
    final (token, user) = await _authService.loginWithOtp(
      phone: phone,
      code: code,
    );
    await _onAuthenticated(token, user);
  }

  Future<void> _onAuthenticated(String token, AppUser user) async {
    await _tokenStorage.saveToken(token);
    currentUser = user;
    status = AuthStatus.authenticated;
    notifyListeners();
  }

  void updateUser(AppUser user) {
    currentUser = user;
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await _authService.logout();
    } on ApiException {
      // Le jeton est peut-être déjà invalide côté serveur ; on nettoie quand même localement.
    }
    await _tokenStorage.clearToken();
    currentUser = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  /// Appelé par l'ApiClient sur une réponse 401 (jeton expiré/révoqué).
  Future<void> forceLogout() async {
    await _tokenStorage.clearToken();
    currentUser = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }
}
