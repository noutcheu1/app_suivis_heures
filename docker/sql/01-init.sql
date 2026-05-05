-- Script d'initialisation pour le projet Chaudoudoux
-- Crée les bases de données et configure les permissions

-- Création de la base de données secondaire si elle n'existe pas
CREATE DATABASE IF NOT EXISTS bdchaudoudoux_horaire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Assurer que la base principale existe (déjà créée par MYSQL_DATABASE mais on s'assure)
CREATE DATABASE IF NOT EXISTS bdchaudoudoux CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Donner les droits complets à l'utilisateur chaudoudoux sur les deux bases
GRANT ALL PRIVILEGES ON bdchaudoudoux.* TO 'chaudoudoux'@'%';
GRANT ALL PRIVILEGES ON bdchaudoudoux_horaire.* TO 'chaudoudoux'@'%';

-- Donner aussi les droits de création de vues et procédures stockées
GRANT CREATE VIEW ON bdchaudoudoux.* TO 'chaudoudoux'@'%';
GRANT CREATE VIEW ON bdchaudoudoux_horaire.* TO 'chaudoudoux'@'%';
GRANT CREATE ROUTINE ON bdchaudoudoux.* TO 'chaudoudoux'@'%';
GRANT CREATE ROUTINE ON bdchaudoudoux_horaire.* TO 'chaudoudoux'@'%';

-- Appliquer les changements immédiatement
FLUSH PRIVILEGES;

-- Afficher un message de confirmation pour le débogage
SELECT 'Initialisation des bases de données Chaudoudoux terminée avec succès' AS message;
