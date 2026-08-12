/// Compatibility wrapper — delegates to new ApiClient + DioClient.
/// Semua screen lama tetap bisa pakai `ApiProvider()` tanpa rewrite massal.
/// Panggil `.toCompat()` untuk migrasi bertahap.

import 'dart:io';
import '../models/user.dart';
import '../models/store.dart';
import '../models/listing.dart';
import '../models/order.dart';
import '../models/category.dart';
import '../models/conversation.dart';
import '../models/customer_request.dart';
import '../models/notification.dart';
import '../models/review.dart';
import '../models/wallet.dart';
import '../models/address.dart';
import '../models/dashboard.dart';
import 'api_client.dart';

class ApiProvider {
  final ApiClient _api = ApiClient();

  // ─── Auth ───
  Future<void> requestOtp(String phone) => _api.requestOtp(phone);
  Future<Map<String,dynamic>> verifyOtp(String phone, String otp) => _api.verifyOtp(phone, otp);
  Future<User> me() async { final r = await _api.me(); return User.fromJson(r['user'] as Map<String,dynamic>); }
  Future<User> updateProfile(Map<String,dynamic> body) async { final r = await _api.updateProfile(body); return User.fromJson(r['user'] as Map<String,dynamic>); }
  Future<Map<String,dynamic>> refresh() => _api.refreshToken();
  Future<void> logout() => _api.logout();

  // ─── Categories ───
  Future<List<Category>> getCategories() async {
    final r = await _api.categories();
    return r.map((c) => Category.fromJson(c as Map<String,dynamic>)).toList();
  }

  // ─── Config ───
  Future<Map<String,dynamic>> config() => _api.config();

  // ─── Home ───
  Future<Map<String,dynamic>> home(double lat, double lng) => _api.home(lat, lng);

  // ─── Search ───
  Future<List<Listing>> searchSuggestions(String q, {String? type}) async {
    final r = await _api.searchSuggestions(q, type: type);
    return r.map((l) => Listing.fromJson(l as Map<String,dynamic>)).toList();
  }

  // ─── Listings ───
  Future<List<Listing>> getListings({required double lat, required double lng, double? radius, int? category, String? type, String? keyword, String sort = 'nearest', int page = 1}) async {
    final r = await _api.listings(lat: lat, lng: lng, radius: radius, category: category, type: type, keyword: keyword, sort: sort, page: page);
    return (r as List?)?.map((l) => Listing.fromJson(l as Map<String,dynamic>)).toList() ?? [];
  }
  Future<Listing> getListing(String id) async { final r = await _api.listingDetail(id); return Listing.fromJson(r['listing'] as Map<String,dynamic>); }
  Future<Listing> createListing(Map<String,dynamic> body) async { final r = await _api.createListing(body); return Listing.fromJson(r['listing'] as Map<String,dynamic>); }
  Future<Listing> updateListing(String id, Map<String,dynamic> body) async { final r = await _api.updateListing(id, body); return Listing.fromJson(r['listing'] as Map<String,dynamic>); }
  Future<void> deleteListing(String id) => _api.deleteListing(id);
  Future<Map<String,dynamic>> shareListing(String id) => _api.shareListing(id);

  // ─── Stores ───
  Future<List<Store>> nearbyStores({required double lat, required double lng, double? radius, String? type}) async {
    final r = await _api.nearbyStores(lat: lat, lng: lng, radius: radius, type: type);
    return (r as List?)?.map((s) => Store.fromJson(s as Map<String,dynamic>)).toList() ?? [];
  }
  Future<List<Store>> mine() async {
    final r = await _api.mineStores();
    return (r as List?)?.map((s) => Store.fromJson(s as Map<String,dynamic>)).toList() ?? [];
  }
  Future<Store> getStore(String id) async { final r = await _api.storeDetail(id); return Store.fromJson(r['store'] as Map<String,dynamic>); }
  Future<Store> createStore(Map<String,dynamic> body, {File? photo}) async { final r = await _api.createStore(body, photo: photo); return Store.fromJson(r['store'] as Map<String,dynamic>); }
  Future<Store> updateStore(String id, Map<String,dynamic> body) async { final r = await _api.updateStore(id, body); return Store.fromJson(r['store'] as Map<String,dynamic>); }
  Future<List<Review>> getStoreReviews(String id) async {
    final r = await _api.storeReviews(id);
    return (r as List?)?.map((rv) => Review.fromJson(rv as Map<String,dynamic>)).toList() ?? [];
  }
  Future<StoreDashboard> getStoreDashboard(String id) async { final r = await _api.storeDashboard(id); return StoreDashboard.fromJson(r); }

