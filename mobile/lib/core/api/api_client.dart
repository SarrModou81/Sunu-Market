import 'package:dio/dio.dart';

import 'api_exception.dart';
import 'app_config.dart';
import '../storage/token_storage.dart';

/// Enveloppe Dio unique pour tous les appels à l'API SunuMarket : ajoute le
/// jeton Sanctum sur chaque requête et normalise les erreurs en [ApiException].
class ApiClient {
  ApiClient(this._tokenStorage)
    : dio = Dio(
        BaseOptions(
          baseUrl: AppConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 15),
          headers: {'Accept': 'application/json'},
        ),
      ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.readToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          handler.next(error);
        },
      ),
    );
  }

  final Dio dio;
  final TokenStorage _tokenStorage;

  /// Callback optionnel déclenché sur une réponse 401 (jeton expiré/révoqué),
  /// pour rediriger vers l'écran de connexion depuis l'application.
  void Function()? onUnauthorized;

  Future<Response<dynamic>> get(String path, {Map<String, dynamic>? query}) =>
      _wrap(() => dio.get(path, queryParameters: query));

  Future<Response<dynamic>> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? query,
  }) => _wrap(() => dio.post(path, data: data, queryParameters: query));

  Future<Response<dynamic>> put(String path, {dynamic data}) =>
      _wrap(() => dio.put(path, data: data));

  Future<Response<dynamic>> patch(String path, {dynamic data}) =>
      _wrap(() => dio.patch(path, data: data));

  Future<Response<dynamic>> delete(String path, {dynamic data}) =>
      _wrap(() => dio.delete(path, data: data));

  Future<Response<dynamic>> _wrap(
    Future<Response<dynamic>> Function() request,
  ) async {
    try {
      return await request();
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  ApiException _toApiException(DioException e) {
    final statusCode = e.response?.statusCode;

    if (statusCode == 401) {
      onUnauthorized?.call();
    }

    final data = e.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'] as String? ?? 'Une erreur est survenue.';
      Map<String, List<String>>? fieldErrors;
      final rawErrors = data['errors'];
      if (rawErrors is Map<String, dynamic>) {
        fieldErrors = rawErrors.map(
          (key, value) =>
              MapEntry(key, (value as List).map((e) => e.toString()).toList()),
        );
      }
      return ApiException(
        message: message,
        statusCode: statusCode,
        fieldErrors: fieldErrors,
      );
    }

    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.connectionError) {
      return ApiException(
        message:
            'Impossible de contacter le serveur. Vérifiez votre connexion.',
      );
    }

    return ApiException(
      message: 'Une erreur inattendue est survenue.',
      statusCode: statusCode,
    );
  }
}
