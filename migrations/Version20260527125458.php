<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527125458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_config (id INT AUTO_INCREMENT NOT NULL, nbrJourSaisie INT NOT NULL, nbrPalierTarifGE INT NOT NULL, nbrPalierTarifM INT NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tarifs_suivi (id INT AUTO_INCREMENT NOT NULL, alheureGE LONGTEXT NOT NULL, alheureM LONGTEXT DEFAULT NULL, fraisGestion LONGTEXT NOT NULL, parIntervention NUMERIC(5, 2) NOT NULL, maxParIntervention NUMERIC(5, 2) NOT NULL, KMenfants NUMERIC(5, 2) NOT NULL, abonnement NUMERIC(5, 2) NOT NULL, dateDebut VARCHAR(7) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tauxhoraire ADD tauxHoraire NUMERIC(5, 2) NOT NULL, ADD dateDebut VARCHAR(7) NOT NULL, DROP nomFam, DROP numInter, DROP datePresta, DROP heureDebutPresta, DROP heureFinPresta, DROP kmAvecEnfant, DROP ajouterLe, DROP modifierLe, DROP desactiver, CHANGE numFam numFam VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE users_suivi CHANGE cree_le cree_le DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users_suivi RENAME INDEX uq_username TO UNIQ_C9CB6EC6F85E0677');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_config');
        $this->addSql('DROP TABLE tarifs_suivi');
        $this->addSql('ALTER TABLE tauxhoraire ADD nomFam VARCHAR(50) NOT NULL, ADD numInter INT NOT NULL, ADD datePresta DATE NOT NULL, ADD heureDebutPresta TIME NOT NULL, ADD heureFinPresta TIME NOT NULL, ADD kmAvecEnfant INT DEFAULT NULL, ADD ajouterLe DATETIME NOT NULL, ADD modifierLe DATETIME DEFAULT NULL, ADD desactiver TINYINT DEFAULT 0 NOT NULL, DROP tauxHoraire, DROP dateDebut, CHANGE numFam numFam VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE users_suivi CHANGE cree_le cree_le DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE users_suivi RENAME INDEX uniq_c9cb6ec6f85e0677 TO uq_username');
    }
}
