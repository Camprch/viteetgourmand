-- Vite & Gourmand - Seed SQL v1 (MariaDB)
-- Execute after database/schema.sql

SET NAMES utf8mb4;

-- Users
-- All test accounts use password: Test1234!
INSERT INTO user (id, nom, prenom, email, password_hash, telephone, adresse, roles, actif, created_at) VALUES
  (1, 'Admin', 'Julie', 'admin@vitegourmand.local', '$2y$10$6BJYcJEOXvm737abIVDpD.LuP08kH3HOE0P3vM8cz31u2LHlEIyAi', '0611111111', '10 rue des Chefs, Bordeaux', '["ROLE_ADMIN","ROLE_EMPLOYEE"]', 1, '2026-04-13 09:00:00'),
  (2, 'Admin', 'Jose', 'jose@vitegourmand.local', '$2y$10$6BJYcJEOXvm737abIVDpD.LuP08kH3HOE0P3vM8cz31u2LHlEIyAi', '0622222222', '12 rue des Chefs, Bordeaux', '["ROLE_ADMIN","ROLE_EMPLOYEE"]', 1, '2026-04-13 09:05:00'),
  (3, 'Equipe', 'Emma', 'employee@vitegourmand.local', '$2y$10$6BJYcJEOXvm737abIVDpD.LuP08kH3HOE0P3vM8cz31u2LHlEIyAi', '0655555555', '7 rue du Commerce, Bordeaux', '["ROLE_EMPLOYEE"]', 1, '2026-04-13 09:08:00'),
  (4, 'Durand', 'Camille', 'user1@example.com', '$2y$10$6BJYcJEOXvm737abIVDpD.LuP08kH3HOE0P3vM8cz31u2LHlEIyAi', '0633333333', '20 rue Sainte-Catherine, Bordeaux', '["ROLE_USER"]', 1, '2026-04-13 09:10:00'),
  (5, 'Martin', 'Alex', 'user2@example.com', '$2y$10$6BJYcJEOXvm737abIVDpD.LuP08kH3HOE0P3vM8cz31u2LHlEIyAi', '0644444444', '5 avenue de la Gare, Merignac', '["ROLE_USER"]', 1, '2026-04-13 09:15:00');

-- Menus
INSERT INTO menu (id, titre, description, theme, prix_min_centimes, personnes_min, conditions_particulieres, regime, stock, actif, created_at) VALUES
  (1, 'Menu Classique Bordeaux', 'Entree, plat, dessert pour evenements familiaux.', 'Classique', 18000, 4, 'Commander 48h a l''avance.', 'Classique', 20, 1, '2026-04-13 09:20:00'),
  (2, 'Menu Vegan Printemps', 'Cuisine vegetale de saison.', 'Evenement', 22000, 6, 'Conservation au frais recommandee.', 'Vegan', 12, 1, '2026-04-13 09:21:00'),
  (3, 'Menu Noel Prestige', 'Menu festif avec options premium.', 'Noel', 35000, 8, 'Commander 10 jours avant la prestation.', 'Classique', 8, 1, '2026-04-13 09:22:00');

INSERT INTO menu_image (id, menu_id, alt_text, url, is_principale, ordre_affichage) VALUES
  (1, 1, 'Photo menu classique', '/images/menus/classique-1.png', 1, 1),
  (2, 2, 'Photo menu vegan', '/images/menus/vegan-1.png', 1, 1),
  (3, 3, 'Photo menu noel', '/images/menus/noel-1.png', 1, 1);

-- Dishes and allergens
INSERT INTO plat (id, nom, description, type) VALUES
  (1, 'Veloute de potimarron', 'Cremeux et herbes fraiches.', 'entree'),
  (2, 'Parmentier de legumes', 'Plat vegan gourmand.', 'plat'),
  (3, 'Mousse chocolat noir', 'Dessert intense.', 'dessert'),
  (4, 'Foie gras maison', 'Entree festive.', 'entree'),
  (5, 'Magret sauce miel', 'Plat de fete.', 'plat'),
  (6, 'Buche artisanale', 'Dessert de saison.', 'dessert'),
  (7, 'Tartare de betterave', 'Entree vegetale aux herbes fraiches.', 'entree'),
  (8, 'Curry de pois chiches', 'Plat vegan epice avec legumes de saison.', 'plat'),
  (9, 'Salade d agrumes et menthe', 'Dessert leger sans produits animaux.', 'dessert');

