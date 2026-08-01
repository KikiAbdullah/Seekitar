import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import '../services/api_client.dart';

class FcmService {
  static final FcmService _instance = FcmService._();
  factory FcmService() => _instance;
  FcmService._();

  final _api = ApiClient();
  final _local = FlutterLocalNotificationsPlugin();

  Future<void> init() async {
    // Android channel
    const androidChannel = AndroidNotificationChannel('seekitar', 'Seekitar', description: 'Notifikasi Seekitar', importance: Importance.high);
    await _local.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(androidChannel);

    const initSettings = InitializationSettings(android: AndroidInitializationSettings('@mipmap/ic_launcher'), iOS: DarwinInitializationSettings());
    await _local.initialize(initSettings);

    // FCM token
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) await _register(token);
    FirebaseMessaging.instance.onTokenRefresh.listen(_register);

    // Foreground messages
    FirebaseMessaging.onMessage.listen(_showLocalNotification);

    // Background/terminated tap
    FirebaseMessaging.onMessageOpenedApp.listen(_onTap);
    final initial = await FirebaseMessaging.instance.getInitialMessage();
    if (initial != null) _onTap(initial);
  }

  Future<void> _register(String token) async {
    try { await _api.registerFcmToken(token, 'android_${token.hashCode}'); } catch (_) {}
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

  void _onTap(RemoteMessage msg) {
    // Navigate handled by go_router via initial route or deep link
  }
}
