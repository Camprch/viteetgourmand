# Vite & Gourmand

Application web de gestion et commande de menus traiteur, realisee dans le cadre de l'ECF RNCP Developpeur Web et Web Mobile.

## 1. Stack technique

- Backend: Symfony 7.4 (PHP 8.2+)
- Frontend: Twig + CSS + JavaScript natif
- Base relationnelle: MariaDB
- Base non relationnelle: MongoDB
- Mail local (dev): Mailpit
- Tests: PHPUnit (unitaires + fonctionnels)

## 2. Fonctionnalites principales

- Public: accueil, menus (liste + filtres dynamiques sans reload), detail menu, contact, pages legales
- Authentification: inscription, connexion/deconnexion, reinitialisation mot de passe
- Espace utilisateur: profil, commandes, suivi statuts, annulation/modification (selon regles metier), avis
- Espace employe: gestion commandes/statuts, moderation avis, CRUD horaires/menus/plats/allergenes
- Espace administrateur: gestion comptes employes + analytics MongoDB (commandes par menu, CA par menu/periode)
- Emails metier: inscription, reset password, confirmation commande, annulation, attente retour materiel, demande avis

## 3. Prerequis

- PHP 8.2 ou plus
- Composer
- Docker + Docker Compose
- Client MySQL/MariaDB (`mysql`)

Optionnel mais recommande pour la gestion d'images:

- Extension PHP `gd`

## 4. Installation locale

### 4.1 Cloner et installer les dependances

```bash
git clone <url-du-repo>
cd viteetgourmand
composer install
```

### 4.2 Configurer les variables d'environnement

Creer/adapter `.env.local`:

```dotenv
APP_SECRET=<secret_local>
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3307/viteetgourmand?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
MONGODB_URL="mongodb://127.0.0.1:27017"
MONGODB_DB="viteetgourmand_stats"
MAILER_DSN="smtp://127.0.0.1:1025"
```

### 4.3 Demarrer les services

```bash
docker-compose up -d
```

Ports locaux:

- MariaDB: `127.0.0.1:3307`
- MongoDB: `127.0.0.1:27017`
- Mailpit SMTP: `127.0.0.1:1025`
- Mailpit UI: `http://127.0.0.1:8025`

### 4.4 Initialiser la base SQL

```bash
mysql -h 127.0.0.1 -P 3307 -u app -p'!ChangeMe!' viteetgourmand < database/schema.sql
mysql -h 127.0.0.1 -P 3307 -u app -p'!ChangeMe!' viteetgourmand < database/seed.sql
```

Synchroniser les images seed:

```bash
./scripts/sync_seed_menu_images.sh
```

## 5. Lancer l'application

```bash
php -S 127.0.0.1:8000 -t public
```

Alternative:

```bash
symfony serve -d
```

Acces:

- Application: `http://127.0.0.1:8000`
- Mailpit: `http://127.0.0.1:8025`

## 6. Comptes de test (seed)

Mot de passe commun: `Test1234!`

- Admin: `admin@vitegourmand.local`
- Admin: `jose@vitegourmand.local`
- Employe: `employee@vitegourmand.local`
- Utilisateur: `user1@example.com`
- Utilisateur: `user2@example.com`

## 7. Controle qualite

```bash
php bin/console lint:container
php bin/console lint:twig templates
php bin/phpunit
```

## 8. Securite (resume)

- Hash des mots de passe via Symfony Security
- Controle d'acces par roles (`USER`, `EMPLOYEE`, `ADMIN`)
- Protection CSRF sur formulaires
- Validation serveur des donnees de formulaire
- Regles metier appliquees cote backend (edition/annulation, statuts, calculs)

## 9. Workflow Git

Convention attendue pour l'ECF:

1. Branche principale: `main`
2. Branche integration: `dev`
3. Chaque fonctionnalite part de `dev` vers `feature/<nom>`
4. Merge feature -> `dev` apres verification
5. Merge `dev` -> `main` quand le lot est valide

## 10. Deploiement (trame)

1. Provisionner un hebergement PHP + MariaDB + MongoDB + SMTP
2. Configurer les variables d'environnement de production
3. Executer `composer install --no-dev --optimize-autoloader`
4. Executer `php bin/console cache:clear --env=prod`
5. Initialiser la base SQL (`database/schema.sql`, puis `database/seed.sql` si necessaire)
6. Verifier les parcours critiques (public, auth, user, employe, admin)
7. Verifier envoi des mails et analytics MongoDB

Pour Heroku (workflow recommande):

- pousser `main` vers Heroku (`git push heroku main:main`)
- laisser les auto-scripts Composer compiler les assets (`asset-map:compile`)

Variables de prod:

- Utiliser `.env.prod.example` comme base (sans committer les secrets)

Smoke test HTTP post-deploiement:

```bash
./scripts/smoke_test_http.sh https://<url-prod>
```

## 11. Documentation complementaire

- Documentation technique/deploiement: `documentation/documentation_technique_et_deploiement.md`
- Manuel utilisateur: `documentation/manuel_utilisateur.md`
- Runbook deploiement prod: `documentation/runbook_deploiement_prod.md`
- Charte graphique: `documentation/charte_graphique.md`
- MCD: `documentation/mcd.png`
- SQL: `database/schema.sql` et `database/seed.sql`
