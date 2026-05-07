-- =============================================
-- Base de Données Unifiée Chaudoudoux
-- Fusion de bdchaudoudoux_horaire + bdchaudoudoux
-- =============================================

-- Création de la base unifiée
CREATE DATABASE IF NOT EXISTS `bdchaudoudoux_unifie` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bdchaudoudoux_unifie`;

-- =============================================
-- Tables Principales Normalisées
-- =============================================

-- Table intervenants_unifie (fusion de intervenants + champs normalisés)
CREATE TABLE `intervenants_unifie` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `num_salarie` VARCHAR(25) UNIQUE NOT NULL,
    `num_ss` VARCHAR(21) NULL,
    `titre` VARCHAR(3) NULL,
    `nom` VARCHAR(50) NOT NULL,
    `prenom` VARCHAR(50) NOT NULL,
    `date_naissance` DATE NULL,
    `lieu_naissance` VARCHAR(20) NULL,
    `pays_naissance` VARCHAR(20) NULL,
    `nationalite` VARCHAR(25) NULL,
    `num_titre_sejour` VARCHAR(15) NULL,
    `date_titre_sejour` DATE NULL,
    `adresse` VARCHAR(50) NULL,
    `code_postal` VARCHAR(5) NULL,
    `ville` VARCHAR(50) NULL,
    `secteur` VARCHAR(50) NULL,
    `quartier` VARCHAR(50) NULL,
    `tel_portable` VARCHAR(14) NULL,
    `tel_fixe` VARCHAR(14) NULL,
    `tel_urgence` VARCHAR(14) NULL,
    `email` VARCHAR(60) NULL,
    `statut_handicap` BOOLEAN DEFAULT FALSE,
    `permis` BOOLEAN NULL,
    `vehicule` BOOLEAN NULL,
    `statut_pro` VARCHAR(15) NULL,
    `situation_familiale` VARCHAR(15) NULL,
    `diplomes` VARCHAR(150) NULL,
    `qualifications` VARCHAR(100) NULL,
    `exp_bb_moins_1an` BOOLEAN NULL,
    `enfant_handicape` BOOLEAN NULL,
    `date_entree` DATE NULL,
    `date_sortie` DATE NULL,
    `archive` BOOLEAN DEFAULT FALSE,
    `certification` VARCHAR(240) NULL,
    `taux_horaire` DECIMAL(10,2) NULL,
    `recherche_complement` BOOLEAN NULL,
    `nb_heures_semaine` VARCHAR(100) NULL,
    `nb_heures_mois` VARCHAR(100) NULL,
    `proposer_psc1` BOOLEAN NULL,
    `justificatifs` VARCHAR(50) NULL,
    `date_modification` VARCHAR(100) NULL,
    `suivi` VARCHAR(250) NULL,
    `arret_travail` BOOLEAN NULL,
    `date_fin_arret` DATE NULL,
    `archive_temporaire` BOOLEAN DEFAULT FALSE,
    `date_debut_archive_temporaire` DATE NULL,
    `date_fin_archive_temporaire` DATE NULL,
    `repassage` BOOLEAN NULL,
    `mutuelle` VARCHAR(40) DEFAULT '0',
    `cmu` BOOLEAN DEFAULT FALSE,
    `disponibilites` TEXT NULL,
    `observations` TEXT NULL,
    `candidature_retenue` VARCHAR(150) DEFAULT 'En attente',
    `date_entretien` DATETIME NULL,
    `travail_voulu` VARCHAR(50) NULL,
    `nom_jeune_fille` VARCHAR(50) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_nom_prenom` (`nom`, `prenom`),
    INDEX `idx_archive` (`archive`),
    INDEX `idx_date_entree` (`date_entree`)
) ENGINE=InnoDB;