  // ─── Favorites ───
  Future<List<Listing>> getFavorites() async {
    final r = await _api.favorites();
    return (r as List?)?.map((l) => Listing.fromJson(l as Map<String,dynamic>)).toList() ?? [];
  }
  Future<void> favoriteListing(String id) => _api.addFavorite(id);
  Future<void> unfavoriteListing(String id) => _api.removeFavorite(id);

  // ─── Notifications ───
  Future<List<AppNotification>> getNotifications({int page = 1}) async {
    final r = await _api.notifications(page: page);
    return (r as List?)?.map((n) => AppNotification.fromJson(n as Map<String,dynamic>)).toList() ?? [];
  }
  Future<int> unreadCount() async { final r = await _api.unreadCount(); return (r['count'] as num?)?.toInt() ?? 0; }
  Future<void> markRead(String id) => _api.markRead(id);
  Future<void> markAllRead() => _api.markAllRead();
  Future<List<NotificationPreference>> getNotifPreferences() async {
    final r = await _api.notifPreferences();
    return (r['channels'] as List?)?.map((c) => NotificationPreference.fromJson(c as Map<String,dynamic>)).toList() ?? [];
  }
  Future<void> updateNotifPreferences(List<Map<String,dynamic>> channels) => _api.updateNotifPreferences(channels);

  // ─── Conversations ───
  Future<List<Conversation>> getConversations() async {
    final r = await _api.conversations();
    return (r as List?)?.map((c) => Conversation.fromJson(c as Map<String,dynamic>)).toList() ?? [];
  }
  Future<Conversation> createConversation(String participantId, {String? orderId}) async {
    final r = await _api.createConversation({'participant_id': participantId, if (orderId != null) 'order_id': orderId});
    return Conversation.fromJson(r);
  }
  Future<List<ChatMessage>> getMessages(String convId, {int page = 1}) async {
    final r = await _api.messages(convId, page: page);
    return (r as List?)?.map((m) => ChatMessage.fromJson(m as Map<String,dynamic>)).toList() ?? [];
  }
  Future<ChatMessage> sendMessage(String convId, String message) async {
    final r = await _api.sendMessage(convId, message);
    return ChatMessage.fromJson(r);
  }

  // ─── Requests ───
  Future<List<CustomerRequest>> getRequests({required double lat, required double lng}) async {
    final r = await _api.requests(lat: lat, lng: lng);
    return (r as List?)?.map((x) => CustomerRequest.fromJson(x as Map<String,dynamic>)).toList() ?? [];
  }
  Future<List<CustomerRequest>> myRequests({String? status}) async {
    final r = await _api.myRequests(status: status);
    return (r as List?)?.map((x) => CustomerRequest.fromJson(x as Map<String,dynamic>)).toList() ?? [];
  }
  Future<CustomerRequest> getRequest(String id) async { final r = await _api.requestDetail(id); return CustomerRequest.fromJson(r['request'] as Map<String,dynamic>); }
  Future<CustomerRequest> createRequest(Map<String,dynamic> body) async { final r = await _api.createRequest(body); return CustomerRequest.fromJson(r['request'] as Map<String,dynamic>); }
  Future<CustomerRequest> updateRequest(String id, Map<String,dynamic> body) async { final r = await _api.updateRequest(id, body); return CustomerRequest.fromJson(r['request'] as Map<String,dynamic>); }
  Future<void> deleteRequest(String id) => _api.deleteRequest(id);
  Future<CustomerRequest> extendRequest(String id) async { final r = await _api.extendRequest(id); return CustomerRequest.fromJson(r['request'] as Map<String,dynamic>); }
  Future<List<Offer>> getRequestOffers(String reqId, {String sort = 'cheapest'}) async {
    final r = await _api.requestOffers(reqId, sort: sort);
    return (r as List?)?.map((o) => Offer.fromJson(o as Map<String,dynamic>)).toList() ?? [];
  }

  // ─── Offers ───
  Future<Offer> createOffer(String requestId, Map<String,dynamic> body) async {
    final r = await _api.createOffer(requestId, body);
    return Offer.fromJson(r['offer'] as Map<String,dynamic>);
  }
  Future<Offer> getOffer(String id) async { final r = await _api.offerDetail(id); return Offer.fromJson(r['offer'] as Map<String,dynamic>); }
  Future<Order> acceptOffer(String id) async { final r = await _api.acceptOffer(id); return Order.fromJson(r['order'] as Map<String,dynamic>); }

