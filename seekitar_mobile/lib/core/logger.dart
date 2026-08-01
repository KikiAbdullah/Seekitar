import 'package:logger/logger.dart';

final appLogger = Logger(
  printer: PrettyPrinter(
    methodCount: 1,
    errorMethodCount: 5,
    lineLength: 80,
    colors: true,
    printEmojis: false,
  ),
  level: Level.debug,
);

void logInfo(String msg) => appLogger.i(msg);
void logWarn(String msg) => appLogger.w(msg);
void logError(String msg, [dynamic error, StackTrace? stack]) => appLogger.e(msg, error: error, stackTrace: stack);
