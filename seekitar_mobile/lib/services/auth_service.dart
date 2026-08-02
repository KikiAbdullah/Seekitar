import 'package:flutter/material.dart';
import '../core/logger.dart';
import '../models/user.dart';
import 'dio_client.dart';
import 'api_client.dart';

class AuthService extends ChangeNotifier {
  static final AuthService _instance = AuthService._();
  factory AuthService() => _instance;
  AuthService._();

  final DioClient _dio = DioClient();
  final ApiClient _api = ApiClient();

  User? _user;
  bool _loading = true;

  User? get user => _user;
  bool get isLoggedIn => _user != null && _dio.token != null;
  bool get isLoading => _loading;

  Future<void> init() async {
    _loading = true;
    notifyListeners();
    await _dio.loadToken();
    if (_dio.token != null) {
      try {
        final res = await _api.me();
        _user = User.fromJson(res['user'] as Map<String, dynamic>);
        logInfo('Auto-login OK: ${_user?.phone}');
      } catch (e) {
        logError('Auto-login failed', e);
        await _dio.clearToken();
      }
    }
    _loading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>> requestOtp(String phone) => _api.requestOtp(phone);

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final res = await _api.verifyOtp(phone, otp);
    final token = res['token'] as String;
    await _dio.setToken(token);
    _user = User.fromJson(res['user'] as Map<String, dynamic>);
    notifyListeners();
    return res;
  }

  Future<void> refreshToken() async {
    try {
      final res = await _api.refreshToken();
      await _dio.setToken(res['token'] as String);
    } catch (_) {
      await logout();
    }
  }

  Future<void> logout() async {
    try { await _api.logout(); } catch (_) {}
    await _dio.clearToken();
    _user = null;
    notifyListeners();
  }

  Future<void> updateProfile({String? name, String? address, double? lat, double? lng}) async {
    final body = <String, dynamic>{};
    if (name != null) body['name'] = name;
    if (address != null) body['address'] = address;
    if (lat != null) body['latitude'] = lat;
    if (lng != null) body['longitude'] = lng;
    final res = await _api.updateProfile(body);
    _user = User.fromJson(res['user'] as Map<String, dynamic>);
    notifyListeners();
  }
}
