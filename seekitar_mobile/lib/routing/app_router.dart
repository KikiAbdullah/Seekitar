import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../screens/splash_screen.dart';
import '../screens/onboarding_screen.dart';
import '../screens/login_screen.dart';
import '../screens/home_screen.dart';
import '../screens/search_screen.dart';
import '../screens/requests_screen.dart';
import '../screens/orders_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/listing_detail_screen.dart';
import '../screens/create_listing_screen.dart';
import '../screens/request_detail_screen.dart';
import '../screens/notifications_screen.dart';
import '../screens/favorites_screen.dart';
import '../screens/wallet_screen.dart';
import '../screens/address_screen.dart';
import '../screens/conversations_screen.dart';
import '../screens/blocked_screen.dart';
import '../screens/notif_prefs_screen.dart';
import '../screens/store_screen.dart';
import '../screens/verification_screen.dart';
import '../screens/reviews_screen.dart';
import '../screens/coupon_screen.dart';
import '../screens/settings_full_screen.dart';
import '../screens/about_screen.dart';

final _rootKey = GlobalKey<NavigatorState>();

final appRouter = GoRouter(
  navigatorKey: _rootKey,
  initialLocation: '/splash',
  routes: [
    GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
    GoRoute(path: '/onboarding', builder: (_, __) => const OnboardingScreen()),
    GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
    StatefulShellRoute.indexedStack(
      builder: (_, __, shell) => HomeShell(navigationShell: shell),
      branches: [
        StatefulShellBranch(routes: [GoRoute(path: '/home', builder: (_, __) => const HomeScreen(key: ValueKey('home')))]),
        StatefulShellBranch(routes: [GoRoute(path: '/search', builder: (_, __) => const SearchScreen(key: ValueKey('search')))]),
        StatefulShellBranch(routes: [GoRoute(path: '/requests', builder: (_, __) => const RequestsScreen(key: ValueKey('requests')))]),
        StatefulShellBranch(routes: [GoRoute(path: '/orders', builder: (_, __) => const OrdersScreen(key: ValueKey('orders')))]),
        StatefulShellBranch(routes: [GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen(key: ValueKey('profile')))]),
      ],
    ),
    GoRoute(path: '/listing/:id', builder: (_, s) => ListingDetailScreen(listingId: s.pathParameters['id']!)),
    GoRoute(path: '/create-listing', builder: (_, s) => CreateListingScreen(store: s.extra as dynamic)),
    GoRoute(path: '/request/:id', builder: (_, s) => RequestDetailScreen(requestId: s.pathParameters['id']!)),
    GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
    GoRoute(path: '/favorites', builder: (_, __) => const FavoritesScreen()),
    GoRoute(path: '/wallet', builder: (_, __) => const WalletScreen()),
    GoRoute(path: '/addresses', builder: (_, __) => const AddressScreen()),
    GoRoute(path: '/conversations', builder: (_, __) => const ConversationsScreen()),
    GoRoute(path: '/blocked', builder: (_, __) => const BlockedUsersScreen()),
    GoRoute(path: '/notif-prefs', builder: (_, __) => const NotifPrefsScreen()),
    GoRoute(path: '/create-store', builder: (_, __) => const CreateStoreScreen()),
    GoRoute(path: '/store/:id/dashboard', builder: (_, s) => StoreDashboardScreen(storeId: s.pathParameters['id']!)),
    GoRoute(path: '/store/:id/reviews', builder: (_, s) => StoreReviewsScreen(storeId: s.pathParameters['id']!, storeName: s.extra?.toString() ?? 'Toko')),
    GoRoute(path: '/verification', builder: (_, __) => const VerificationScreen()),
    GoRoute(path: '/coupon', builder: (_, s) { final a = s.extra as Map<String,dynamic>?; return CouponScreen(orderTotal: (a?['total'] as num?)?.toDouble() ?? 0, orderId: a?['orderId']?.toString() ?? ''); }),
    GoRoute(path: '/settings', builder: (_, __) => const FullSettingsScreen()),
    GoRoute(path: '/about', builder: (_, __) => const AboutScreen()),
  ],
);

class HomeShell extends StatelessWidget {
  final StatefulNavigationShell navigationShell;
  const HomeShell({super.key, required this.navigationShell});
  @override Widget build(BuildContext ctx) => Scaffold(
    body: navigationShell,
    bottomNavigationBar: NavigationBar(
      selectedIndex: navigationShell.currentIndex,
      onDestinationSelected: (i) => navigationShell.goBranch(i),
      destinations: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Beranda'),
        NavigationDestination(icon: Icon(Icons.search_outlined), selectedIcon: Icon(Icons.search), label: 'Cari'),
        NavigationDestination(icon: Icon(Icons.broadcast_on_personal_outlined), selectedIcon: Icon(Icons.broadcast_on_personal), label: 'Kebutuhan'),
        NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Pesanan'),
        NavigationDestination(icon: Icon(Icons.person_outlined), selectedIcon: Icon(Icons.person), label: 'Profil'),
      ],
    ),
  );
}
