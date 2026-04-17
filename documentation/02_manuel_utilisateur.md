# Manuel Utilisateur

## 1. Objectif

Ce manuel explique les parcours principaux pour les roles User, Employe et Admin.

## 2. Acces application

- URL locale: `http://127.0.0.1:8000`
- Mailpit (mails): `http://127.0.0.1:8025`

## 3. Comptes de test

- `admin@vitegourmand.local` (admin)
- `jose@vitegourmand.local` (admin)
- `employee@vitegourmand.local` (employe)
- `user1@example.com` (utilisateur)
- `user2@example.com` (utilisateur)

Si mot de passe inconnu, utiliser le reset via `/reset-password`.

## 4. Parcours visiteur

1. Ouvrir l'accueil `/`
2. Consulter `/menus`
3. Utiliser les filtres (theme, regime, prix, personnes)
4. Ouvrir un detail menu `/menus/{id}`
5. Contacter l'entreprise via `/contact`

## 5. Parcours utilisateur connecte

1. Se connecter via `/login`
2. Consulter/editer son profil via `/profile` et `/profile/edit`
3. Commander un menu via `/orders/new/{id}`
4. Suivre ses commandes via `/profile/orders` et `/profile/orders/{id}`
5. Modifier/annuler une commande tant que non acceptee
6. Deposer un avis apres commande terminee

## 6. Parcours employe

1. Ouvrir `/employee/orders`
2. Filtrer les commandes par client/statut
3. Mettre a jour les statuts d'une commande
4. Moderer les avis via `/employee/reviews`
5. Gerer horaires, menus, plats, allergenes

## 7. Parcours administrateur

1. Ouvrir `/admin`
2. Creer un compte employe via `/admin/employees/new`
3. Activer/desactiver un compte employe
4. Consulter analytics:
   - comparatif nb commandes par menu
   - chiffre d'affaires par menu avec filtres periode

## 8. Mails attendus

- inscription user: mail bienvenue
- creation employe: mail notification employe
- commande user: mail confirmation
- statuts employe: mails annulation / attente retour materiel / demande avis
- reset mot de passe: mail avec lien de reinitialisation

## 9. Problemes frequents

- Aucun mail recu:
  - verifier Mailpit UI
  - verifier `MAILER_DSN` dans `.env.local`
- Erreur DB:
  - verifier port MariaDB `3307` + `DATABASE_URL`
- Analytics indisponibles:
  - verifier `MONGODB_URL` (`127.0.0.1:27017`), `MONGODB_DB` et conteneur MongoDB

## 10. Smoke test rapide avant livraison

1. Public: home, menus, detail, contact, pages legales
2. Auth: login, reset password
3. User: profil, commande, suivi, avis
4. Employe: statuts commande + moderation avis
5. Admin: creation employe + analytics
