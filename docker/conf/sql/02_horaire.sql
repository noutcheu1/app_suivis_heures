-- ============================================================
-- Création de la base secondaire pour l'app suivi heures
-- Ce fichier est exécuté automatiquement par MariaDB au
-- démarrage via docker-entrypoint-initdb.d/
-- À chaque docker compose up, la base repart proprement.
-- ============================================================

CREATE DATABASE IF NOT EXISTS bdchaudoudoux_horaire
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Donne tous les droits à l'utilisateur de l'app sur cette base
GRANT ALL PRIVILEGES ON bdchaudoudoux_horaire.* TO 'chaudoudoux'@'%';
FLUSH PRIVILEGES;

USE bdchaudoudoux_horaire;

-- ── Table des comptes utilisateurs de l'app suivi heures ──
-- username contient selon le rôle :
--   admin       → identifiant libre
--   intervenant → numéro SS (correspond à numSS_Candidats dans bdchaudoudoux)
--   famille     → code PM_Famille ou PGE_Famille dans bdchaudoudoux
CREATE TABLE IF NOT EXISTS `users_suivi` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `username`     VARCHAR(255) NOT NULL,
    `role`         VARCHAR(20)  NOT NULL,
    `password`     VARCHAR(255) NOT NULL,
    `token_reinit` VARCHAR(64)  DEFAULT NULL,
    `token_expire` DATETIME     DEFAULT NULL,
    `cree_le`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Table des heures saisies par les intervenants ─────────
CREATE TABLE IF NOT EXISTS `horaireinter` (
    `id`               INT(15)      NOT NULL AUTO_INCREMENT,
    `numFam`           VARCHAR(10)  DEFAULT NULL,
    `nomFam`           VARCHAR(50)  NOT NULL,
    `numInter`         INT(5)       NOT NULL,
    `datePresta`       DATE         NOT NULL,
    `heureDebutPresta` TIME         NOT NULL,
    `heureFinPresta`   TIME         NOT NULL,
    `typePresta`       VARCHAR(4)   NOT NULL,
    `kmAvecEnfant`     DECIMAL(5,1) DEFAULT NULL,
    `ajouterLe`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modifierLe`       DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `desactiver`       TINYINT(1)   NOT NULL DEFAULT 0,
    `validerFam`       TINYINT(1)   NOT NULL DEFAULT 0,
    `validerLe`        DATETIME     DEFAULT NULL,
    `remarque`         TEXT         DEFAULT NULL,
    `remarqueLe`       DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_numFam`    (`numFam`),
    KEY `idx_numInter`  (`numInter`),
    KEY `idx_datePresta`(`datePresta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Relevé mensuel intervenant (signature + heures hors structure) ──
CREATE TABLE IF NOT EXISTS `relevemensuelinter` (
    `moisannee`            VARCHAR(7)  NOT NULL,
    `numInter`             INT(5)      NOT NULL,
    `typePresta`           VARCHAR(4)  NOT NULL,
    `heureDehors`          TIME        DEFAULT NULL,
    `heureDehorsAjouterLe` DATETIME   DEFAULT NULL,
    `signer`               TINYINT(1) NOT NULL DEFAULT 0,
    `signerLe`             DATETIME   DEFAULT NULL,
    PRIMARY KEY (`moisannee`, `numInter`, `typePresta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Récapitulatif mensuel famille (règlement + avis + signature) ──
CREATE TABLE IF NOT EXISTS `relevemensuelfam` (
    `numFam`            VARCHAR(10)  NOT NULL,
    `moisannee`         VARCHAR(7)   NOT NULL,
    `typePresta`        VARCHAR(4)   NOT NULL,
    `typeReglement`     VARCHAR(15)  DEFAULT NULL,
    `numCheque`         VARCHAR(15)  DEFAULT NULL,
    `nbrCESU`           INT(11)      DEFAULT NULL,
    `montantPrincipal`  DECIMAL(5,2) DEFAULT NULL,
    `complementCESU`    VARCHAR(15)  DEFAULT NULL,
    `montantComplement` DECIMAL(5,2) DEFAULT NULL,
    `libelerSupl`       TEXT         DEFAULT NULL,
    `montantSupl`       DECIMAL(5,2) DEFAULT NULL,
    `avisPonctualite`   INT(11)      DEFAULT NULL,
    `avisReguRela`      INT(11)      DEFAULT NULL,
    `avisRespectHo`     INT(11)      DEFAULT NULL,
    `avisQualiteTr`     INT(11)      DEFAULT NULL,
    `signerLe`          DATETIME     DEFAULT NULL,
    PRIMARY KEY (`numFam`, `moisannee`, `typePresta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grilles tarifaires horodatées ─────────────────────────
CREATE TABLE IF NOT EXISTS `tarifs_suivi` (
    `id`                 INT(15)      NOT NULL AUTO_INCREMENT,
    `alheureGE`          TEXT         NOT NULL,
    `alheureM`           TEXT         DEFAULT NULL,
    `fraisGestion`       TEXT         NOT NULL,
    `parIntervention`    DECIMAL(5,2) NOT NULL DEFAULT 1.50,
    `maxParIntervention` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
    `KMenfants`          DECIMAL(5,2) NOT NULL DEFAULT 0.35,
    `abonnement`         DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    `dateDebut`          VARCHAR(7)   NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Données initiales ─────────────────────────────────────

-- Compte admin par défaut
-- username : 9.99.99.99.999.999.99
-- password : admin (hashé en bcrypt)
INSERT IGNORE INTO `users_suivi` (`username`, `role`, `password`) VALUES (
    '9.99.99.99.999.999.99',
    'admin',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- Tarif initial (juillet 2025)
INSERT IGNORE INTO `tarifs_suivi`
    (`alheureGE`, `alheureM`, `fraisGestion`, `parIntervention`,
     `maxParIntervention`, `KMenfants`, `abonnement`, `dateDebut`)
VALUES (
    '[["$h >= 16","V"],["$h < 16 && $h >= 13",27.00],["$h < 13 && $h >= 10",30.00],["$h < 10 && $h >= 7",32.00],["$h < 7",35.00]]',
    '[["$h >= 0","V"],["$h < 0",0.00]]',
    '[["$age < 6","$h < 19",25.00],["$age >= 6","$h < 8",15.00]]',
    1.50, 15.00, 0.35, 2.00,
    '2025-07'
);