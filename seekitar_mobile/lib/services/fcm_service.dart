import 'dart:math';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/logger.dart';
import '../routing/app_router.dart';
import '../services/api_client.dart';

class FcmService {
  static final FcmService _instance = FcmService._();
  factory FcmService() => _instance;
  FcmService._();

  final _api = ApiClient();
  final _local = FlutterLocalNotificationsPlugin();
  String? _deviceId;

  Future<void> init() async {
    // Firebase/FLN belum tersedia (mis. google-services.json belum terpasang) —
    // jangan ganggu startup aplikasi; push menyala otomatis setelah dikonfigurasi.
    try {
      await _setupLocalNotifications();
      await _requestNotificationPermission();

      final token = await FirebaseMessaging.instance.getToken();
      if (token != null) await _register(token);
      FirebaseMessaging.instance.onTokenRefresh.listen((t) => _register(t));

      FirebaseMessaging.onMessage.listen(_showLocalNotification);
      FirebaseMessaging.onMessageOpenedApp.listen(_onTap);
      final initial = await FirebaseMessaging.instance.getInitialMessage();
      if (initial != null) _onTap(initial);
    } catch (e) {
      logError('FCM init gagal', e);
    }
  }

  Future<void> _setupLocalNotifications() async {
    const androidChannel = AndroidNotificationChannel('seekitar', 'Seekitar', description: 'Notifikasi Seekitar', importance: Importance.high);
    await _local.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(androidChannel);

    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    );
    await _local.initialize(initSettings);
  }

  /// Android 13+ (API 33) mewajibkan izin POST_NOTIFICATIONS.
  Future<void> _requestNotificationPermission() async {
    await _local
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.requestNotificationsPermission();
  }

  /// device_id STABIL (persisten) — bukan hash token yang berubah tiap proses.
  /// Server memakai device_id sebagai kunci: satu ponsel → satu baris perangkat.
  Future<String> _getDeviceId() async {
    if (_deviceId != null) return _deviceId!;
    final prefs = await SharedPreferences.getInstance();
    var id = prefs.getString('device_id');
    if (id == null) {
      final rand = Random().nextInt(0xFFFFFF).toRadixString(16).padLeft(6, '0');
      id = 'android_${DateTime.now().microsecondsSinceEpoch}_$rand';
      await prefs.setString('device_id', id);
    }
    return _deviceId = id;
  }

  Future<void> _register(String token) async {
    try {
      await _api.registerFcmToken(token, await _getDeviceId());
    } catch (_) {}
  }

  Future<void> _showLocalNotification(RemoteMessage msg) async {
    await _local.show(
      msg.hashCode,
      msg.notification?.title ?? 'Seekitar',
      msg.notification?.body ?? '',
      const NotificationDetails(
        android: AndroidNotificationDetails('seekitar', 'Seekitar', channelDescription: 'Notifikasi Seekitar', importance: Importance.high, priority: Priority.high),
        iOS: DarwinNotificationDetails(),
      ),
      payload: msg.data.isNotEmpty ? msg.data.toString() : null,
    );
  }

  /// Tap notifikasi → navigasi ke route dari payload `data.route` server.
  /// Route harus path go_router valid (mis. `/order-detail/123`); tanpa route
  /// dikirim server, fallback ke halaman utama.
  void _onTap(RemoteMessage msg) {
    final route = msg.data['route']?.toString();
    if (route != null && route.startsWith('/')) {
      appRouter.go(route);
    } else {
      appRouter.go('/home');
    }
  }
}
