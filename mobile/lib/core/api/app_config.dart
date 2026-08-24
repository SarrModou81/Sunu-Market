/// Configuration de l'application, surchageable au build :
/// `flutter run --dart-define=API_BASE_URL=https://api.sunumarket.sn/api`
class AppConfig {
  AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    // Tunnel ngrok exposant `php artisan serve` en local, pour un accès
    // depuis un vrai appareil Android (10.0.2.2 ne fonctionne que sur
    // l'émulateur). Remplacer par l'URL ngrok active si elle change.
    defaultValue: 'https://patchwork-resupply-drastic.ngrok-free.dev/api',
  );
}
