<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413113758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE allergene (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(100) NOT NULL,
              UNIQUE INDEX UNIQ_93232AE56C6E55B5 (nom),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE avis (
              id INT AUTO_INCREMENT NOT NULL,
              note INT NOT NULL,
              commentaire LONGTEXT NOT NULL,
              valide TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              commande_id INT NOT NULL,
              UNIQUE INDEX UNIQ_8F91ABF082EA2E54 (commande_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE contact_message (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(150) NOT NULL,
              email VARCHAR(190) NOT NULL,
              titre VARCHAR(200) NOT NULL,
              message LONGTEXT NOT NULL,
              traite TINYINT NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE horaire (
              id INT AUTO_INCREMENT NOT NULL,
              jour INT NOT NULL,
              heure_ouverture TIME DEFAULT NULL,
              heure_fermeture TIME DEFAULT NULL,
              ferme TINYINT NOT NULL,
              UNIQUE INDEX UNIQ_HORAIRE_JOUR (jour),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_plat (
              menu_id INT NOT NULL,
              plat_id INT NOT NULL,
              INDEX IDX_E8775249CCD7E912 (menu_id),
              INDEX IDX_E8775249D73DB560 (plat_id),
              PRIMARY KEY (menu_id, plat_id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_image (
              id INT AUTO_INCREMENT NOT NULL,
              alt_text VARCHAR(255) DEFAULT NULL,
              url VARCHAR(255) NOT NULL,
              is_principale TINYINT NOT NULL,
              ordre_affichage INT NOT NULL,
              menu_id INT NOT NULL,
              INDEX IDX_54912738CCD7E912 (menu_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_token (
              id INT AUTO_INCREMENT NOT NULL,
              token VARCHAR(255) NOT NULL,
              expires_at DATETIME NOT NULL,
              used_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              user_id INT NOT NULL,
              UNIQUE INDEX UNIQ_6B7BA4B65F37A13B (token),
              INDEX IDX_6B7BA4B6A76ED395 (user_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE plat (
              id INT AUTO_INCREMENT NOT NULL,
              nom VARCHAR(150) NOT NULL,
              description LONGTEXT DEFAULT NULL,
              type VARCHAR(50) NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE plat_allergene (
              plat_id INT NOT NULL,
              allergene_id INT NOT NULL,
              INDEX IDX_6FA44BBFD73DB560 (plat_id),
              INDEX IDX_6FA44BBF4646AB2 (allergene_id),
              PRIMARY KEY (plat_id, allergene_id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              avis
            ADD
              CONSTRAINT FK_8F91ABF082EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              menu_plat
            ADD
              CONSTRAINT FK_E8775249CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              menu_plat
            ADD
              CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              menu_image
            ADD
              CONSTRAINT FK_54912738CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              password_reset_token
            ADD
              CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              plat_allergene
            ADD
              CONSTRAINT FK_6FA44BBFD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              plat_allergene
            ADD
              CONSTRAINT FK_6FA44BBF4646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF082EA2E54');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249CCD7E912');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE menu_image DROP FOREIGN KEY FK_54912738CCD7E912');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY FK_6FA44BBFD73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY FK_6FA44BBF4646AB2');
        $this->addSql('DROP TABLE allergene');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE menu_plat');
        $this->addSql('DROP TABLE menu_image');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE plat_allergene');
    }
}
