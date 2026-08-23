import 'dart:io';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exception.dart';
import '../../models/city.dart';
import '../../providers/auth_provider.dart';
import '../../providers/catalog_provider.dart';
import '../../services/auth_service.dart';
import '../../services/profile_service.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late final _firstNameController = TextEditingController(
    text: _user?.profile?.firstName,
  );
  late final _lastNameController = TextEditingController(
    text: _user?.profile?.lastName,
  );
  late final _emailController = TextEditingController(
    text: _user?.profile?.email,
  );
  late final _bioController = TextEditingController(text: _user?.profile?.bio);
  City? _city;
  XFile? _avatar;
  bool _loading = false;
  String? _error;

  get _user => context.read<AuthProvider>().currentUser;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<CatalogProvider>().load(),
    );
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  Future<void> _pickAvatar() async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );
    if (file != null) setState(() => _avatar = file);
  }

  Future<void> _save() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final updated = await context.read<ProfileService>().update(
        firstName: _firstNameController.text,
        lastName: _lastNameController.text,
        email: _emailController.text.isEmpty ? null : _emailController.text,
        cityId: _city?.id,
        bio: _bioController.text,
        avatar: _avatar,
      );
      if (!mounted) return;
      context.read<AuthProvider>().updateUser(updated);
      context.pop();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _deleteAccount() async {
    final passwordController = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer mon compte'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Cette action est irréversible. Confirmez avec votre mot de passe.',
            ),
            const SizedBox(height: 12),
            TextField(
              controller: passwordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Mot de passe'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await context.read<AuthService>().deleteAccount(
        password: passwordController.text,
      );
      if (!mounted) return;
      await context.read<AuthProvider>().logout();
      if (mounted) context.go('/login');
    } on ApiException catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final catalog = context.watch<CatalogProvider>();
    if (_city == null && _user?.profile?.city != null) {
      for (final c in catalog.cities) {
        if (c.id == _user!.profile!.city!.id) {
          _city = c;
          break;
        }
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Modifier mon profil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_error != null) ...[
            Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 12),
          ],
          Center(
            child: GestureDetector(
              onTap: _pickAvatar,
              child: CircleAvatar(
                radius: 40,
                backgroundImage: _avatar != null
                    ? FileImage(File(_avatar!.path))
                    : (_user?.profile?.avatarUrl != null
                              ? NetworkImage(_user!.profile!.avatarUrl!)
                              : null)
                          as ImageProvider?,
                child: (_avatar == null && _user?.profile?.avatarUrl == null)
                    ? const Icon(Icons.camera_alt_outlined)
                    : null,
              ),
            ),
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _firstNameController,
            decoration: const InputDecoration(labelText: 'Prénom'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _lastNameController,
            decoration: const InputDecoration(labelText: 'Nom'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _emailController,
            decoration: const InputDecoration(labelText: 'Email (facultatif)'),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<City>(
            initialValue: _city,
            decoration: const InputDecoration(labelText: 'Ville'),
            items: catalog.cities
                .map((c) => DropdownMenuItem(value: c, child: Text(c.name)))
                .toList(),
            onChanged: (value) => setState(() => _city = value),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _bioController,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'À propos de moi (facultatif)',
            ),
          ),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: _loading ? null : _save,
            child: _loading
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Enregistrer'),
          ),
          const SizedBox(height: 24),
          TextButton(
            onPressed: _deleteAccount,
            child: const Text(
              'Supprimer mon compte',
              style: TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );
  }
}
