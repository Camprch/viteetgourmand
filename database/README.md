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

## Comptes de test (seed)

Tous les comptes de `database/seed.sql` utilisent le meme mot de passe:

- Mot de passe: `Test1234!`
- Admin: `admin@vitegourmand.local`, `jose@vitegourmand.local`
- Employe: `employee@vitegourmand.local`
- Utilisateur: `user1@example.com`, `user2@example.com`
