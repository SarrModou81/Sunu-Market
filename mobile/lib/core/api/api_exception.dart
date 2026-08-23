/// Erreur API normalisée, construite à partir des réponses JSON du backend
/// Laravel ({"message": "...", "errors": {"champ": ["..."]}}).
class ApiException implements Exception {
  ApiException({required this.message, this.statusCode, this.fieldErrors});

  final String message;
  final int? statusCode;
  final Map<String, List<String>>? fieldErrors;

  /// Premier message d'erreur pour un champ donné, utile pour l'afficher sous
  /// un champ de formulaire.
  String? errorFor(String field) => fieldErrors?[field]?.first;

  bool get isUnauthorized => statusCode == 401;

  bool get isValidationError => statusCode == 422;

  @override
  String toString() => message;
}
