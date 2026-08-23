import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'core/api/api_client.dart';
import 'core/router/app_router.dart';
import 'core/storage/token_storage.dart';
import 'core/theme/app_theme.dart';
import 'providers/auth_provider.dart';
import 'providers/catalog_provider.dart';
import 'providers/notification_provider.dart';
import 'services/auth_service.dart';
import 'services/catalog_service.dart';
import 'services/conversation_service.dart';
import 'services/notification_service.dart';
import 'services/payment_service.dart';
import 'services/product_service.dart';
import 'services/profile_service.dart';
import 'services/report_service.dart';
import 'services/review_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('fr_FR');
  runApp(const SunuMarketApp());
}

class SunuMarketApp extends StatelessWidget {
  const SunuMarketApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider(create: (_) => TokenStorage()),
        ProxyProvider<TokenStorage, ApiClient>(
          update: (_, tokenStorage, previous) =>
              previous ?? ApiClient(tokenStorage),
        ),
        Provider(create: (context) => AuthService(context.read<ApiClient>())),
        Provider(
          create: (context) => CatalogService(context.read<ApiClient>()),
        ),
        Provider(
          create: (context) => ProductService(context.read<ApiClient>()),
        ),
        Provider(
          create: (context) => ConversationService(context.read<ApiClient>()),
        ),
        Provider(create: (context) => ReviewService(context.read<ApiClient>())),
        Provider(create: (context) => ReportService(context.read<ApiClient>())),
        Provider(
          create: (context) => PaymentService(context.read<ApiClient>()),
        ),
        Provider(
          create: (context) => NotificationService(context.read<ApiClient>()),
        ),
        Provider(
          create: (context) => ProfileService(context.read<ApiClient>()),
        ),
        ChangeNotifierProvider(
          create: (context) => AuthProvider(
            context.read<AuthService>(),
            context.read<TokenStorage>(),
          ),
        ),
        ChangeNotifierProvider(
          create: (context) => CatalogProvider(context.read<CatalogService>()),
        ),
        ChangeNotifierProvider(
          create: (context) =>
              NotificationProvider(context.read<NotificationService>()),
        ),
      ],
      child: const _AppRoot(),
    );
  }
}

class _AppRoot extends StatefulWidget {
  const _AppRoot();

  @override
  State<_AppRoot> createState() => _AppRootState();
}

class _AppRootState extends State<_AppRoot> {
  late final authProvider = context.read<AuthProvider>();
  late final router = buildRouter(authProvider);

  @override
  void initState() {
    super.initState();
    context.read<ApiClient>().onUnauthorized = () => authProvider.forceLogout();
    authProvider.bootstrap();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'SunuMarket',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: router,
    );
  }
}
