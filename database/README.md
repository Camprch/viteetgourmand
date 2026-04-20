# Database SQL

## Prerequis
- MariaDB en local (port 3306)
- Base `viteetgourmand` creee

## Executer le schema
```bash
mysql -u app -p viteetgourmand < database/schema.sql
```

Attention: cette commande recree les tables (DROP/CREATE) et ecrase les donnees existantes.

## Notes
- `database/schema.sql` est la reference SQL attendue pour l'ECF.
- Les donnees de test seront ajoutees dans `database/seed.sql`.

## Charger les donnees de test
```bash
mysql -u app -p viteetgourmand < database/seed.sql
```

## Images des menus (seed)

Les lignes `menu_image.url` de `database/seed.sql` pointent vers:

- `/images/menus/classique-1.png`
- `/images/menus/vegan-1.png`
- `/images/menus/noel-1.png`

Sources versionnees:

- `database/seed-assets/menus/`

Destination web servie par Symfony:

- `public/images/menus/`

Synchroniser les images seed vers `public/`:

```bash
./scripts/sync_seed_menu_images.sh
```

## Upload images (back-office)

- Destination des uploads: `public/uploads/menus/`
- Limite validation: 2 Mo/fichier
- Compression/redimensionnement automatiques si extension PHP `gd` active

Verifier `gd`:

```bash
php -m | rg -i gd
```

Installer `gd` (Ubuntu + PHP 8.3):

```bash
sudo apt update
sudo apt install php8.3-gd
```

Apres installation, redemarrer ton runtime PHP (`php -S`/`symfony serve`/`php-fpm`) pour activer la compression.

## Comptes de test (seed)

Tous les comptes de `database/seed.sql` utilisent le meme mot de passe:

- Mot de passe: `Test1234!`
- Admin: `admin@vitegourmand.local`, `jose@vitegourmand.local`
- Employe: `employee@vitegourmand.local`
- Utilisateur: `user1@example.com`, `user2@example.com`
