# Déploiement, sécurité et exploitation

Ce document couvre les points opérationnels du cahier des charges (section 21 :
sécurité, sauvegardes, gestion des secrets) qui dépendent de l'infrastructure de
production plutôt que du code applicatif.

## Secrets et configuration

- Ne jamais committer `.env`. Seul `.env.example` (sans valeurs réelles) est versionné.
- En production, injecter les secrets (clés API Wave/Orange Money, `APP_KEY`,
  identifiants base de données) via les variables d'environnement de la plateforme
  d'hébergement (secrets manager), jamais en dur dans le code ou l'application mobile.
- Générer un `APP_KEY` unique par environnement : `php artisan key:generate --force`.
- `APP_DEBUG` doit toujours être à `false` en production (évite la fuite de traces
  d'erreurs détaillées).
- Si l'API est servie derrière un reverse proxy/load balancer, renseigner
  `TRUSTED_PROXIES` (voir `.env.example`) pour que le rate limiting par IP et la
  détection HTTPS (`$request->secure()`) restent fiables.

## Base de données et sauvegardes

- PostgreSQL est la base de données cible. Prévoir des sauvegardes automatiques
  régulières (`pg_dump` planifié, ou sauvegardes managées si base hébergée), avec
  rétention (ex: quotidienne sur 14 jours, hebdomadaire sur 3 mois) et test de
  restauration périodique.
- Les images (annonces, avatars, pièces jointes de messages) sont stockées sur le
  disque `public` (`storage/app/public`) en développement. En production, configurer
  un disque objet (S3-compatible) via `config/filesystems.php` et `FILESYSTEM_DISK`,
  qui bénéficie généralement de la réplication/durabilité du fournisseur cloud plutôt
  que d'une sauvegarde manuelle du disque local.

## Tâches planifiées

Les commandes suivantes doivent tourner via le scheduler Laravel (`routes/console.php`)
en production, ce qui nécessite une entrée cron unique :

```
* * * * * cd /chemin/vers/backend && php artisan schedule:run >> /dev/null 2>&1
```

Elle déclenche `boosts:expire`, `boosts:notify-expiring` et `subscriptions:expire`
aux fréquences définies dans `routes/console.php`.

## Files d'attente

Les notifications sont envoyées de façon synchrone dans ce MVP (volumétrie limitée).
Si le trafic augmente, activer `QUEUE_CONNECTION=redis` (Redis est déjà utilisé pour
le cache) et faire implémenter `ShouldQueue` aux classes de `App\Notifications`, puis
exécuter un worker (`php artisan queue:work`) supervisé (systemd/Supervisor).

## Monitoring et logs

- Les logs applicatifs (`storage/logs/laravel.log`) doivent être expédiés vers un
  système centralisé en production (ex: agrégateur de logs du fournisseur cloud)
  plutôt que de rester uniquement sur le disque local.
- Prévoir une alerte sur le endpoint de santé `GET /up` (fourni nativement par
  Laravel) pour la supervision de disponibilité.
- Surveiller en particulier : le taux d'échec des webhooks de paiement
  (`payment_transactions.status = 'failed'`), le volume de signalements en attente
  (`GET /api/admin/stats`), et les tentatives de connexion échouées (protégées par
  rate limiting mais à surveiller pour détecter du bourrage de compte).

## Paiements — passage en production

`PAYMENTS_DRIVER=fake` (par défaut en développement) doit être retiré en production :
`PaymentGatewayFactory` refuse explicitement ce pilote lorsque `APP_ENV=production`.
Avant la mise en production réelle :

1. Obtenir des comptes marchands (Wave, Orange Money, et/ou PayTech qui agrège les deux
   plus la carte bancaire derrière une seule API), renseigner leurs identifiants
   (`WAVE_API_KEY`, `ORANGE_MONEY_CLIENT_ID`/`CLIENT_SECRET`/`MERCHANT_KEY`,
   `PAYTECH_API_KEY`/`PAYTECH_API_SECRET`) et secrets de webhook (`WAVE_WEBHOOK_SECRET`,
   `ORANGE_MONEY_WEBHOOK_SECRET` — PayTech n'utilise pas de secret séparé, sa vérification
   IPN repose sur le hash SHA-256 de `PAYTECH_API_KEY`/`PAYTECH_API_SECRET` eux-mêmes).
2. Valider `App\Services\Payments\WaveGateway`, `OrangeMoneyGateway` et `PaytechGateway`
   contre l'environnement sandbox officiel de chaque fournisseur — leur implémentation
   suit la structure publique documentée de ces API mais n'a pas pu être testée contre de
   vrais comptes marchands dans cet environnement de développement. Pour PayTech en
   particulier, faire un paiement test réel (`PAYTECH_ENV=test`) et comparer le payload
   IPN effectivement reçu à `PaytechGateway::parseWebhook()` avant la mise en production.
3. Configurer les URLs de webhook (`/api/webhooks/wave`, `/api/webhooks/orange-money`,
   `/api/webhooks/paytech`) côté tableau de bord marchand de chaque fournisseur, et
   `PAYTECH_IPN_URL` côté `.env` avec la même URL publique HTTPS.

## CI/CD

Le workflow GitHub Actions `.github/workflows/backend-tests.yml` (à la racine du
dépôt) exécute à chaque push/PR touchant `backend/` : le contrôle de style (Laravel
Pint) et la suite de tests complète contre une vraie base PostgreSQL.
