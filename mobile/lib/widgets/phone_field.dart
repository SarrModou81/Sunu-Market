import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Champ de saisie du numéro de téléphone sénégalais (9 chiffres locaux,
/// l'indicatif +221 est géré par le backend via PhoneNumber::normalize).
class PhoneField extends StatelessWidget {
  const PhoneField({
    super.key,
    required this.controller,
    this.errorText,
    this.autofocus = false,
  });

  final TextEditingController controller;
  final String? errorText;
  final bool autofocus;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      autofocus: autofocus,
      keyboardType: TextInputType.phone,
      inputFormatters: [
        FilteringTextInputFormatter.digitsOnly,
        LengthLimitingTextInputFormatter(9),
      ],
      decoration: InputDecoration(
        prefixIcon: const Padding(
          padding: EdgeInsets.only(left: 14, right: 8),
          child: Center(
            widthFactor: 1,
            child: Text('+221', style: TextStyle(fontWeight: FontWeight.w600)),
          ),
        ),
        prefixIconConstraints: const BoxConstraints(minWidth: 0),
        hintText: '77 123 45 67',
        errorText: errorText,
      ),
    );
  }
}
