import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../screens/auth/splash_screen.dart';
import '../screens/auth/onboarding_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/home/home_screen.dart';
import '../screens/home/search_screen.dart';
import '../screens/requests/requests_screen.dart';
import '../screens/orders/orders_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/home/listing_detail_screen.dart';
import '../screens/store/create_listing_screen.dart';
import '../screens/requests/request_detail_screen.dart';
import '../screens/notifications/notifications_screen.dart';
import '../screens/profile/favorites_screen.dart';
import '../screens/profile/legal_screen.dart';
import '../screens/orders/status_screen.dart';
import '../screens/profile/pricing_screen.dart';
import '../screens/home/stores_nearby_screen.dart';
import '../screens/home/category_screen.dart';
import '../screens/wallet/wallet_screen.dart';
import '../screens/common/address_screen.dart';
import '../screens/chat/conversations_screen.dart';
import '../models/conversation.dart';
import '../models/store.dart';
import '../models/order.dart';
import '../screens/profile/blocked_screen.dart';
import '../screens/notifications/notif_prefs_screen.dart';
import '../screens/store/store_screen.dart';
import '../screens/profile/verification_screen.dart';
import '../screens/store/reviews_screen.dart';
import '../screens/orders/coupon_screen.dart';
import '../screens/profile/settings_full_screen.dart';
import '../screens/profile/about_screen.dart';
import '../screens/orders/checkout_screen.dart';
import '../screens/orders/order_detail_screen.dart';

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
    GoRoute(path: '/create-request', builder: (_, __) => const CreateRequestScreen()),
    GoRoute(path: '/order-detail/:id', builder: (_, s) => OrderDetailScreen(orderId: s.pathParameters['id']!, order: s.extra as Order?)),
    GoRoute(path: '/checkout', builder: (_, s) => CheckoutScreen(listing: s.extra as dynamic)),
    GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
    GoRoute(path: '/favorites', builder: (_, __) => const FavoritesScreen()),
    GoRoute(path: '/wallet', builder: (_, __) => const WalletScreen()),
    GoRoute(path: '/addresses', builder: (_, __) => const AddressScreen()),
    GoRoute(path: '/conversations', builder: (_, __) => const ConversationsScreen()),
    GoRoute(path: '/chat/:id', builder: (_, s) => ChatScreen(conversation: s.extra as Conversation)),
    GoRoute(path: '/blocked', builder: (_, __) => const BlockedUsersScreen()),
    GoRoute(path: '/notif-prefs', builder: (_, __) => const NotifPrefsScreen()),
    GoRoute(path: '/create-store', builder: (_, __) => const CreateStoreScreen()),
    GoRoute(path: '/store/:id/dashboard', builder: (_, s) => StoreDashboardScreen(storeId: s.pathParameters['id']!, store: s.extra as Store?)),
    GoRoute(path: '/store/:id/reviews', builder: (_, s) => StoreReviewsScreen(storeId: s.pathParameters['id']!, storeName: s.extra?.toString() ?? 'Toko')),
    GoRoute(path: '/verification', builder: (_, __) => const VerificationScreen()),
    GoRoute(path: '/coupon', builder: (_, s) { final a = s.extra as Map<String,dynamic>?; return CouponScreen(orderTotal: (a?['total'] as num?)?.toDouble() ?? 0, orderId: a?['orderId']?.toString() ?? ''); }),
    GoRoute(path: '/settings', builder: (_, __) => const FullSettingsScreen()),
    GoRoute(path: '/categories', builder: (_, __) => const CategoryBrowseScreen()),
    GoRoute(path: '/category-search', builder: (_, s) => SearchScreen(category: int.tryParse(s.uri.queryParameters['id'] ?? ''), label: s.uri.queryParameters['label'] ?? 'Cari', showAppBar: true)),
    GoRoute(path: '/stores-nearby', builder: (_, __) => const StoresNearbyScreen()),
    GoRoute(path: '/about', builder: (_, __) => const AboutScreen()),
    GoRoute(path: '/help-legal', builder: (_, __) => const HelpLegalScreen()),
    GoRoute(path: '/status', builder: (_, __) => const StatusScreen()),
    GoRoute(path: '/pricing', builder: (_, __) => const PricingScreen()),
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
