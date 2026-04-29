# Manuel Utilisateur - Vite & Gourmand

Version: 2026-04-27  
Public cible: utilisateurs finaux, employes, administrateurs

## 1. Objectif

Ce manuel presente les parcours d'utilisation de l'application Vite & Gourmand:
- consultation des menus,
- commande en ligne,
- gestion des commandes et avis,
- gestion metier cote employe et administrateur.

## 2. Acces a l'application

- URL de production: `https://viteetgourmand-ecf-1777281827-46fc6847b4b1.herokuapp.com`
- URL locale (developpement): `http://127.0.0.1:8000`
- Boite mail locale (developpement): `http://127.0.0.1:8025`

## 3. Comptes de demonstration

Mot de passe commun (seed): `Test1234!`

- Administrateur: `admin@vitegourmand.local`
- Administrateur: `jose@vitegourmand.local`
- Employe: `employee@vitegourmand.local`
- Utilisateur: `user1@example.com`
- Utilisateur: `user2@example.com`

## 4. Parcours visiteur (non connecte)

1. Ouvrir la page d'accueil (`/`).
2. Consulter la liste des menus (`/menus`).
3. Utiliser les filtres (prix, theme, regime, nombre de personnes).
4. Ouvrir le detail d'un menu (`/menus/{id}`).
5. Acceder au formulaire de contact (`/contact`).
6. Consulter les pages legales (`/mentions-legales`, `/cgv`).

## 5. Parcours utilisateur connecte

1. Se connecter (`/login`) ou creer un compte (`/register`).
2. Consulter son profil (`/profile`) et le modifier (`/profile/edit`).
3. Commander un menu via la page detail ou directement (`/orders/new/{id}`).
4. Verifier le recapitulatif du prix (menu, livraison, remises).
5. Suivre ses commandes (`/profile/orders`, `/profile/orders/{id}`).
6. Modifier/annuler une commande tant qu'elle n'est pas "acceptee".
7. Deposer un avis quand la commande est "terminee".

## 6. Parcours employe

1. Ouvrir l'espace commande (`/employee/orders`).
2. Filtrer les commandes par statut et client.
3. Mettre a jour les statuts:
   - acceptee
   - en preparation
   - en cours de livraison
   - livree
   - en attente du retour de materiel
   - terminee
4. Moderer les avis (`/employee/reviews`).
5. Gerer les horaires (`/employee/hours`).
6. Gerer menus, plats, allergenes:
   - `/employee/menus`
   - `/employee/plats`
   - `/employee/allergenes`

## 7. Parcours administrateur

1. Ouvrir le dashboard (`/admin`).
2. Creer un compte employe (`/admin/employees/new`).
3. Activer/desactiver un compte employe.
4. Consulter les statistiques:
   - nombre de commandes par menu,
   - chiffre d'affaires par menu et periode.

## 8. Emails automatiques attendus

- confirmation d'inscription utilisateur (bienvenue),
- creation de compte employe,
- confirmation de commande,
- annulation de commande,
- passage en attente de retour materiel,
- demande d'avis apres commande terminee,
- reinitialisation mot de passe.

## 9. Regles de gestion visibles pour l'utilisateur

- Respect du nombre minimum de personnes du menu.
- Reduction de 10% au dela du seuil prevu (min + 5 personnes).
- Frais de livraison appliques hors Bordeaux.
- Conditions du menu affichees dans la vue detail.
- Stock disponible pris en compte a la commande.

## 10. Depannage rapide

Probleme: pages inaccessibles  
Actions:
- verifier l'URL,
- verifier l'etat de l'application hebergee,
- verifier la connectivite internet.

Probleme: commande impossible  
Actions:
- verifier connexion utilisateur,
- verifier stock du menu,
- verifier contraintes (nombre de personnes, date/heure).

Probleme: email non recu  
Actions:
- verifier l'adresse email saisie,
- verifier le dossier spam,
- en local: verifier Mailpit.

## 11. Checklist de recette utilisateur

- Home, menus, detail, contact, mentions legales, CGV.
- Inscription, connexion, reset password.
- Creation et suivi d'une commande utilisateur.
- Changement de statut commande cote employe.
- Moderation d'un avis.
- Creation d'un employe cote admin.
- Affichage des analytics admin.
