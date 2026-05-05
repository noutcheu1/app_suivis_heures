-- Script de refactoring de la base de données Chaudoudoux
-- Fusion des tables dupliquées et normalisation

-- =============================================
-- ÉTAPE 1: Fusion des tables intervenants
-- =============================================

-- Créer la table unifiée intervenants dans bdchaudoudoux_horaire
DROP TABLE IF EXISTS intervenants_unifie;

CREATE TABLE intervenants_unifie (
    -- Clé primaire
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Informations personnelles (de candidats)
    num_salarie VARCHAR(25),
    num_ss VARCHAR(21),
    titre VARCHAR(3),
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    date_naissance DATE,
    lieu_naissance VARCHAR(20),
    pays_naissance VARCHAR(20),
    nationalite VARCHAR(25),
    num_titre_sejour VARCHAR(15),
    date_titre_sejour DATE,
    
    -- Contact
    adresse VARCHAR(50),
    code_postal CHAR(5),
    ville VARCHAR(50),
    secteur VARCHAR(50),
    quartier VARCHAR(50),
    tel_portable VARCHAR(14),
    tel_fixe VARCHAR(14),
    tel_urgence VARCHAR(14),
    email VARCHAR(60),
    
    -- Situation professionnelle
    statut_handicap TINYINT(1) DEFAULT 0,
    permis TINYINT(1),
    vehicule TINYINT(1),
    statut_pro VARCHAR(15),
    situation_familiale VARCHAR(15),
    
    -- Compétences
    diplomes VARCHAR(150),
    qualifications VARCHAR(100),
    exp_bb_moins_1an TINYINT(1),
    enfant_handicape TINYINT(1),
    
    -- Contrat et travail
    date_entree DATE,
    date_sortie DATE,
    archive TINYINT(1) DEFAULT 0,
    certification VARCHAR(240),
    taux_horaire DECIMAL(10,2),
    recherche_complement TINYINT(1),
    nb_heures_semaine VARCHAR(100),
    nb_heures_mois VARCHAR(100),
    proposer_psc1 TINYINT(1),
    justificatifs VARCHAR(50),
    
    -- Suivi administratif
    date_modification VARCHAR(100),
    suivi VARCHAR(250),
    arret_travail TINYINT(1),
    date_fin_arret DATE,
    archive_temporaire TINYINT(1) DEFAULT 0,
    date_debut_archive_temporaire DATE,
    date_fin_archive_temporaire DATE,
    repassage TINYINT(1),
    
    -- Mutuelle et couverture
    mutuelle VARCHAR(40) DEFAULT '0',
    cmu TINYINT(1) DEFAULT 0,
    
    -- Disponibilités et observations (seront normalisées plus tard)
    disponibilites TEXT,
    observations TEXT,
    
    -- Candidature
    candidature_retenue VARCHAR(150) DEFAULT 'En attente',
    date_entretien DATETIME DEFAULT CURRENT_TIMESTAMP,
    travail_voulu VARCHAR(50),
    nom_jeune_fille VARCHAR(50),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_nom_prenom (nom, prenom),
    INDEX idx_email (email),
    INDEX idx_num_ss (num_ss),
    INDEX idx_archive (archive),
    INDEX idx_date_entree (date_entree)
);

-- =============================================
-- ÉTAPE 2: Fusion des tables candidats
-- =============================================

-- La table candidats sera fusionnée dans intervenants_unifie
-- Les données seront migrées depuis les deux bases

-- =============================================
-- ÉTAPE 3: Standardisation des noms de tables
-- =============================================

-- Renommer la table avec caractères spéciaux
DROP TABLE IF EXISTS garde_enfants_menage_2018;
CREATE TABLE garde_enfants_menage_2018 AS SELECT * FROM bdchaudoudoux.`1- garde d enfants menage 2018`;

-- =============================================
-- ÉTAPE 4: Normalisation des disponibilités
-- =============================================

-- Créer table pour les disponibilités
CREATE TABLE disponibilites_intervenants (
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

-- =============================================
-- ÉTAPE 5: Normalisation des observations
-- =============================================

-- Créer table pour les observations
CREATE TABLE observations_intervenants (
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
-- ÉTAPE 6: Amélioration de la table famille
-- =============================================

-- Créer table famille_normalisee
CREATE TABLE famille_normalisee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_famille VARCHAR(10) UNIQUE NOT NULL,
    nom_famille VARCHAR(100),
    
    -- Adresse
    adresse VARCHAR(50),
    code_postal CHAR(5),
    ville VARCHAR(50),
    secteur VARCHAR(50),
    quartier VARCHAR(30),
    
    -- Contact
    tel_dom CHAR(14),
    email VARCHAR(60),
    
    -- Administration
    num_alloc VARCHAR(15),
    num_urssaf VARCHAR(20),
    date_entree DATETIME,
    date_sortie DATETIME,
    archive TINYINT(1) DEFAULT 0,
    
    -- Logement
    type_logement VARCHAR(30),
    superficie INT(4),
    nb_etage INT(3),
    nb_chambres INT(3),
    nb_sdb INT(3),
    nb_sanitaire INT(3),
    
    -- Transport
    arret_bus VARCHAR(50),
    num_bus VARCHAR(20),
    vehicule TINYINT(1),
    
    -- Prestations
    garde_partielle TINYINT(1),
    repassage TINYINT(1),
    prest_menage TINYINT(1),
    prest_garde_enfants TINYINT(1),
    
    -- Vacances
    nb_sem_vacances_menage TINYINT(4) DEFAULT 0,
    nb_sem_vacances_ge TINYINT(4) DEFAULT 0,
    
    -- Options et paiement
    options_famille VARCHAR(10),
    mode_paiement VARCHAR(50),
    mandataire TINYINT(1),
    
    -- Suivi
    observations_famille TEXT,
    remarques_famille TEXT,
    enfant_handicape TINYINT(1),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_nom_famille (nom_famille),
    INDEX idx_ville (ville),
    INDEX idx_archive (archive)
);

-- =============================================
-- ÉTAPE 7: Standardisation des tarifs
-- =============================================

-- Créer table tarifs_unifiee
CREATE TABLE tarifs_unifiee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_prestation VARCHAR(4) NOT NULL,
    libelle_prestation VARCHAR(50),
    tarif_horaire_base DECIMAL(5,2) NOT NULL,
    tarif_horaire_majoration DECIMAL(5,2),
    frais_gestion DECIMAL(5,2) NOT NULL,
    km_enfants DECIMAL(5,2) NOT NULL,
    abonnement DECIMAL(5,2),
    date_debut_validite VARCHAR(7) NOT NULL,
    date_fin_validite VARCHAR(7),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_type_prestation (type_prestation),
    INDEX idx_validite (date_debut_validite, date_fin_validite)
);

-- Message de confirmation
SELECT 'Refactoring de la base de données terminé avec succès' AS message;
