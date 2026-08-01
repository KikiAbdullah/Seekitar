import 'dart:io';
import 'package:dio/dio.dart';
import '../core/constants.dart';
import 'dio_client.dart';

/// Single entry point untuk SEMUA API call — thin wrapper di atas DioClient.
class ApiClient {
  final DioClient _client = DioClient();
  Dio get _dio => _client.dio;

  Future<T> _get<T>(String path, {Map<String, dynamic>? query, T Function(dynamic json)? parser}) async {
    final res = await _dio.get(path, queryParameters: query);
    return parser != null ? parser(res.data) : res.data as T;
  }

  Future<T> _post<T>(String path, {dynamic data, T Function(dynamic json)? parser}) async {
    final res = await _dio.post(path, data: data);
    return parser != null ? parser(res.data) : res.data as T;
  }

  Future<T> _patch<T>(String path, {dynamic data, T Function(dynamic json)? parser}) async {
    final res = await _dio.patch(path, data: data);
    return parser != null ? parser(res.data) : res.data as T;
  }

  Future<T> _put<T>(String path, {dynamic data, T Function(dynamic json)? parser}) async {
    final res = await _dio.put(path, data: data);
    return parser != null ? parser(res.data) : res.data as T;
  }

  Future<T> _delete<T>(String path, {T Function(dynamic json)? parser}) async {
    final res = await _dio.delete(path);
    return parser != null ? parser(res.data) : res.data as T;
  }

  Future<Map<String,dynamic>> upload(String path, File file, {String field = 'file', Map<String,String>? fields}) async {
    final formData = FormData.fromMap({field: await MultipartFile.fromFile(file.path), if (fields != null) ...fields});
    final res = await _dio.post(path, data: formData);
    return Map<String,dynamic>.from(res.data as Map);
  }

  // ─── Auth ──────────────────────────────────────────
  Future<Map<String,dynamic>> requestOtp(String phone) => _post('/auth/request-otp', data: {'phone': phone});
  Future<Map<String,dynamic>> verifyOtp(String phone, String otp) => _post('/auth/verify-otp', data: {'phone': phone, 'otp': otp});
  Future<Map<String,dynamic>> me() => _get('/auth/me');
  Future<Map<String,dynamic>> refreshToken() => _post('/auth/refresh');
  Future<void> logout() => _post('/auth/logout');
  Future<Map<String,dynamic>> updateProfile(Map<String,dynamic> body) => _patch('/auth/profile', data: body);

  // ─── Categories & Config ──────────────────────────
  Future<List<dynamic>> categories() => _get('/categories', parser: (d) => d['data'] as List);
  Future<Map<String,dynamic>> config() => _get('/config');

  // ─── Home ──────────────────────────────────────────
  Future<Map<String,dynamic>> home(double lat, double lng) => _get('/home', query: {'lat': lat.toString(), 'lng': lng.toString()});

  // ─── Search ────────────────────────────────────────
  Future<List<dynamic>> searchSuggestions(String q, {String? type}) => _get('/search/suggestions', query: {'q': q, if (type != null) 'type': type}, parser: (d) => d['data'] as List);

  // ─── Listings ──────────────────────────────────────
  Future<Map<String,dynamic>> listings({required double lat, required double lng, double? radius, int? category, String? type, String? keyword, String sort = 'nearest', int page = 1}) {
    final q = <String,dynamic>{'lat': lat.toString(), 'lng': lng.toString(), 'sort': sort, 'page': page.toString()};
    if (radius != null) q['radius'] = radius.toString();
    if (category != null) q['category'] = category.toString();
    if (type != null) q['type'] = type;
    if (keyword != null && keyword.isNotEmpty) q['keyword'] = keyword;
    return _get('/listings', query: q);
  }
  Future<Map<String,dynamic>> listingDetail(String id) => _get('/listings/$id');
  Future<Map<String,dynamic>> createListing(Map<String,dynamic> body) => _post('/listings', data: body);
  Future<Map<String,dynamic>> updateListing(String id, Map<String,dynamic> body) => _put('/listings/$id', data: body);
  Future<void> deleteListing(String id) => _delete('/listings/$id');
  Future<Map<String,dynamic>> shareListing(String id) => _get('/listings/$id/share');

  // ─── Stores ────────────────────────────────────────
  Future<Map<String,dynamic>> nearbyStores({required double lat, required double lng, double? radius, String? type}) {
    final q = <String,dynamic>{'lat': lat.toString(), 'lng': lng.toString()};
    if (radius != null) q['radius'] = radius.toString();
    if (type != null) q['type'] = type;
    return _get('/stores/nearby', query: q);
  }
  Future<Map<String,dynamic>> mineStores() => _get('/stores/mine');
  Future<Map<String,dynamic>> storeDetail(String id) => _get('/stores/$id');
  Future<Map<String,dynamic>> createStore(Map<String,dynamic> body) => _post('/stores', data: body);
  Future<Map<String,dynamic>> updateStore(String id, Map<String,dynamic> body) => _patch('/stores/$id', data: body);
  Future<Map<String,dynamic>> storeReviews(String id) => _get('/stores/$id/reviews');
  Future<Map<String,dynamic>> storeDashboard(String id) => _get('/stores/$id/dashboard');

  // ─── Favorites ─────────────────────────────────────
  Future<Map<String,dynamic>> favorites() => _get('/favorites');
  Future<void> addFavorite(String id) => _post('/listings/$id/favorite');
  Future<void> removeFavorite(String id) => _delete('/listings/$id/favorite');