INSERT INTO allergene (id, nom) VALUES
  (1, 'Gluten'),
  (2, 'Lait'),
  (3, 'Oeufs'),
  (4, 'Fruits a coque');

INSERT INTO menu_plat (menu_id, plat_id) VALUES
  (1, 1), (1, 2), (1, 3),
  (2, 7), (2, 8), (2, 9),
  (3, 4), (3, 5), (3, 6);

INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
  (1, 2),
  (3, 2), (3, 3),
  (4, 2),
  (5, 1),
  (6, 2), (6, 3), (6, 4);

-- Delivery areas
INSERT INTO commune_livraison (id, nom, code_postal, distance_km, actif) VALUES
  (1, 'Bordeaux', '33000', 0.00, 1),
  (2, 'Merignac', '33700', 8.50, 1),
  (3, 'Pessac', '33600', 6.20, 1),
  (4, 'Talence', '33400', 4.10, 1);

-- Orders
INSERT INTO commande (
  id, user_id, menu_id, commune_livraison_id, date_commande, date_prestation, heure_prestation,
  adresse_prestation, nom_prenom_client, gsm_client,
  prix_menu_total_centimes, frais_livraison_centimes, reduction_appliquee_centimes, prix_total_centimes,
  nb_personnes, pret_materiel
) VALUES
  (1, 4, 1, 1, '2026-04-13 10:00:00', '2026-04-20', '12:30:00', '20 rue Sainte-Catherine, Bordeaux', 'Camille Durand', '0633333333', 18000, 0, 0, 18000, 4, 0),
  (2, 5, 2, 2, '2026-04-13 10:10:00', '2026-04-22', '19:00:00', '5 avenue de la Gare, Merignac', 'Alex Martin', '0644444444', 24200, 1002, 2420, 22782, 11, 1),
  (3, 4, 3, 3, '2026-04-13 10:20:00', '2026-12-24', '20:00:00', '11 avenue Jean Jaures, Pessac', 'Camille Durand', '0633333333', 35000, 866, 0, 35866, 8, 0);

-- Status timeline
INSERT INTO commande_statut (id, commande_id, user_id, statut, date_heure, commentaire) VALUES
  (1, 1, 2, 'accepte', '2026-04-13 10:30:00', 'Commande validee'),
  (2, 1, 2, 'en_preparation', '2026-04-19 08:00:00', NULL),
  (3, 1, 2, 'en_cours_livraison', '2026-04-20 11:30:00', NULL),
  (4, 1, 2, 'livre', '2026-04-20 12:40:00', NULL),
  (5, 1, 2, 'terminee', '2026-04-20 14:00:00', 'Sans pret materiel'),

  (6, 2, 2, 'accepte', '2026-04-13 10:40:00', NULL),
  (7, 2, 2, 'en_preparation', '2026-04-21 07:30:00', NULL),
  (8, 2, 2, 'en_cours_livraison', '2026-04-22 18:20:00', NULL),
  (9, 2, 2, 'livre', '2026-04-22 19:10:00', 'Materiel prete au client'),
  (10, 2, 2, 'attente_retour_materiel', '2026-04-22 19:15:00', 'Retour sous 10 jours ou penalite CGV'),

  (11, 3, 2, 'accepte', '2026-04-13 10:45:00', 'Prestation de Noel');

-- Reviews
INSERT INTO avis (id, commande_id, note, commentaire, valide, created_at) VALUES
  (1, 1, 5, 'Service tres professionnel et repas excellent.', 1, '2026-04-21 09:00:00');

-- Contact messages
INSERT INTO contact_message (id, nom, email, titre, message, traite, created_at) VALUES
  (1, 'Lefevre', 'contact.client@example.com', 'Demande devis mariage', 'Bonjour, pouvez-vous proposer un menu vegetarien pour 40 personnes ?', 0, '2026-04-13 11:00:00');

-- Opening hours (1=lundi ... 7=dimanche)
INSERT INTO horaire (id, jour, heure_ouverture, heure_fermeture, ferme) VALUES
  (1, 1, '09:00:00', '18:00:00', 0),
  (2, 2, '09:00:00', '18:00:00', 0),
  (3, 3, '09:00:00', '18:00:00', 0),
  (4, 4, '09:00:00', '18:00:00', 0),
  (5, 5, '09:00:00', '18:00:00', 0),
  (6, 6, '10:00:00', '16:00:00', 0),
  (7, 7, NULL, NULL, 1);
