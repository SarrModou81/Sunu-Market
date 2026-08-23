import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/theme/app_theme.dart';
import '../../models/user.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.currentUser;

    if (user == null) return const Scaffold(body: LoadingView());

    final seller = user.sellerProfile;

    return Scaffold(
      appBar: AppBar(title: const Text('Mon profil')),
      body: ListView(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 32,
                  backgroundImage: user.profile?.avatarUrl != null
                      ? CachedNetworkImageProvider(user.profile!.avatarUrl!)
                      : null,
                  child: user.profile?.avatarUrl == null
                      ? const Icon(Icons.person, size: 32)
                      : null,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              user.profile?.fullName ?? user.phone,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          if (seller != null && seller.isPro) ...[
                            const SizedBox(width: 6),
                            const ProBadge(),
                          ],
                        ],
                      ),
                      Text(
                        user.phone,
                        style: const TextStyle(color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.edit_outlined),
                  onPressed: () => context.push('/profile/edit'),
                ),
              ],
            ),
          ),
          if (seller != null) _SellerStatsRow(seller: seller),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.inventory_2_outlined),
            title: const Text('Mes annonces'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/my-products'),
          ),
          ListTile(
            leading: const Icon(Icons.favorite_border),
            title: const Text('Mes favoris'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/favorites'),
          ),
          ListTile(
            leading: const Icon(Icons.notifications_none),
            title: const Text('Notifications'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/notifications'),
          ),
          if (seller != null && !seller.isPro)
            ListTile(
              leading: const Icon(
                Icons.workspace_premium_outlined,
                color: AppColors.primary,
              ),
              title: const Text('Passer au compte Pro'),
              subtitle: const Text(
                'Annonces illimitées, statistiques, badge Pro',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push('/subscription/pro'),
            ),
          ListTile(
            leading: const Icon(Icons.person_outline),
            title: Text('Profil public (${user.id})'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/seller/${user.id}'),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: AppColors.danger),
            title: const Text(
              'Se déconnecter',
              style: TextStyle(color: AppColors.danger),
            ),
            onTap: () => context.read<AuthProvider>().logout(),
          ),
        ],
      ),
    );
  }
}

class _SellerStatsRow extends StatelessWidget {
  const _SellerStatsRow({required this.seller});

  final SellerProfile seller;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: _StatTile(
              label: 'Note',
              value: seller.ratingAverage.toStringAsFixed(1),
            ),
          ),
          Expanded(
            child: _StatTile(label: 'Avis', value: '${seller.ratingCount}'),
          ),
          Expanded(
            child: _StatTile(
              label: 'Type',
              value: seller.isPro ? 'Pro' : 'Standard',
            ),
          ),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
      ],
    );
  }
}
