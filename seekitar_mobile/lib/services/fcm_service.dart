import 'package:firebase_messaging/firebase_messaging.dart';
import '../services/api_client.dart';

class FcmService {
  static final FcmService _instance = FcmService._();
  factory FcmService() => _instance;
  FcmService._();

  final _api = ApiClient();

  Future<void> init() async {
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) await _register(token);
    FirebaseMessaging.instance.onTokenRefresh.listen(_register);
    FirebaseMessaging.onMessage.listen(_onMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_onMessageOpenedApp);
    final initial = await FirebaseMessaging.instance.getInitialMessage();
    if (initial != null) _onMessageOpenedApp(initial);
  }

  Future<void> _register(String token) async {
    try {
      await _api.registerFcmToken(token, 'android_${token.hashCode}');
    } catch (_) {}
  }

  void _onMessage(RemoteMessage msg) {}
  void _onMessageOpenedApp(RemoteMessage msg) {}
}
