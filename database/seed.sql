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
  (3, 4, 3, 3, '2026-04-13 10:20:00', '2026-12-24', '20:00:00', '11 avenue Jean Jaures, Pessac', 'Camille Durand', '0633333333', 35000, 866, 0, 35866, 8, 0),
  (4, 5, 1, 4, '2026-04-18 09:30:00', '2026-04-30', '13:00:00', '14 cours Gambetta, Talence', 'Alex Martin', '0644444444', 40500, 742, 4050, 37192, 9, 0),
  (5, 4, 2, 1, '2026-05-02 11:15:00', '2026-05-10', '12:00:00', '20 rue Sainte-Catherine, Bordeaux', 'Camille Durand', '0633333333', 26400, 0, 0, 26400, 12, 0),
  (6, 5, 3, 2, '2026-05-20 16:40:00', '2026-06-05', '19:30:00', '5 avenue de la Gare, Merignac', 'Alex Martin', '0644444444', 52500, 1002, 5250, 48252, 12, 1),
  (7, 4, 1, 1, '2026-06-01 10:05:00', '2026-06-08', '12:15:00', '20 rue Sainte-Catherine, Bordeaux', 'Camille Durand', '0633333333', 27000, 0, 0, 27000, 6, 0),
  (8, 5, 2, 3, '2026-06-10 14:25:00', '2026-06-20', '19:45:00', '28 avenue Pasteur, Pessac', 'Alex Martin', '0644444444', 33000, 866, 3300, 30566, 15, 0),
  (9, 4, 1, 2, '2026-07-04 09:50:00', '2026-07-14', '12:00:00', '44 avenue de la Somme, Merignac', 'Camille Durand', '0633333333', 22500, 1002, 0, 23502, 5, 0),
  (10, 5, 3, 1, '2026-08-01 12:10:00', '2026-08-15', '20:00:00', '5 avenue de la Gare, Merignac', 'Alex Martin', '0644444444', 35000, 0, 0, 35000, 8, 0),
  (11, 4, 2, 4, '2026-09-03 15:20:00', '2026-09-12', '18:45:00', '9 rue Frederic Mistral, Talence', 'Camille Durand', '0633333333', 28600, 742, 2860, 26482, 13, 1),
  (12, 5, 1, 3, '2026-10-05 10:55:00', '2026-10-18', '12:20:00', '62 route de Toulouse, Pessac', 'Alex Martin', '0644444444', 31500, 866, 3150, 29216, 7, 0);

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

  (11, 3, 2, 'accepte', '2026-04-13 10:45:00', 'Prestation de Noel'),
  (12, 4, 3, 'accepte', '2026-04-18 09:50:00', NULL),
  (13, 4, 3, 'en_preparation', '2026-04-29 09:00:00', NULL),
  (14, 4, 3, 'en_cours_livraison', '2026-04-30 12:15:00', NULL),
  (15, 4, 3, 'livre', '2026-04-30 13:10:00', NULL),
  (16, 4, 3, 'terminee', '2026-04-30 15:00:00', NULL),
  (17, 5, 2, 'accepte', '2026-05-02 11:40:00', NULL),
  (18, 5, 2, 'en_preparation', '2026-05-09 08:10:00', NULL),
  (19, 5, 2, 'livre', '2026-05-10 12:20:00', NULL),
  (20, 5, 2, 'terminee', '2026-05-10 13:10:00', NULL),
  (21, 6, 2, 'accepte', '2026-05-20 17:00:00', NULL),
  (22, 6, 2, 'en_preparation', '2026-06-04 10:00:00', NULL),
  (23, 6, 2, 'en_cours_livraison', '2026-06-05 18:50:00', NULL),
  (24, 6, 2, 'livre', '2026-06-05 19:35:00', 'Materiel prete au client'),
  (25, 6, 2, 'attente_retour_materiel', '2026-06-05 19:40:00', NULL),
  (26, 7, 3, 'accepte', '2026-06-01 10:20:00', NULL),
  (27, 7, 3, 'terminee', '2026-06-08 14:30:00', NULL),
  (28, 8, 2, 'accepte', '2026-06-10 14:35:00', NULL),
  (29, 8, 2, 'annulee', '2026-06-11 09:00:00', '[gsm] Date evenement decalee par le client'),
  (30, 9, 3, 'accepte', '2026-07-04 10:00:00', NULL),
  (31, 9, 3, 'terminee', '2026-07-14 14:00:00', NULL),
  (32, 10, 2, 'accepte', '2026-08-01 12:25:00', NULL),
  (33, 11, 2, 'accepte', '2026-09-03 15:30:00', NULL),
  (34, 11, 2, 'en_preparation', '2026-09-11 09:30:00', NULL),
  (35, 12, 3, 'accepte', '2026-10-05 11:10:00', NULL);

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
