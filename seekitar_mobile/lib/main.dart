import 'dart:async';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'core/constants.dart';
import 'core/logger.dart';
import 'core/theme.dart';
import 'providers/app_state.dart';
import 'routing/app_router.dart';
import 'services/fcm_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  _setupErrorHandling();
  // Firebase (untuk FCM). Dijaga agar app tetap jalan walau google-services.json
  // belum terpasang — push notification aktif otomatis setelah file disediakan.
  try {
    await Firebase.initializeApp();
  } catch (e) {
    logError('Firebase init gagal', e);
  }
  final app = AppState();
  unawaited(app.init());
  unawaited(FcmService().init());
  runApp(SeekitarApp(app: app));
}

void _setupErrorHandling() {
  FlutterError.onError = (details) {
    FlutterError.presentError(details);
    logError('Flutter Error', details.exception, details.stack);
  };
  runZonedGuarded(() {}, (error, stack) {
    logError('Uncaught Error', error, stack);
  });
}

class SeekitarApp extends StatelessWidget {
  final AppState app;
  const SeekitarApp({super.key, required this.app});

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(390, 844),
      minTextAdapt: true,
      builder: (_, __) => ChangeNotifierProvider.value(
        value: app,
        child: MaterialApp.router(
          title: AppConstants.appName,
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light,
          themeMode: ThemeMode.light,
          routerConfig: appRouter,
          builder: (ctx, child) {
            if (child == null) return const SizedBox.shrink();
            return child;
          },
        ),
      ),
    );
  }
}
