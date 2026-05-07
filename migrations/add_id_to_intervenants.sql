-- Migration pour ajouter une colonne id auto-générée à la table intervenants
-- Conserve numSalarie_Intervenants comme clé primaire existante

ALTER TABLE intervenants 
ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- Note: Cette commande va:
-- 1. Ajouter une colonne id auto-générée
-- 2. La placer comme première colonne (FIRST)
-- 3. En faire la clé primaire (PRIMARY KEY)
-- 4. numSalarie_Intervenants restera unique mais ne sera plus primaire

-- Alternative si la table a déjà des données et on veut garder numSalarie_Intervenants comme primaire:
-- ALTER TABLE intervenants 
-- ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST;
