import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants.dart';
import '../core/logger.dart';

class DioClient {
  static final DioClient _instance = DioClient._();
  factory DioClient() => _instance;
  DioClient._() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: AppConstants.connectTimeout,
      receiveTimeout: AppConstants.receiveTimeout,
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
    ));
    _dio.interceptors.addAll([_authInterceptor(), _retryInterceptor(), _logInterceptor()]);
  }

  late final Dio _dio;
  final _storage = const FlutterSecureStorage();
  String? _token;

  Dio get dio => _dio;
  String? get token => _token;

  Future<void> setToken(String t) async {
    _token = t;
    await _storage.write(key: 'jwt_token', value: t);
  }

  Future<void> loadToken() async {
    _token = await _storage.read(key: 'jwt_token');
  }

  Future<void> clearToken() async {
    _token = null;
    await _storage.delete(key: 'jwt_token');
  }

  InterceptorsWrapper _authInterceptor() => InterceptorsWrapper(
    onRequest: (options, handler) {
      if (_token != null) options.headers['Authorization'] = 'Bearer $_token';
      handler.next(options);
    },
    onError: (error, handler) async {
      if (error.response?.statusCode == 401 && _token != null) {
        try {
          final refreshDio = Dio(BaseOptions(baseUrl: AppConstants.baseUrl));
          refreshDio.options.headers['Authorization'] = 'Bearer $_token';
          final res = await refreshDio.post('/auth/refresh');
          final newToken = res.data['token'] as String;
          await setToken(newToken);
          error.requestOptions.headers['Authorization'] = 'Bearer $newToken';
          final retry = await _dio.fetch(error.requestOptions);
          handler.resolve(retry);
          return;
        } catch (_) {
          await clearToken();
        }
      }
      handler.next(error);
    },
  );

  InterceptorsWrapper _retryInterceptor() => InterceptorsWrapper(
    onError: (error, handler) async {
      if (_shouldRetry(error) && (error.requestOptions.extra['retryCount'] ?? 0) < AppConstants.maxRetries) {
        error.requestOptions.extra['retryCount'] = (error.requestOptions.extra['retryCount'] ?? 0) + 1;
        await Future.delayed(Duration(seconds: error.requestOptions.extra['retryCount'] as int));
        final retry = await _dio.fetch(error.requestOptions);
        handler.resolve(retry);
        return;
      }
      handler.next(error);
    },
  );

  bool _shouldRetry(DioException error) {
    return error.type == DioExceptionType.connectionTimeout ||
           error.type == DioExceptionType.receiveTimeout ||
           error.type == DioExceptionType.connectionError;
  }

  InterceptorsWrapper _logInterceptor() => InterceptorsWrapper(
    onRequest: (options, handler) {
      logInfo('➡️ ${options.method} ${options.path}');
      handler.next(options);
    },
    onResponse: (response, handler) {
      logInfo('✅ ${response.statusCode} ${response.requestOptions.path}');
      handler.next(response);
    },
    onError: (error, handler) {
      logError('❌ ${error.response?.statusCode} ${error.requestOptions.path}', error.message);
      handler.next(error);
    },
  );

  String getMessage(DioException e) {
    if (e.response?.data is Map) {
      return (e.response!.data as Map)['message']?.toString() ?? 'Terjadi kesalahan';
    }
    return e.message ?? 'Terjadi kesalahan';
  }
}
