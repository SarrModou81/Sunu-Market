import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../models/payment.dart';
import '../../services/payment_service.dart';
import '../../widgets/common.dart';

/// Écran affiché après redirection vers le fournisseur de paiement : permet
/// de vérifier le statut (l'activation réelle se fait côté serveur via
/// webhook, jamais depuis ce simple affichage client).
class PaymentStatusScreen extends StatefulWidget {
  const PaymentStatusScreen({super.key, required this.paymentId});

  final int paymentId;

  @override
  State<PaymentStatusScreen> createState() => _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends State<PaymentStatusScreen> {
  Payment? _payment;
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    _check();
  }

  Future<void> _check() async {
    setState(() => _checking = true);
    try {
      final payment = await context.read<PaymentService>().show(
        widget.paymentId,
      );
      if (mounted) setState(() => _payment = payment);
    } catch (_) {
      // Silencieux : l'utilisateur peut réessayer manuellement.
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final payment = _payment;

    return Scaffold(
      appBar: AppBar(title: const Text('Statut du paiement')),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (payment == null)
                const LoadingView()
              else if (payment.isPaid) ...[
                const Icon(
                  Icons.check_circle,
                  color: AppColors.success,
                  size: 64,
                ),
                const SizedBox(height: 16),
                const Text(
                  'Paiement confirmé !',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Votre annonce est maintenant boostée / votre abonnement est actif.',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Terminer'),
                ),
              ] else if (payment.isPending) ...[
                const Icon(
                  Icons.hourglass_top,
                  color: AppColors.accent,
                  size: 64,
                ),
                const SizedBox(height: 16),
                const Text(
                  'En attente de confirmation...',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text(
                  "Le paiement peut prendre quelques instants à être confirmé. Vous pouvez fermer cet écran, vous serez notifié dès l'activation.",
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                OutlinedButton(
                  onPressed: _checking ? null : _check,
                  child: _checking
                      ? const CircularProgressIndicator()
                      : const Text('Vérifier à nouveau'),
                ),
              ] else ...[
                const Icon(
                  Icons.error_outline,
                  color: AppColors.danger,
                  size: 64,
                ),
                const SizedBox(height: 16),
                Text(
                  'Paiement ${payment.status}.',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Fermer'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
