<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413112759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commande (
              id INT AUTO_INCREMENT NOT NULL,
              date_commande DATETIME NOT NULL,
              date_prestation DATE NOT NULL,
              heure_prestation TIME NOT NULL,
              adresse_prestation VARCHAR(255) NOT NULL,
              nom_prenom_client VARCHAR(200) NOT NULL,
              gsm_client VARCHAR(30) NOT NULL,
              prix_menu_total_centimes INT NOT NULL,
              frais_livraison_centimes INT NOT NULL,
              reduction_appliquee_centimes INT NOT NULL,
              prix_total_centimes INT NOT NULL,
              nb_personnes INT NOT NULL,
              pret_materiel TINYINT NOT NULL,
              user_id INT NOT NULL,
              menu_id INT NOT NULL,
              commune_livraison_id INT NOT NULL,
              INDEX IDX_6EEAA67DA76ED395 (user_id),
              INDEX IDX_6EEAA67DCCD7E912 (menu_id),
              INDEX IDX_6EEAA67DB27D4DC (commune_livraison_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commande_statut (
              id INT AUTO_INCREMENT NOT NULL,
              statut VARCHAR(50) NOT NULL,
              date_heure DATETIME NOT NULL,
              commentaire LONGTEXT DEFAULT NULL,
              commande_id INT NOT NULL,
              user_id INT DEFAULT NULL,
              INDEX IDX_E7300B6A82EA2E54 (commande_id),
              INDEX IDX_E7300B6AA76ED395 (user_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE commune_livraison (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(150) NOT NULL,
              code_postal VARCHAR(10) NOT NULL,
              distance_km NUMERIC(6, 2) NOT NULL,
              actif TINYINT NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu (
              id INT AUTO_INCREMENT NOT NULL,
              titre VARCHAR(150) NOT NULL,
              description LONGTEXT NOT NULL,
              theme VARCHAR(100) NOT NULL,
              prix_min_centimes INT NOT NULL,
              personnes_min INT NOT NULL,
              conditions_particulieres LONGTEXT DEFAULT NULL,
              regime VARCHAR(100) NOT NULL,
              stock INT NOT NULL,
              actif TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(180) NOT NULL,
              roles JSON NOT NULL,
              password_hash VARCHAR(255) NOT NULL,
              nom VARCHAR(100) NOT NULL,
              prenom VARCHAR(100) NOT NULL,
              telephone VARCHAR(30) DEFAULT NULL,
              adresse VARCHAR(255) DEFAULT NULL,
              actif TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
              id BIGINT AUTO_INCREMENT NOT NULL,
              body LONGTEXT NOT NULL,
              headers LONGTEXT NOT NULL,
              queue_name VARCHAR(190) NOT NULL,
              created_at DATETIME NOT NULL,
              available_at DATETIME NOT NULL,
              delivered_at DATETIME DEFAULT NULL,
              INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (
                queue_name, available_at, delivered_at,
                id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              commande
            ADD
              CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              commande
            ADD
              CONSTRAINT FK_6EEAA67DCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              commande
            ADD
              CONSTRAINT FK_6EEAA67DB27D4DC FOREIGN KEY (commune_livraison_id) REFERENCES commune_livraison (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              commande_statut
            ADD
              CONSTRAINT FK_E7300B6A82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              commande_statut
            ADD
              CONSTRAINT FK_E7300B6AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DCCD7E912');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB27D4DC');
        $this->addSql('ALTER TABLE commande_statut DROP FOREIGN KEY FK_E7300B6A82EA2E54');
        $this->addSql('ALTER TABLE commande_statut DROP FOREIGN KEY FK_E7300B6AA76ED395');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_statut');
        $this->addSql('DROP TABLE commune_livraison');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
