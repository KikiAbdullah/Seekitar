import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/api_client.dart';

class AppState extends ChangeNotifier {
  final AuthService _auth = AuthService();
  final ApiClient _api = ApiClient();

  User? get user => _auth.user;
  bool get isLoggedIn => _auth.isLoggedIn;
  bool get isLoading => _auth.isLoading;

  int _unreadNotif = 0;
  int get unreadNotif => _unreadNotif;

  Future<void> init() => _auth.init();

  Future<Map<String, dynamic>> requestOtp(String phone) => _auth.requestOtp(phone);
  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) => _auth.verifyOtp(phone, otp);
  Future<void> logout() => _auth.logout();
  Future<void> updateProfile({String? name, String? address, double? lat, double? lng}) => _auth.updateProfile(name: name, address: address, lat: lat, lng: lng);

  Future<void> fetchUnreadCount() async {
    try {
      final res = await _api.unreadCount();
      _unreadNotif = (res['count'] as num?)?.toInt() ?? 0;
      notifyListeners();
    } catch (_) {}
  }
}
