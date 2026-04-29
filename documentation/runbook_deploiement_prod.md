# Runbook Deploiement Production

Ce document decrit une procedure de deploiement exploitable pour livrer l'application en ligne.

## 1. Prerequis infrastructure

- Un hebergement PHP 8.2+ (Nginx/Apache avec `public/` en document root)
- Une base MariaDB
- Une base MongoDB
- Un service SMTP transactionnel
- Acces shell pour lancer les commandes Symfony

## 2. Variables d'environnement

Copier les cles de `.env.prod.example` dans l'environnement de prod:

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET`
- `DEFAULT_URI`
- `DATABASE_URL`
- `MONGODB_URL`
- `MONGODB_DB`
- `MAILER_DSN`
- `CONTACT_RECIPIENT`
- `CONTACT_SENDER`

Important:
- Ne jamais committer les secrets.
- Utiliser des credentials distincts dev/prod.

## 3. Build et installation

Depuis la racine du projet:

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Note Heroku:
- deploiement recommande depuis `main` (`git push heroku main:main`)
- `asset-map:compile` est lance via les auto-scripts Composer

## 4. Initialisation base SQL

```bash
mysql -h <db-host> -P 3306 -u <user> -p viteetgourmand < database/schema.sql
mysql -h <db-host> -P 3306 -u <user> -p viteetgourmand < database/seed.sql
```

Puis synchroniser les images de seed:

```bash
./scripts/sync_seed_menu_images.sh
```

## 5. Verification fonctionnelle post-deploiement

Verifier rapidement les routes publiques:

```bash
./scripts/smoke_test_http.sh https://<url-prod>
```

Verifier aussi manuellement:
- login user, employe, admin
- creation commande et mail de confirmation
- changement de statut cote employe et mails associes
- dashboard admin (MongoDB)
- chargement des assets front (`/assets/*` en 200)

## 6. Point critique mail en production

Le projet force l'envoi synchrone des mails en `prod` via:

- `config/packages/prod/mailer.yaml` (`message_bus: false`)

Objectif: ne pas dependre d'un worker Messenger pour les emails metier.

## 7. Procedure de rollback minimale

1. Revenir au commit precedent stable.
2. Vider/regen cache prod:
   - `php bin/console cache:clear --env=prod`
3. Recharger la version applicative precedente.
4. Rejouer le smoke test HTTP.
