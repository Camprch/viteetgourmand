-- Vite & Gourmand - Schema SQL v1 (MariaDB)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS commande_statut;
DROP TABLE IF EXISTS commande;
DROP TABLE IF EXISTS contact_message;
DROP TABLE IF EXISTS horaire;
DROP TABLE IF EXISTS password_reset_token;
DROP TABLE IF EXISTS menu_image;
DROP TABLE IF EXISTS menu_plat;
DROP TABLE IF EXISTS plat_allergene;
DROP TABLE IF EXISTS menu;
DROP TABLE IF EXISTS plat;
DROP TABLE IF EXISTS allergene;
DROP TABLE IF EXISTS commune_livraison;
DROP TABLE IF EXISTS user;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE user (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  telephone VARCHAR(30) NULL,
  adresse VARCHAR(255) NULL,
  roles TEXT NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_token (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_prt_token (token),
  KEY idx_prt_user (user_id),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  theme VARCHAR(100) NOT NULL,
  prix_min_centimes INT UNSIGNED NOT NULL,
  personnes_min INT UNSIGNED NOT NULL,
  conditions_particulieres TEXT NULL,
  regime VARCHAR(100) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  KEY idx_menu_theme (theme),
  KEY idx_menu_regime (regime),
  KEY idx_menu_prix (prix_min_centimes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_image (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id BIGINT UNSIGNED NOT NULL,
  alt_text VARCHAR(255) NULL,
  url VARCHAR(255) NOT NULL,
  is_principale TINYINT(1) NOT NULL DEFAULT 0,
  ordre_affichage INT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_menu_image_menu (menu_id),
  CONSTRAINT fk_menu_image_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  description TEXT NULL,
  type VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE allergene (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_allergene_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_plat (
  menu_id BIGINT UNSIGNED NOT NULL,
  plat_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (menu_id, plat_id),
  KEY idx_menu_plat_plat (plat_id),
  CONSTRAINT fk_menu_plat_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
  CONSTRAINT fk_menu_plat_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat_allergene (
  plat_id BIGINT UNSIGNED NOT NULL,
  allergene_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (plat_id, allergene_id),
  KEY idx_plat_allergene_allergene (allergene_id),
  CONSTRAINT fk_plat_allergene_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE CASCADE,
  CONSTRAINT fk_plat_allergene_allergene FOREIGN KEY (allergene_id) REFERENCES allergene(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commune_livraison (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  code_postal VARCHAR(10) NOT NULL,
  distance_km DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_commune_nom_cp (nom, code_postal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commande (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  menu_id BIGINT UNSIGNED NOT NULL,
  commune_livraison_id BIGINT UNSIGNED NOT NULL,
  date_commande DATETIME NOT NULL,
  date_prestation DATE NOT NULL,
  heure_prestation TIME NOT NULL,
  adresse_prestation VARCHAR(255) NOT NULL,
  nom_prenom_client VARCHAR(200) NOT NULL,
  gsm_client VARCHAR(30) NOT NULL,
  prix_menu_total_centimes INT UNSIGNED NOT NULL,
  frais_livraison_centimes INT UNSIGNED NOT NULL DEFAULT 0,
  reduction_appliquee_centimes INT UNSIGNED NOT NULL DEFAULT 0,
  prix_total_centimes INT UNSIGNED NOT NULL,
  nb_personnes INT UNSIGNED NOT NULL,
  pret_materiel TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_commande_user (user_id),
  KEY idx_commande_menu (menu_id),
  KEY idx_commande_commune (commune_livraison_id),
  KEY idx_commande_date (date_commande),
  CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_commande_menu FOREIGN KEY (menu_id) REFERENCES menu(id),
  CONSTRAINT fk_commande_commune FOREIGN KEY (commune_livraison_id) REFERENCES commune_livraison(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commande_statut (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  statut VARCHAR(50) NOT NULL,
  date_heure DATETIME NOT NULL,
  commentaire TEXT NULL,
  KEY idx_cmd_statut_commande (commande_id),
  KEY idx_cmd_statut_user (user_id),
  KEY idx_cmd_statut_statut (statut),
  CONSTRAINT fk_cmd_statut_commande FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
  CONSTRAINT fk_cmd_statut_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id BIGINT UNSIGNED NOT NULL,
  note TINYINT UNSIGNED NOT NULL,
  commentaire TEXT NOT NULL,
  valide TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_avis_commande (commande_id),
  KEY idx_avis_valide (valide),
  CONSTRAINT fk_avis_commande FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_message (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  titre VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  traite TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_contact_traite (traite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE horaire (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jour TINYINT UNSIGNED NOT NULL,
  heure_ouverture TIME NULL,
  heure_fermeture TIME NULL,
  ferme TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_horaire_jour (jour),
  CONSTRAINT chk_horaire_jour CHECK (jour BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
