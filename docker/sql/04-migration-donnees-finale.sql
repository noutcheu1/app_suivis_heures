-- Script de migration finale et définitive
-- Version finale : Migration simple et fonctionnelle

-- =============================================
-- MIGRATION 1: Intervenants (approche directe)
-- =============================================

-- Migration avec identifiants uniques générés
INSERT INTO intervenants_unifie (
    num_salarie, nom, prenom, date_entree, date_sortie, archive, certification, 
    taux_horaire, recherche_complement, nb_heures_semaine, nb_heures_mois,
    proposer_psc1, justificatifs, date_modification, suivi, 
    arret_travail, date_fin_arret, archive_temporaire, 
    date_debut_archive_temporaire, date_fin_archive_temporaire, repassage
) 
SELECT 
    CONCAT('INT_', idSalarie_Intervenants, '_', 
           ROW_NUMBER() OVER (PARTITION BY idSalarie_Intervenants ORDER BY idSalarie_Intervenants)),
    'Intervenant' AS nom, 
    CONCAT('Intervenant_', idSalarie_Intervenants) AS prenom,
    dateEntree_Intervenants, dateSortie_Intervenants, 
    archive_Intervenants, Certification_Intervenants, tauxH_Intervenants,
    rechCompl_Intervenants, nbHeureSem_Intervenants, nbHeureMois_Intervenants,
    ProposerPSC1_Intervenants, justificatifs_Intervenants, dateModif_Intervenants,
    suivi_Intervenants, arretTravail_Intervenants, dateFinArret_Intervenants,
    archiveTemporaire, dateDebutArchiveTemporaire, dateFinArchiveTemporaire,
    repassage_Intervenants
FROM bdchaudoudoux_horaire.intervenants;

-- =============================================
-- MIGRATION 2: Candidats
-- =============================================

INSERT INTO intervenants_unifie (
    num_salarie, num_ss, titre, nom, prenom, date_naissance,
    lieu_naissance, pays_naissance, nationalite, num_titre_sejour,
    date_titre_sejour, adresse, code_postal, ville, secteur, quartier,
    tel_portable, tel_fixe, tel_urgence, email, statut_handicap,
    permis, vehicule, statut_pro, situation_familiale, diplomes,
    qualifications, exp_bb_moins_1an, enfant_handicape, mutuelle,
    cmu, disponibilites, observations, candidature_retenue,
    date_entretien, travail_voulu, nom_jeune_fille
)
SELECT 
    CONCAT('CAND_', LPAD(numCandidat_Candidats, 6, '0')),
    NULLIF(numSS_Candidats, ''), titre_Candidats, nom_Candidats, prenom_Candidats,
    DATE(dateNaiss_Candidats), lieuNaiss_Candidats, paysNaiss_Candidats,
    nationalite_Candidats, numTitreSejour, dateTitreSejour,
    adresse_Candidats, cp_Candidats, ville_Candidats, secteur_Candidats,
    Quartier_Candidats, telPortable_Candidats, telFixe_Candidats,
    TelUrg_Candidats, email_Candidats, statutHandicap_Candidats,
    permis_Candidats, vehicule_Candidats, statutPro_Candidats,
    situationFamiliale_Candidats, diplomes_Candidats,
    qualifications_Candidats, expBBmoins1a_Candidats,
    enfantHand_Candidats, Mutuelle_Candidats, CMU_Candidats,
    disponibilites_Candidats, observations_Candidats,
    candidatureRetenue_Candidats, dateEntretien_Candidats,
    travailVoulu_Candidats, nomJF_Candidats
FROM bdchaudoudoux_horaire.candidats;

-- =============================================
-- MIGRATION 3: Familles
-- =============================================

INSERT INTO famille_normalisee (
    numero_famille, nom_famille, adresse, code_postal, ville, secteur,
    quartier, tel_dom, num_alloc, num_urssaf, date_entree, date_sortie,
    archive, type_logement, superficie, nb_etage, nb_chambres, nb_sdb,
    nb_sanitaire, arret_bus, num_bus, vehicule, garde_partielle,
    repassage, prest_menage, prest_garde_enfants, nb_sem_vacances_menage,
    nb_sem_vacances_ge, options_famille, mode_paiement, mandataire,
    observations_famille, remarques_famille, enfant_handicape
)
SELECT 
    numero_Famille, Famille_Famille, adresse_Famille, cp_Famille,
    ville_Famille, secteur_Famille, quartier_Famille, telDom_Famille,
    numAlloc_Famille, numURSSAF_Famille, dateEntree_Famille,
    dateSortie_Famille, archive_Famille, typeLogement_Famille,
    superficie_Famille, nbEtage_Famille, nbChambres_Famille,
    nbSDB_Famille, nbSanitaire_Famille, arretBus_Famille,
    numBus_Famille, vehicule_Famille, gardePart_Famille,
    repassage_Famille, prestM_Famille, prestGE_Famille,
    nbSemVacancesM, nbSemVacancesGE, option_Famille,
    modePaiement_Famille, mand_Famille, observations_Famille,
    Remarques_Famille, enfantHand_Famille
FROM bdchaudoudoux.famille;

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
    type_prestation, libelle_prestation, tarif_horaire_base
)
VALUES 
    ('GE', 'Garde d\'enfants', 15.50),
    ('ENFA', 'Enfants', 12.00)
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

SELECT '=== MIGRATION TERMINÉE ===' AS status, '' AS details
UNION ALL
SELECT 'Intervenants unifiés', CAST(COUNT(*) AS CHAR) FROM intervenants_unifie
UNION ALL
SELECT 'Familles normalisées', CAST(COUNT(*) AS CHAR) FROM famille_normalisee
UNION ALL
SELECT 'Tarifs unifiés', CAST(COUNT(*) AS CHAR) FROM tarifs_unifiee;

SELECT 'Base de données refactorisée avec succès!' AS message;
