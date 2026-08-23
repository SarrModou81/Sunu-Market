# SunuMarket

![Backend tests](https://github.com/SarrModou81/Sunu-Market/actions/workflows/backend-tests.yml/badge.svg)

Marketplace mobile dédiée au Sénégal — publication gratuite, Boosts payants, abonnement Vendeur Pro, 0 % de commission sur les ventes.

Cahier des charges de référence : [`docs/Cahier_des_charges_Marketplace_Senegal.pdf`](docs/Cahier_des_charges_Marketplace_Senegal.pdf).

## Structure du monorepo

```
backend/   API REST Laravel (PHP 8.2+) + PostgreSQL + Sanctum
mobile/    Application mobile Flutter
docs/      Cahier des charges, architecture, notes de phase
```

## Architecture technique

- **Mobile** : Flutter
- **Backend** : Laravel (API REST), organisé en Models / Controllers / Services / Form Requests / API Resources / Policies / Notifications
- **Base de données** : PostgreSQL
- **Stockage images** : stockage objet/cloud (S3-compatible) + CDN, upload validé et compressé côté serveur
- **Notifications push** : Firebase Cloud Messaging
- **Authentification** : Laravel Sanctum (tokens API), inscription/connexion par numéro de téléphone + OTP
- **Paiements** : Wave, Orange Money et autres fournisseurs — confirmation exclusivement côté serveur via API/webhook signé

Flux : `Flutter → API Laravel → PostgreSQL + stockage images + services de paiement/notifications`.

## Méthode de développement

Conformément au cahier des charges (section 25), l'application est développée **module par module**, en commençant par l'architecture et la base de données. Chaque phase de la roadmap est développée sur sa propre branche puis fusionnée dans la branche de développement `claude/sunu-market-app-dev-phl9vs` :

| Branche | Contenu |
|---|---|
| `phase-1-architecture-auth` | Architecture Laravel, schéma BDD complet, authentification OTP, profils, rôles |
| `phase-2-catalogue-recherche` | Catégories, annonces, photos, recherche, filtres, tri, rotation des boosts |
| `phase-3-messagerie-favoris-avis` | Messagerie, favoris, avis, signalements |
| `phase-4-boosts-paiements` | Boosts, abonnement Vendeur Pro, paiements, webhooks, expiration automatique |
| `phase-5-admin-dashboard` | Back-office administrateur : modération, statistiques, journaux |
| `phase-6-securite-cicd` | Sécurité renforcée, tests, CI/CD |
| `phase-7-mobile-app` | Application mobile Flutter consommant l'API |

Hors périmètre MVP (voir cahier des charges) : appels audio/vidéo intégrés, livraison complète avec suivi, infrastructure surdimensionnée.

## Démarrage rapide — Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Démarrage rapide — Mobile

```bash
cd mobile
flutter pub get
flutter run
```

## Qualité, sécurité et CI/CD

- `.github/workflows/backend-tests.yml` exécute à chaque push/PR : le contrôle de style (Laravel Pint) et la suite de tests complète contre une vraie base PostgreSQL.
- Sécurité : validation stricte (Form Requests), autorisation par rôle (Policies + middleware `admin`), rate limiting global et par endpoint sensible, en-têtes de sécurité HTTP, whitelist des relations polymorphiques exposées à l'API (`Relation::enforceMorphMap`), webhooks de paiement vérifiés par signature HMAC et traités de façon idempotente.
- Voir [`backend/DEPLOYMENT.md`](backend/DEPLOYMENT.md) pour la configuration de production : secrets, sauvegardes, tâches planifiées, monitoring et passage des paiements en production.