  // ─── Notifications ─────────────────────────────────
  Future<Map<String,dynamic>> notifications({int page = 1}) => _get('/notifications', query: {'page': page.toString()});
  Future<Map<String,dynamic>> unreadCount() => _get('/notifications/unread-count');
  Future<void> markRead(String id) => _patch('/notifications/$id/read');
  Future<void> markAllRead() => _patch('/notifications/read-all');
  Future<Map<String,dynamic>> notifPreferences() => _get('/notifications/preferences');
  Future<void> updateNotifPreferences(List<Map<String,dynamic>> channels) => _patch('/notifications/preferences', data: {'channels': channels});

  // ─── Conversations ─────────────────────────────────
  Future<Map<String,dynamic>> conversations() => _get('/conversations');
  Future<Map<String,dynamic>> createConversation(Map<String,dynamic> body) => _post('/conversations', data: body);
  Future<Map<String,dynamic>> messages(String convId, {int page = 1}) => _get('/conversations/$convId/messages', query: {'page': page.toString()});
  Future<Map<String,dynamic>> sendMessage(String convId, String message) => _post('/conversations/$convId/messages', data: {'message': message});

  // ─── Customer Requests ─────────────────────────────
  Future<Map<String,dynamic>> requests({required double lat, required double lng}) => _get('/requests', query: {'lat': lat.toString(), 'lng': lng.toString()});
  Future<Map<String,dynamic>> myRequests({String? status}) => _get('/requests/mine', query: status != null ? {'status': status} : null);
  Future<Map<String,dynamic>> requestDetail(String id) => _get('/requests/$id');
  Future<Map<String,dynamic>> createRequest(Map<String,dynamic> body) => _post('/requests', data: body);
  Future<Map<String,dynamic>> updateRequest(String id, Map<String,dynamic> body) => _patch('/requests/$id', data: body);
  Future<void> deleteRequest(String id) => _delete('/requests/$id');
  Future<Map<String,dynamic>> extendRequest(String id) => _post('/requests/$id/extend');
  Future<Map<String,dynamic>> requestOffers(String reqId, {String sort = 'cheapest'}) => _get('/requests/$reqId/offers', query: {'sort': sort});

  // ─── Offers ────────────────────────────────────────
  Future<Map<String,dynamic>> createOffer(String requestId, Map<String,dynamic> body) => _post('/requests/$requestId/offers', data: body);
  Future<Map<String,dynamic>> offerDetail(String id) => _get('/offers/$id');
  Future<Map<String,dynamic>> acceptOffer(String id) => _patch('/offers/$id/accept');

  // ─── Orders ────────────────────────────────────────
  Future<Map<String,dynamic>> orders({String role = 'buyer', String? status}) => _get('/orders', query: {'role': role, if (status != null) 'status': status});
  Future<Map<String,dynamic>> createOrder(Map<String,dynamic> body) => _post('/orders', data: body);
  Future<Map<String,dynamic>> orderDetail(String id) => _get('/orders/$id');
  Future<Map<String,dynamic>> updateOrderStatus(String id, Map<String,dynamic> body) => _patch('/orders/$id/status', data: body);
  Future<void> uploadPaymentProof(String id, File file) => upload('/orders/$id/payment-proof', file: file, field: 'proof');
  Future<Map<String,dynamic>> submitReview(String id, Map<String,dynamic> body) => _post('/orders/$id/review', data: body);
  Future<void> disputeOrder(String id, Map<String,dynamic> body) => _post('/orders/$id/disputes', data: body);

  // ─── Wallet ────────────────────────────────────────
  Future<Map<String,dynamic>> wallet() => _get('/wallet');
  Future<Map<String,dynamic>> walletTxs() => _get('/wallet/transactions');
  Future<Map<String,dynamic>> topup(double amount) => _post('/wallet/topup', data: {'amount': amount});

  // ─── Addresses ─────────────────────────────────────
  Future<dynamic> addresses() => _get('/addresses');
  Future<Map<String,dynamic>> createAddress(Map<String,dynamic> body) => _post('/addresses', data: body);
  Future<Map<String,dynamic>> updateAddress(String id, Map<String,dynamic> body) => _patch('/addresses/$id', data: body);
  Future<void> deleteAddress(String id) => _delete('/addresses/$id');
  Future<void> setDefaultAddress(String id) => _patch('/addresses/$id/default');

  // ─── Block ─────────────────────────────────────────
  Future<dynamic> blockedUsers() => _get('/users/blocked');
  Future<void> blockUser(String id) => _post('/users/$id/block');
  Future<void> unblockUser(String id) => _delete('/users/$id/block');

  // ─── Reports ───────────────────────────────────────
  Future<void> report(Map<String,dynamic> body) => _post('/reports', data: body);

  // ─── Verification ──────────────────────────────────
  Future<Map<String,dynamic>> uploadKtp(File ktp, File selfie, {String? nik}) => upload('/auth/verification/ktp', file: ktp, field: 'ktp_image');

  // ─── FCM / Device ──────────────────────────────────
  Future<void> registerFcmToken(String token, String deviceId) => _post('/auth/fcm-token', data: {'fcm_token': token, 'device_id': deviceId, 'platform': 'android'});
  Future<void> removeFcmToken(String deviceId) => _delete('/auth/fcm-token?device_id=$deviceId');

  // ─── Privacy (UU PDP) ──────────────────────────────
  Future<Map<String,dynamic>> exportData() => _get('/auth/export-data');
  Future<void> deleteAccount() => _delete('/auth/account');
}
