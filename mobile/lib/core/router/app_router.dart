import 'package:go_router/go_router.dart';

import '../../models/product.dart';
import '../../providers/auth_provider.dart';
import '../../screens/auth/forgot_password_screen.dart';
import '../../screens/auth/login_screen.dart';
import '../../screens/auth/otp_verify_screen.dart';
import '../../screens/auth/phone_entry_screen.dart';
import '../../screens/auth/register_details_screen.dart';
import '../../screens/auth/reset_password_otp_screen.dart';
import '../../screens/auth/reset_password_screen.dart';
import '../../screens/favorites/favorites_screen.dart';
import '../../screens/home/home_shell.dart';
import '../../screens/home/home_tab.dart';
import '../../screens/search/search_tab.dart';
import '../../screens/messages/chat_screen.dart';
import '../../screens/messages/conversations_screen.dart';
import '../../screens/notifications/notifications_screen.dart';
import '../../screens/product/edit_product_screen.dart';
import '../../screens/product/my_products_screen.dart';
import '../../screens/product/product_detail_screen.dart';
import '../../screens/product/publish_product_screen.dart';
import '../../screens/profile/edit_profile_screen.dart';
import '../../screens/profile/profile_screen.dart';
import '../../screens/boost/boost_screen.dart';
import '../../screens/boost/subscription_screen.dart';
import '../../screens/seller/seller_profile_screen.dart';
import '../../screens/splash_screen.dart';

const _authRequiredPaths = [
  '/my-products',
  '/favorites',
  '/messages',
  '/notifications',
  '/profile',
  '/publish',
];

GoRouter buildRouter(AuthProvider authProvider) {
  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: authProvider,
    redirect: (context, state) {
      final loggedIn = authProvider.isAuthenticated;
      final path = state.matchedLocation;

      if (authProvider.status == AuthStatus.unknown) {
        return null;
      }

      if (path == '/splash') {
        return loggedIn ? '/home' : '/login';
      }

      final needsAuth = _authRequiredPaths.any((p) => path.startsWith(p));
      final isAuthScreen =
          path.startsWith('/login') ||
          path.startsWith('/register') ||
          path.startsWith('/forgot-password') ||
          path.startsWith('/reset-password') ||
          path.startsWith('/otp-verify');

      if (needsAuth && !loggedIn) {
        return '/login?redirect=${Uri.encodeComponent(path)}';
      }

      if (isAuthScreen && loggedIn) {
        return '/home';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/register',
        builder: (context, state) =>
            const PhoneEntryScreen(purpose: 'register'),
      ),
      GoRoute(
        path: '/login-otp',
        builder: (context, state) => const PhoneEntryScreen(purpose: 'login'),
      ),
      GoRoute(
        path: '/forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/reset-password-otp',
        builder: (context, state) =>
            ResetPasswordOtpScreen(phone: state.uri.queryParameters['phone']!),
      ),
      GoRoute(
        path: '/reset-password',
        builder: (context, state) => ResetPasswordScreen(
          phone: state.uri.queryParameters['phone']!,
          code: state.uri.queryParameters['code']!,
        ),
      ),
      GoRoute(
        path: '/otp-verify',
        builder: (context, state) {
          final extra = state.extra! as Map<String, dynamic>;
          return OtpVerifyScreen(
            phone: extra['phone'] as String,
            verificationId: extra['verificationId'] as String,
          );
        },
      ),
      GoRoute(
        path: '/register-details',
        builder: (context, state) => const RegisterDetailsScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) =>
            HomeShell(location: state.matchedLocation, child: child),
        routes: [
          GoRoute(path: '/home', builder: (context, state) => const HomeTab()),
          GoRoute(
            path: '/search',
            builder: (context, state) => const SearchTab(),
          ),
          GoRoute(
            path: '/favorites',
            builder: (context, state) => const FavoritesScreen(),
          ),
          GoRoute(
            path: '/messages',
            builder: (context, state) => const ConversationsScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),
      GoRoute(
        path: '/product/:id',
        builder: (context, state) => ProductDetailScreen(
          productId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/publish',
        builder: (context, state) => const PublishProductScreen(),
      ),
      GoRoute(
        path: '/product/:id/edit',
        builder: (context, state) {
          final product = state.extra as Product?;
          return EditProductScreen(
            productId: int.parse(state.pathParameters['id']!),
            product: product,
          );
        },
      ),
      GoRoute(
        path: '/my-products',
        builder: (context, state) => const MyProductsScreen(),
      ),
      GoRoute(
        path: '/messages/:id',
        builder: (context, state) =>
            ChatScreen(conversationId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/seller/:id',
        builder: (context, state) => SellerProfileScreen(
          sellerId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/notifications',
        builder: (context, state) => const NotificationsScreen(),
      ),
      GoRoute(
        path: '/profile/edit',
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: '/boost/:productId',
        builder: (context, state) => BoostScreen(
          productId: int.parse(state.pathParameters['productId']!),
        ),
      ),
      GoRoute(
        path: '/subscription/pro',
        builder: (context, state) => const SubscriptionScreen(),
      ),
    ],
  );
}
