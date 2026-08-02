import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'core/constants.dart';
import 'core/logger.dart';
import 'core/theme.dart';
import 'providers/app_state.dart';
import 'routing/app_router.dart';
import 'services/fcm_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  _setupErrorHandling();
  final app = AppState();
  app.init();
  FcmService().init().catchError((_) {});
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
          darkTheme: AppTheme.dark,
          themeMode: ThemeMode.system,
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
