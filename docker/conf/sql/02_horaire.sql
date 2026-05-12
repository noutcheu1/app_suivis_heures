-- ============================================================
-- 02_horaire.sql — Tables de l'app + migration des données
-- ============================================================
-- S'exécute APRÈS :
--   01-init.sql         (crée les DBs + permissions)
--   02-import-dumps.sh  (importe les dumps production)
--
-- Ce fichier crée uniquement les NOUVELLES tables de l'app
-- (absentes des dumps) et migre les données depuis les tables
-- legacy correspondantes.
-- ============================================================

USE bdchaudoudoux_horaire;

-- ─────────────────────────────────────────────────────────────
-- TABLE : users_suivi
-- Remplace users2 (ancien système sans bcrypt ni rôles Symfony)
-- ─────────────────────────────────────────────────────────────
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte admin par défaut
-- username : 9.99.99.99.999.999.99  /  password : admin
INSERT IGNORE INTO `users_suivi` (`username`, `role`, `password`) VALUES (
    '9.99.99.99.999.999.99',
    'admin',
    '$2y$13$n1Nvk59rJbNfdEqLJ.ytJeJUWlOCaaJ3q1VAY8kPpChFONGsoc6FG'
);

-- Migration des comptes existants depuis users2
-- Règle de détection du rôle :
--   identifiant commençant par un chiffre suivi d'un point → intervenant (numSS)
--   identifiant commençant par PM, M, PGE               → famille
--   admin identifié par son username exact               → déjà inséré ci-dessus
INSERT IGNORE INTO `users_suivi` (`username`, `role`, `password`)
SELECT
    u.`identifiant`,
    CASE
        WHEN u.`identifiant` REGEXP '^[0-9]+\\.'   THEN 'intervenant'
        WHEN u.`identifiant` REGEXP '^(PM|PGE|M)[0-9]' THEN 'famille'
        ELSE 'intervenant'
    END AS `role`,
    u.`mdp`
FROM `users2` u
WHERE u.`identifiant` IS NOT NULL
  AND u.`identifiant` != ''
  AND u.`identifiant` != '9.99.99.99.999.999.99';

-- ─────────────────────────────────────────────────────────────
-- TABLE : tarifs_suivi
-- Même structure que tarifs2 (legacy PdoApp).
-- tarifs2  → lu par PdoApp (couche legacy)
-- tarifs_suivi → lu par Doctrine (entité Tarif)
-- ─────────────────────────────────────────────────────────────
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
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_date_debut` (`dateDebut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration : copie tous les tarifs de tarifs2 vers tarifs_suivi
INSERT IGNORE INTO `tarifs_suivi`
    (`id`, `alheureGE`, `alheureM`, `fraisGestion`,
     `parIntervention`, `maxParIntervention`, `KMenfants`, `abonnement`, `dateDebut`)
SELECT
    `id`, `alheureGE`, `alheureM`, `fraisGestion`,
    `parIntervention`, `maxParIntervention`, `KMenfants`, `abonnement`, `dateDebut`
FROM `tarifs2`;

-- ─────────────────────────────────────────────────────────────
-- Confirmation
-- ─────────────────────────────────────────────────────────────
SELECT
    'users_suivi'   AS `table`,
    COUNT(*)        AS `lignes`
FROM `users_suivi`

UNION ALL

SELECT
    'tarifs_suivi'  AS `table`,
    COUNT(*)        AS `lignes`
FROM `tarifs_suivi`;
