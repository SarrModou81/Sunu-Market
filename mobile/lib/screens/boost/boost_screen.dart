import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api/api_exception.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../models/boost_plan.dart';
import '../../services/payment_service.dart';
import '../../widgets/common.dart';
import '../payment/payment_status_screen.dart';

/// Écran d'achat d'un Boost pour une annonce (section 11 du cahier des charges).
class BoostScreen extends StatefulWidget {
  const BoostScreen({super.key, required this.productId});

  final int productId;

  @override
  State<BoostScreen> createState() => _BoostScreenState();
}

class _BoostScreenState extends State<BoostScreen> {
  List<BoostPlan>? _plans;
  BoostPlan? _selectedPlan;
  String _provider = 'wave';
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final plans = await context.read<PaymentService>().boostPlans();
      if (mounted) setState(() => _plans = plans);
    } catch (_) {
      if (mounted)
        setState(() => _error = 'Impossible de charger les offres de Boost.');
    }
  }

  Future<void> _pay() async {
    if (_selectedPlan == null) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final payment = await context.read<PaymentService>().boostProduct(
        widget.productId,
        boostPlanId: _selectedPlan!.id,
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
    return Scaffold(
      appBar: AppBar(title: const Text('Booster mon annonce')),
      body: _plans == null
          ? (_error != null
                ? ErrorView(message: _error!, onRetry: _load)
                : const LoadingView())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const Text(
                  'Un Boost améliore la visibilité de votre annonce pendant sa durée, sans garantir une première place permanente.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 16),
                if (_error != null) ...[
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                  const SizedBox(height: 12),
                ],
                ..._plans!.map(
                  (plan) => RadioListTile<BoostPlan>(
                    value: plan,
                    groupValue: _selectedPlan,
                    onChanged: (value) => setState(() => _selectedPlan = value),
                    title: Text(plan.name),
                    subtitle: Text(formatPrice(plan.price)),
                  ),
                ),
                const SizedBox(height: 16),
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
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: (_selectedPlan == null || _submitting)
                      ? null
                      : _pay,
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Payer et booster'),
                ),
              ],
            ),
    );
  }
}
