import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/phone_field.dart';

/// Étape 1 de l'inscription (ou changement de numéro) : saisie du téléphone
/// et envoi du code OTP.
class PhoneEntryScreen extends StatefulWidget {
  const PhoneEntryScreen({super.key, required this.purpose});

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

    try {
      await context.read<AuthProvider>().requestOtp(
        phone: _phoneController.text,
        purpose: widget.purpose,
      );
      if (!mounted) return;
      context.push(
        '/otp-verify?phone=${_phoneController.text}&purpose=${widget.purpose}',
      );
    } on ApiException catch (e) {
      setState(() => _error = e.errorFor('phone') ?? e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Créer un compte')),
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
