-- Script d'importation des dumps SQL pour le projet Chaudoudoux
-- Ce script sera exécuté après la création des bases et des permissions

-- Importer les données de la base principale bdchaudoudoux
-- Note: Le fichier sera copié et importé automatiquement par le volume Docker

-- Importer les données de la base secondaire bdchaudoudoux_horaire  
-- Note: Le fichier sera copié et importé automatiquement par le volume Docker

-- Message de confirmation
SELECT 'Importation des dumps SQL terminée' AS message;
