/// Configuration de l'application, surchageable au build :
/// `flutter run --dart-define=API_BASE_URL=https://api.sunumarket.sn/api`
class AppConfig {
  AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    // 10.0.2.2 = alias de l'hôte depuis l'émulateur Android ; utiliser
    // localhost sur iOS Simulator ou l'IP de la machine sur un vrai appareil.
    defaultValue: 'http://10.0.2.2:8000/api',
  );
}
