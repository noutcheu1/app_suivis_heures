-- Migration sécurisée pour ajouter une colonne id à la table intervenants
-- Garde numSalarie_Intervenants comme clé primaire existante

-- Étape 1: Ajouter la colonne id (non primaire, juste auto-générée)
ALTER TABLE intervenants 
ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST;

-- Étape 2: Créer un index sur numSalarie_Intervenants pour garantir l'unicité
-- (si ce n'est pas déjà fait)
-- ALTER TABLE intervenants ADD UNIQUE INDEX idx_num_salarie (numSalarie_Intervenants);

-- Cette approche:
-- 1. Ajoute id comme colonne auto-générée
-- 2. Garde numSalarie_Intervenants comme clé primaire
-- 3. Assure l'unicité avec une contrainte UNIQUE
-- 4. Fonctionne avec les données existantes
