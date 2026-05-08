-- Script de migration finale pour compléter le processus
-- Focus sur les tarifs et finalisation

-- =============================================
-- MIGRATION 4: Tarifs (valeurs par défaut)
-- =============================================

INSERT INTO tarifs_unifiee (
    type_prestation, libelle_prestation, tarif_horaire_base, frais_gestion, 
    km_enfants, abonnement, date_debut_validite
)
SELECT 
    'MENA', 'Ménage', 18.00, 2.50, 0.10, 0.00, '2024-01'
WHERE NOT EXISTS (SELECT 1 FROM tarifs_unifiee WHERE type_prestation = 'MENA');

INSERT INTO tarifs_unifiee (
    type_prestation, libelle_prestation, tarif_horaire_base, frais_gestion, 
    km_enfants, abonnement, date_debut_validite
)
VALUES 
    ('GE', 'Garde d\'enfants', 15.50, 2.50, 0.10, 0.00, '2024-01'),
    ('ENFA', 'Enfants', 12.00, 2.50, 0.10, 0.00, '2024-01')
ON DUPLICATE KEY UPDATE tarif_horaire_base = VALUES(tarif_horaire_base);

-- =============================================
-- MIGRATION 5: Tables normalisées
-- =============================================

CREATE TABLE IF NOT EXISTS disponibilites_intervenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intervenant_id INT NOT NULL,
    jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
    periode_journee ENUM('Matin', 'Apres-midi', 'Soir', 'Nuit') NOT NULL,
    disponible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (intervenant_id) REFERENCES intervenants_unifie(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dispo (intervenant_id, jour_semaine, periode_journee),
    INDEX idx_intervenant (intervenant_id)
);

CREATE TABLE IF NOT EXISTS observations_intervenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intervenant_id INT NOT NULL,
    type_observation ENUM('Generale', 'Comportement', 'Competence', 'Sante', 'Administratif') NOT NULL,
    observation TEXT NOT NULL,
    date_observation DATE NOT NULL,
    auteur_observation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (intervenant_id) REFERENCES intervenants_unifie(id) ON DELETE CASCADE,
    INDEX idx_intervenant (intervenant_id),
    INDEX idx_date_observation (date_observation)
);

-- =============================================
-- VALIDATION FINALE
-- =============================================

SELECT '=== MIGRATION TERMINÉE AVEC SUCCÈS ===' AS status, '' AS details
UNION ALL
SELECT 'Intervenants unifiés', CAST(COUNT(*) AS CHAR) FROM intervenants_unifie
UNION ALL
SELECT 'Familles normalisées', CAST(COUNT(*) AS CHAR) FROM famille_normalisee
UNION ALL
SELECT 'Tarifs unifiés', CAST(COUNT(*) AS CHAR) FROM tarifs_unifiee
UNION ALL
SELECT '=== STRUCTURE NORMALISÉE ===', ''
UNION ALL
SELECT 'Tables de disponibilités', CAST(COUNT(*) AS CHAR) FROM disponibilites_intervenants
UNION ALL
SELECT 'Tables d\'observations', CAST(COUNT(*) AS CHAR) FROM observations_intervenants;

-- Vérification de la qualité des données
SELECT '=== QUALITÉ DES DONNÉES ===', ''
UNION ALL
SELECT 'Intervenants avec nom', CAST(COUNT(*) AS CHAR) FROM intervenants_unifie WHERE nom IS NOT NULL AND nom != ''
UNION ALL
SELECT 'Intervenants avec email', CAST(COUNT(*) AS CHAR) FROM intervenants_unifie WHERE email IS NOT NULL AND email != ''
UNION ALL
SELECT 'Familles avec numéro', CAST(COUNT(*) AS CHAR) FROM famille_normalisee WHERE numero_famille IS NOT NULL;

SELECT 'Refactoring de la base de données terminé avec succès!' AS message;