  // ─── Orders ───
  Future<List<Order>> getOrders({String role = 'buyer', String? status}) async {
    final r = await _api.orders(role: role, status: status);
    return (r as List?)?.map((o) => Order.fromJson(o as Map<String,dynamic>)).toList() ?? [];
  }
  Future<Order> createOrder(Map<String,dynamic> body) async { final r = await _api.createOrder(body); return Order.fromJson(r['order'] as Map<String,dynamic>); }
  Future<Order> getOrder(String id) async { final r = await _api.orderDetail(id); return Order.fromJson(r['order'] as Map<String,dynamic>); }
  Future<Order> updateOrderStatus(String id, String status, {String? reason}) async {
    final r = await _api.updateOrderStatus(id, {'status': status, if (reason != null) 'reason': reason});
    return Order.fromJson(r['order'] as Map<String,dynamic>);
  }
  Future<void> uploadPaymentProof(String orderId, File file) => _api.uploadPaymentProof(orderId, file);
  Future<Review> submitReview(String orderId, int rating, {String? comment}) async {
    final r = await _api.submitReview(orderId, {'rating': rating, if (comment != null) 'comment': comment});
    return Review.fromJson(r['review'] as Map<String,dynamic>);
  }
  Future<void> disputeOrder(String orderId, String reason, {String? description}) => _api.disputeOrder(orderId, {'reason': reason, if (description != null) 'description': description});

  // ─── Wallet ───
  Future<Wallet> getWallet() async { final r = await _api.wallet(); return Wallet.fromJson(r['wallet'] as Map<String,dynamic>); }
  Future<List<WalletTransaction>> getWalletTransactions() async {
    final r = await _api.walletTxs();
    return (r as List?)?.map((t) => WalletTransaction.fromJson(t as Map<String,dynamic>)).toList() ?? [];
  }
  Future<WalletTransaction> topup(double amount) async { final r = await _api.topup(amount); return WalletTransaction.fromJson(r['transaction'] as Map<String,dynamic>); }

  // ─── Addresses ───
  Future<List<UserAddress>> getAddresses() async {
    final r = await _api.addresses();
    return (r as List).map((a) => UserAddress.fromJson(a as Map<String,dynamic>)).toList();
  }
  Future<UserAddress> createAddress(Map<String,dynamic> body) async { final r = await _api.createAddress(body); return UserAddress.fromJson(r); }
  Future<UserAddress> updateAddress(String id, Map<String,dynamic> body) async { final r = await _api.updateAddress(id, body); return UserAddress.fromJson(r); }
  Future<void> deleteAddress(String id) => _api.deleteAddress(id);
  Future<void> setDefaultAddress(String id) => _api.setDefaultAddress(id);

  // ─── Block ───
  Future<List<Map<String,dynamic>>> getBlockedUsers() async {
    final r = await _api.blockedUsers();
    return (r as List?)?.cast<Map<String,dynamic>>() ?? [];
  }
  Future<void> blockUser(String id) => _api.blockUser(id);
  Future<void> unblockUser(String id) => _api.unblockUser(id);

  // ─── Upload ───
  Future<Map<String,dynamic>> uploadImage(File file, {String purpose = 'listing'}) => _api.uploadImage(file, purpose: purpose);
  Future<void> deleteUpload(String path) => _api.deleteUpload(path);
  Future<Map<String,dynamic>> validateCoupon(String code, double orderTotal) => _api.validateCoupon(code, orderTotal);
  Future<Map<String,dynamic>> applyCoupon(String code, String orderId) => _api.applyCoupon(code, orderId);
  Future<Map<String,dynamic>> uploadKtp(File ktpImage, File selfieImage, {required String nik, String? address, double? latitude, double? longitude}) => _api.uploadKtp(ktpImage, selfieImage, nik: nik, address: address, latitude: latitude, longitude: longitude);
  Future<List<int>> verificationPhoto(String kind) => _api.verificationPhoto(kind);

  // ─── Reports ───
  Future<void> report(String targetType, String targetId, String reason, {String? description}) => _api.report({'target_type': targetType, 'target_id': targetId, 'reason': reason, if (description != null) 'description': description});

  // ─── Privacy ───
  Future<Map<String,dynamic>> exportData() => _api.exportData();
  Future<void> deleteAccount() => _api.deleteAccount();

  // ─── Wallet Extras ───
  Future<Map<String,dynamic>> withdraw(Map<String,dynamic> body) => _api.withdraw(body);

  // ─── Phone Change ───
  Future<void> requestPhoneChangeOtp(String phone) => _api.requestPhoneChangeOtp(phone);
  Future<Map<String,dynamic>> verifyPhoneChangeOtp(String phone, String otp) => _api.verifyPhoneChangeOtp(phone, otp);
}