-- Table famille_normalisee (structure normalisée)
CREATE TABLE `famille_normalisee` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `numero_famille` VARCHAR(10) UNIQUE NOT NULL,
    `nom_famille` VARCHAR(100) NULL,
    `adresse` VARCHAR(50) NULL,
    `code_postal` VARCHAR(5) NULL,
    `ville` VARCHAR(50) NULL,
    `secteur` VARCHAR(50) NULL,
    `quartier` VARCHAR(50) NULL,
    `tel_dom` VARCHAR(14) NULL,
    `email` VARCHAR(60) NULL,
    `num_alloc` VARCHAR(20) NULL,
    `num_urssaf` VARCHAR(20) NULL,
    `date_entree` DATE NULL,
    `date_sortie` DATE NULL,
    `archive` BOOLEAN DEFAULT FALSE,
    `type_logement` VARCHAR(50) NULL,
    `superficie` INT NULL,
    `nb_etage` INT NULL,
    `nb_chambres` INT NULL,
    `nb_sdb` INT NULL,
    `nb_sanitaire` INT NULL,
    `arret_bus` VARCHAR(50) NULL,
    `num_bus` VARCHAR(20) NULL,
    `vehicule` BOOLEAN NULL,
    `garde_partielle` BOOLEAN NULL,
    `repassage` BOOLEAN NULL,
    `prest_menage` BOOLEAN NULL,
    `prest_garde_enfants` BOOLEAN NULL,
    `nb_sem_vacances_menage` INT NULL,
    `nb_sem_vacances_ge` INT NULL,
    `options_famille` VARCHAR(100) NULL,
    `mode_paiement` VARCHAR(50) NULL,
    `mandataire` VARCHAR(50) NULL,
    `observations_famille` TEXT NULL,
    `remarques_famille` TEXT NULL,
    `enfant_handicape` BOOLEAN NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_numero_famille` (`numero_famille`),
    INDEX `idx_archive` (`archive`)
) ENGINE=InnoDB;

-- Table tarifs_unifiee
CREATE TABLE `tarifs_unifiee` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type_prestation` VARCHAR(50) NOT NULL,
    `libelle_prestation` VARCHAR(100) NOT NULL,
    `tarif_horaire_base` DECIMAL(10,2) NOT NULL,
    `tarif_horaire_majoration` DECIMAL(10,2) NULL,
    `frais_gestion` DECIMAL(10,2) NULL,
    `km_enfants` BOOLEAN DEFAULT FALSE,
    `abonnement` DECIMAL(10,2) NULL,
    `date_debut_validite` DATE NULL,
    `date_fin_validite` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_type_prestation` (`type_prestation`),
    INDEX `idx_validite` (`date_debut_validite`, `date_fin_validite`)
) ENGINE=InnoDB;

-- =============================================
-- Tables de Données (existantes)
-- =============================================

-- Table horaireinter (identique à la base actuelle)
CREATE TABLE `horaireinter` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `numFam` VARCHAR(10) NULL,
    `nomFam` VARCHAR(50) NULL,
    `numInter` INT DEFAULT 0,
    `date_presta` DATE NOT NULL,
    `heure_debut_presta` TIME NULL,
    `heure_fin_presta` TIME NULL,
    `typePresta` VARCHAR(4) NULL,
    `nbHeurePresta` VARCHAR(10) NULL,
    `tauxH` DECIMAL(10,2) NULL,
    `montantPresta` DECIMAL(10,2) NULL,
    `reglement` VARCHAR(15) NULL,
    `numCheque` VARCHAR(15) NULL,
    `datePaiement` DATE NULL,
    `dateSaisie` DATETIME NULL,
    `desactiver` BOOLEAN DEFAULT 0,
    `commentaire` TEXT NULL,
    
    INDEX `idx_date_presta` (`date_presta`),
    INDEX `idx_numFam` (`numFam`),
    INDEX `idx_numInter` (`numInter`)
) ENGINE=InnoDB;

-- Table relevemensuelfam
CREATE TABLE `relevemensuelfam` (
    `numFam` VARCHAR(10) NOT NULL,
    `moisannee` VARCHAR(7) NOT NULL,
    `typePresta` VARCHAR(4) NOT NULL,
    `typeRèglement` VARCHAR(15) NULL,
    `numChèque` VARCHAR(15) NULL,
    `nbHeureFacturees` VARCHAR(10) NULL,
    `totalFacture` DECIMAL(10,2) NULL,
    `reglementEffectue` VARCHAR(15) NULL,
    `dateReglement` DATE NULL,
    `commentaire` TEXT NULL,
    PRIMARY KEY (`numFam`, `moisannee`, `typePresta`)
) ENGINE=InnoDB;

-- Table relevemensuelinter
CREATE TABLE `relevemensuelinter` (
    `numInter` VARCHAR(10) NOT NULL,
    `moisannee` VARCHAR(7) NOT NULL,
    `typePresta` VARCHAR(4) NOT NULL,
    `typeRèglement` VARCHAR(15) NULL,
    `numChèque` VARCHAR(15) NULL,
    `nbHeureFacturees` VARCHAR(10) NULL,
    `totalFacture` DECIMAL(10,2) NULL,
    `reglementEffectue` VARCHAR(15) NULL,
    `dateReglement` DATE NULL,
    `commentaire` TEXT NULL,
    PRIMARY KEY (`numInter`, `moisannee`, `typePresta`)
) ENGINE=InnoDB;

-- Table users2
CREATE TABLE `users2` (
    `identifiant` VARCHAR(21) PRIMARY KEY,
    `mdp` VARCHAR(255) NOT NULL,
    `userType` VARCHAR(20) NULL,
    `intervenant_id` INT NULL,
    `famille_id` INT NULL,
    `dateCreation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `derniereConnexion` DATETIME NULL,
    
    INDEX `idx_userType` (`userType`),
    INDEX `idx_intervenant_id` (`intervenant_id`),
    INDEX `idx_famille_id` (`famille_id`)
) ENGINE=InnoDB;

-- Table tarifs2 (conservée pour compatibilité)
CREATE TABLE `tarifs2` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `typePresta` VARCHAR(4) NOT NULL,
    `libelle` VARCHAR(100) NOT NULL,
    `tarifHoraire` DECIMAL(10,2) NOT NULL,
    `dateDebutValidite` DATE NULL,
    `dateFinValidite` DATE NULL,
    
    INDEX `idx_typePresta` (`typePresta`)
) ENGINE=InnoDB;

-- =============================================
-- Tables de Normalisation (Formes Normales)
-- =============================================

-- Table disponibilites_intervenants
CREATE TABLE `disponibilites_intervenants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `intervenant_id` INT NOT NULL,
    `jour_semaine` VARCHAR(10) NOT NULL,
    `periode_journee` VARCHAR(20) NOT NULL,
    `disponible` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`intervenant_id`) REFERENCES `intervenants_unifie`(`id`) ON DELETE CASCADE,
    INDEX `idx_intervenant_dispo` (`intervenant_id`, `jour_semaine`, `periode_journee`)
) ENGINE=InnoDB;

-- =============================================
-- Contraintes et Relations
-- =============================================

-- Ajouter les contraintes foreign key après la migration des données
-- ALTER TABLE `users2` 
-- ADD CONSTRAINT `fk_users_intervenant` FOREIGN KEY (`intervenant_id`) REFERENCES `intervenants_unifie`(`id`);
-- ALTER TABLE `users2` 
-- ADD CONSTRAINT `fk_users_famille` FOREIGN KEY (`famille_id`) REFERENCES `famille_normalisee`(`id`);
