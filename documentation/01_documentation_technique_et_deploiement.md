# Documentation Technique Et Deploiement

## 1. Stack technique

- Backend: Symfony 7.4 (PHP 8.2+)
- Front: Twig + CSS + JS natif
- Base relationnelle: MariaDB
- Base non relationnelle: MongoDB
- Mail local: Mailpit
- Tests: PHPUnit (unitaires + fonctionnels)

## 2. Architecture fonctionnelle (resume)

- Public: accueil, menus (filtres dynamiques), detail menu, contact, pages legales
- Auth: inscription, connexion, reset mot de passe
- Utilisateur: profil, edition profil, commandes, suivi, avis, edition/annulation avant acceptation
- Employe/Admin: gestion commandes/statuts, moderation avis, CRUD menus/plats/allergenes/horaires
- Admin: gestion comptes employes + dashboard analytics Mongo (commandes par menu + CA filtre)

## 3. Variables d'environnement

Exemple `.env.local`:

```dotenv
APP_SECRET=<secret_local>
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3307/viteetgourmand?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
MONGODB_URL="mongodb://127.0.0.1:27017"
MONGODB_DB="viteetgourmand_stats"
MAILER_DSN="smtp://127.0.0.1:1025"
```

Points importants:

- `DATABASE_URL` doit etre sur une seule ligne.
- `.env.local` ne se commit pas.

## 4. Installation initiale (one-shot)

A executer une seule fois sur une machine neuve.

### 4.1 Dependances

```bash
composer install
```

### 4.2 Demarrage services

```bash
docker-compose up -d
```

### 4.3 Ports locaux (fixes)

- MariaDB: `127.0.0.1:3307`
- MongoDB: `127.0.0.1:27017`
- Mailpit SMTP: `127.0.0.1:1025`
- Mailpit UI: `http://127.0.0.1:8025`

### 4.4 Initialisation SQL

```bash
mysql -h 127.0.0.1 -P 3307 -u app -p'!ChangeMe!' viteetgourmand < database/schema.sql
mysql -h 127.0.0.1 -P 3307 -u app -p'!ChangeMe!' viteetgourmand < database/seed.sql
```

## 5. Demarrage quotidien (chaque session)

```bash
cd /home/cam/dev/viteetgourmand
docker-compose up -d
php -S 127.0.0.1:8000 -t public
```

Alternatif serveur:

```bash
symfony serve -d
```

Acces:

- App: `http://127.0.0.1:8000`
- Mailpit UI: `http://127.0.0.1:8025`

## 6. Base non relationnelle (MongoDB)

Le dashboard admin utilise MongoDB via une projection des commandes SQL vers une collection `order_stats`.

Donnees projetees:

- `order_id`
- `menu_id`
- `menu_titre`
- `prix_total_centimes`
- `date_commande`

Aggregations exposees:

- nombre de commandes par menu
- chiffre d'affaires par menu avec filtres `menu_id`, `date_from`, `date_to`

## 7. Emails metier

Mails envoyes:

- bienvenue inscription
- creation compte employe
- confirmation commande
- annulation commande
- attente retour materiel
- demande avis commande terminee
- reset mot de passe

En dev, l'envoi est synchrone (`message_bus: false`) pour eviter le besoin d'un worker Messenger.

## 8. Controle qualite

Commandes utiles:

```bash
php bin/console lint:container
php bin/console lint:twig templates
php bin/phpunit
```

## 9. Strategie git

- developpement en branches feature depuis `dev`
- merge PR vers `dev`
- merge `dev` vers `main` sur jalon stable

## 10. Deploiement (trame)

Checklist deploiement:

1. Provisionner un hebergement PHP + MariaDB + MongoDB + SMTP
2. Configurer les variables d'environnement de prod
3. `composer install --no-dev --optimize-autoloader`
4. `php bin/console cache:clear --env=prod`
5. Importer schema SQL + donnees de reference si necessaire
6. Verifier routes critiques + envoi mail + dashboard admin
7. Publier URL de l'application et procedure de rollback
