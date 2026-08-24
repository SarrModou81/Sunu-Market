import 'package:firebase_auth/firebase_auth.dart' show PhoneAuthCredential;
import 'package:flutter/foundation.dart';

import '../core/api/api_exception.dart';
import '../core/storage/token_storage.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/firebase_auth_service.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

/// État d'authentification global de l'application.
class AuthProvider extends ChangeNotifier {
  AuthProvider(
    this._authService,
    this._tokenStorage,
    this._firebaseAuthService,
  );

  final AuthService _authService;
  final TokenStorage _tokenStorage;
  final FirebaseAuthService _firebaseAuthService;

  /// Jeton Firebase en attente de finalisation : posé lorsque le numéro
  /// vérifié est nouveau et que le profil (prénom/nom) n'a pas encore été
  /// fourni. Réutilisé par [completeFirebaseRegistration] sans redemander
  /// de code SMS (valide environ une heure côté Firebase).
  String? _pendingFirebaseIdToken;

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

  /// Utilisé uniquement pour le mot de passe oublié (le reste de
  /// l'authentification par téléphone passe désormais par Firebase).
  Future<void> requestOtp({required String phone, required String purpose}) {
    return _authService.requestOtp(phone: phone, purpose: purpose);
  }

  Future<void> login({required String phone, required String password}) async {
    final (token, user) = await _authService.login(
      phone: phone,
      password: password,
    );
    await _onAuthenticated(token, user);
  }

  /// Déclenche l'envoi du SMS Firebase pour [phone] (format +221XXXXXXXXX).
  Future<FirebasePhoneVerificationResult> startFirebasePhoneVerification(
    String phone,
  ) {
    return _firebaseAuthService.verifyPhoneNumber(phone);
  }

  /// Termine la connexion pour un identifiant déjà obtenu (vérification
  /// automatique Android, sans saisie de code). Retourne false si le numéro
  /// est nouveau : appeler [completeFirebaseRegistration] pour finir.
  Future<bool> signInWithFirebaseCredential(PhoneAuthCredential credential) {
    return _firebaseAuthService
        .signInWithCredential(credential)
        .then(_completeFirebaseAuth);
  }

  /// Confirme le code saisi par l'utilisateur. Retourne false si le numéro
  /// est nouveau : appeler [completeFirebaseRegistration] pour finir.
  Future<bool> confirmFirebaseCode({
    required String verificationId,
    required String code,
  }) {
    return _firebaseAuthService
        .confirmCode(verificationId: verificationId, smsCode: code)
        .then(_completeFirebaseAuth);
  }

  /// Finalise l'inscription d'un numéro nouvellement vérifié, après que
  /// [confirmFirebaseCode]/[signInWithFirebaseCredential] a retourné false.
  Future<void> completeFirebaseRegistration({
    required String firstName,
    required String lastName,
    String? email,
    int? cityId,
  }) async {
    final idToken = _pendingFirebaseIdToken;
    if (idToken == null) {
      throw StateError('Aucune vérification Firebase en attente.');
    }

    await _completeFirebaseAuth(
      idToken,
      firstName: firstName,
      lastName: lastName,
      email: email,
      cityId: cityId,
    );
  }

  Future<bool> _completeFirebaseAuth(
    String idToken, {
    String? firstName,
    String? lastName,
    String? email,
    int? cityId,
  }) async {
    try {
      final (token, user, _) = await _authService.loginOrRegisterWithFirebase(
        idToken: idToken,
        firstName: firstName,
        lastName: lastName,
        email: email,
        cityId: cityId,
      );
      _pendingFirebaseIdToken = null;
      await _onAuthenticated(token, user);
      return true;
    } on ApiException catch (e) {
      if (e.isValidationError && firstName == null) {
        // Numéro nouveau : le profil est requis avant de pouvoir finaliser.
        _pendingFirebaseIdToken = idToken;
        return false;
      }
      rethrow;
    }
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
