import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_theme.dart';
import '../../providers/auth_provider.dart';
import '../../services/payment_service.dart';
import '../payment/payment_status_screen.dart';

/// Souscription à l'abonnement Vendeur Pro (section 3 du cahier des charges) :
/// 2000 FCFA/mois, annonces illimitées, statistiques, badge Pro.
class SubscriptionScreen extends StatefulWidget {
  const SubscriptionScreen({super.key});

  @override
  State<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends State<SubscriptionScreen> {
  String _provider = 'wave';
  bool _submitting = false;
  String? _error;

  Future<void> _pay() async {
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final payment = await context.read<PaymentService>().subscribePro(
        provider: _provider,
      );
      if (payment.checkoutUrl != null) {
        await launchUrl(
          Uri.parse(payment.checkoutUrl!),
          mode: LaunchMode.externalApplication,
        );
      }
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (context) => PaymentStatusScreen(paymentId: payment.id),
        ),
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isPro =
        context.watch<AuthProvider>().currentUser?.sellerProfile?.isPro ??
        false;

    return Scaffold(
      appBar: AppBar(title: const Text('Vendeur Pro')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Card(
            color: AppColors.primary,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Vendeur Pro',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    '2 000 FCFA / mois',
                    style: TextStyle(color: Colors.white, fontSize: 18),
                  ),
                  const SizedBox(height: 16),
                  ..._benefits.map(
                    (b) => Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.check_circle,
                            color: Colors.white,
                            size: 18,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              b,
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          if (isPro)
            const Text(
              'Vous êtes déjà abonné au compte Pro.',
              textAlign: TextAlign.center,
            )
          else ...[
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 12),
            ],
            const Text(
              'Moyen de paiement',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            RadioListTile<String>(
              value: 'wave',
              groupValue: _provider,
              onChanged: (value) => setState(() => _provider = value!),
              title: const Text('Wave'),
            ),
            RadioListTile<String>(
              value: 'orange_money',
              groupValue: _provider,
              onChanged: (value) => setState(() => _provider = value!),
              title: const Text('Orange Money'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: _submitting ? null : _pay,
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text("S'abonner"),
            ),
          ],
        ],
      ),
    );
  }

  static const _benefits = [
    'Annonces illimitées (au lieu de 10 maximum)',
    'Profil professionnel avec badge Pro',
    'Statistiques de vues et de contacts',
    'Produits les plus consultés',
    'Réponse automatique aux messages',
  ];
}
