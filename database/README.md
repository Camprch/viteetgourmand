# Database SQL

## Prerequis
- MariaDB en local (port 3306)
- Base `viteetgourmand` creee

## Executer le schema
```bash
mysql -u app -p viteetgourmand < database/schema.sql
```

## Notes
- `database/schema.sql` est la reference SQL attendue pour l'ECF.
- Les donnees de test seront ajoutees dans `database/seed.sql`.

## Charger les donnees de test
```bash
mysql -u app -p viteetgourmand < database/seed.sql
```
