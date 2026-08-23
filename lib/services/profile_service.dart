import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

import '../core/api/api_client.dart';
import '../models/user.dart';

class ProfileService {
  ProfileService(this._client);

  final ApiClient _client;

  Future<AppUser> update({
    String? firstName,
    String? lastName,
    String? email,
    int? cityId,
    String? bio,
    XFile? avatar,
  }) async {
    final formData = FormData.fromMap({
      '_method': 'PUT',
      if (firstName != null) 'first_name': firstName,
      if (lastName != null) 'last_name': lastName,
      if (email != null) 'email': email,
      if (cityId != null) 'city_id': cityId,
      if (bio != null) 'bio': bio,
      if (avatar != null)
        'avatar': await MultipartFile.fromFile(
          avatar.path,
          filename: avatar.name,
        ),
    });

    // Laravel n'analyse pas les fichiers multipart sur PUT ; on poste avec un
    // champ _method=PUT (convention Laravel de "method spoofing").
    final response = await _client.post('/profile', data: formData);
    return AppUser.fromJson(response.data['user'] as Map<String, dynamic>);
  }
}
