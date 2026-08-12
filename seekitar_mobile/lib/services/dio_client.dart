import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants.dart';
import '../core/logger.dart';

class DioClient {
  static final DioClient _instance = DioClient._();
  factory DioClient() => _instance;
  DioClient._() {
    if (AppConstants.baseUrl.isEmpty) {
      throw StateError(
        'API_BASE_URL belum diatur. Bangun ulang dengan '
        '--dart-define=API_BASE_URL=http://<host>:<port>/api/v1',
      );
    }
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
      // 423 = akun diblokir. Token dihapus supaya tidak terus dicoba &
      // sesi dianggap berakhir (kembali ke login saat app dibuka ulang).
      if (error.response?.statusCode == 423 && _token != null) {
        await clearToken();
      }
      if (error.response?.statusCode == 401 && _token != null) {
        try {
          final refreshDio = Dio(BaseOptions(baseUrl: AppConstants.baseUrl));
          refreshDio.options.headers['Authorization'] = 'Bearer $_token';
          final res = await refreshDio.post('/auth/refresh');
          final data = res.data is Map ? res.data['data'] : null;
          final newToken = data is Map ? data['token']?.toString() : null;
          if (newToken == null) throw Exception('Refresh gagal');
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
    // Hanya retry request idempotent (GET/HEAD). POST/PUT/PATCH/DELETE
    // tidak aman diulang: bisa menggandakan OTP, listing, order, dst.
    final method = error.requestOptions.method.toUpperCase();
    if (method != 'GET' && method != 'HEAD') return false;
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

  /// Pesan ramah untuk user — satu pintu untuk SEMUA penanganan error di UI.
  /// Pesan teknis mentah (mis. teks timeout dari Dio) TIDAK boleh tampil ke
  /// pengguna. DioException dipetakan ke Bahasa Indonesia; error lain cukup
  /// pesan generik.
  static String friendly(Object e) {
    if (e is DioException) {
      return getMessage(e);
    }
    return 'Terjadi kesalahan. Coba lagi nanti.';
  }

  /// Pesan ramah untuk DioException.
  static String getMessage(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      return 'Waktu tunggu server habis. Periksa koneksi lalu coba lagi.';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet.';
    }
    if (e.response?.data is Map) {
      return (e.response!.data as Map)['message']?.toString() ?? 'Terjadi kesalahan. Coba lagi nanti.';
    }
    return 'Terjadi kesalahan. Coba lagi nanti.';
  }
}
