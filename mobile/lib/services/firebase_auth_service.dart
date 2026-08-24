import 'dart:async';

import 'package:firebase_auth/firebase_auth.dart';

/// Résultat du déclenchement de la vérification par téléphone Firebase :
/// soit un code a été envoyé par SMS (cas normal), soit Android a pu vérifier
/// le numéro automatiquement sans saisie de code (auto-retrieval).
sealed class FirebasePhoneVerificationResult {}

class FirebaseCodeSent extends FirebasePhoneVerificationResult {
  FirebaseCodeSent(this.verificationId);

  final String verificationId;
}

class FirebaseAutoVerified extends FirebasePhoneVerificationResult {
  FirebaseAutoVerified(this.credential);

  final PhoneAuthCredential credential;
}

/// Enveloppe autour de FirebaseAuth pour la vérification de numéro de
/// téléphone (envoi du SMS + confirmation du code), utilisée pour
/// l'inscription et la connexion côté SunuMarket.
class FirebaseAuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;

  /// Déclenche l'envoi du SMS de vérification pour [phoneE164] (format
  /// +221XXXXXXXXX). Se résout dès que le code est envoyé (cas normal) ou dès
  /// qu'Android a vérifié le numéro automatiquement.
  Future<FirebasePhoneVerificationResult> verifyPhoneNumber(String phoneE164) {
    final completer = Completer<FirebasePhoneVerificationResult>();

    _auth.verifyPhoneNumber(
      phoneNumber: phoneE164,
      timeout: const Duration(seconds: 60),
      verificationCompleted: (credential) {
        if (!completer.isCompleted) {
          completer.complete(FirebaseAutoVerified(credential));
        }
      },
      verificationFailed: (e) {
        if (!completer.isCompleted) {
          completer.completeError(e);
        }
      },
      codeSent: (verificationId, resendToken) {
        if (!completer.isCompleted) {
          completer.complete(FirebaseCodeSent(verificationId));
        }
      },
      codeAutoRetrievalTimeout: (verificationId) {},
    );

    return completer.future;
  }

  /// Confirme le code saisi par l'utilisateur et retourne le jeton Firebase
  /// vérifié à transmettre au backend.
  Future<String> confirmCode({
    required String verificationId,
    required String smsCode,
  }) {
    final credential = PhoneAuthProvider.credential(
      verificationId: verificationId,
      smsCode: smsCode,
    );
    return signInWithCredential(credential);
  }

  /// Termine la connexion Firebase avec un identifiant déjà obtenu (cas de
  /// la vérification automatique Android) et retourne le jeton vérifié.
  Future<String> signInWithCredential(PhoneAuthCredential credential) async {
    final userCredential = await _auth.signInWithCredential(credential);
    final idToken = await userCredential.user?.getIdToken();

    if (idToken == null) {
      throw FirebaseAuthException(
        code: 'no-id-token',
        message: "Impossible d'obtenir le jeton d'authentification Firebase.",
      );
    }

    return idToken;
  }
}
