import 'package:firebase_auth/firebase_auth.dart' show FirebaseAuthException;
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../providers/auth_provider.dart';
import '../../services/firebase_auth_service.dart';
import '../../widgets/phone_field.dart';

/// Étape 1 de l'inscription/connexion par téléphone : saisie du numéro et
/// envoi du code de vérification par SMS (via Firebase Authentication).
class PhoneEntryScreen extends StatefulWidget {
  const PhoneEntryScreen({super.key, required this.purpose});

  /// Purement informatif (titre de l'écran) : Firebase ne distingue pas
  /// inscription et connexion, seule la réponse du backend le fait.
  final String purpose;

  @override
  State<PhoneEntryScreen> createState() => _PhoneEntryScreenState();
}

class _PhoneEntryScreenState extends State<PhoneEntryScreen> {
  final _phoneController = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final phone = '+221${_phoneController.text.trim()}';

    try {
      final auth = context.read<AuthProvider>();
      final result = await auth.startFirebasePhoneVerification(phone);

      if (!mounted) return;

      switch (result) {
        case FirebaseCodeSent(:final verificationId):
          context.push(
            '/otp-verify',
            extra: {'phone': phone, 'verificationId': verificationId},
          );
        case FirebaseAutoVerified(:final credential):
          final isKnownUser = await auth.signInWithFirebaseCredential(
            credential,
          );
          if (!mounted) return;
          if (isKnownUser) {
            context.go('/home');
          } else {
            context.push('/register-details');
          }
      }
    } on FirebaseAuthException catch (e) {
      setState(
        () => _error =
            e.message ?? 'Numéro invalide ou envoi du code impossible.',
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Numéro de téléphone')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Quel est votre numéro de téléphone ?',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'Nous vous enverrons un code de vérification par SMS.',
              style: TextStyle(color: Colors.black54),
            ),
            const SizedBox(height: 24),
            PhoneField(
              controller: _phoneController,
              errorText: _error,
              autofocus: true,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _loading ? null : _submit,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Recevoir le code'),
            ),
          ],
        ),
      ),
    );
  }
}
