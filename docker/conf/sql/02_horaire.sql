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

