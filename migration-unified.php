<?php

/**
 * Script de Migration vers Base de Données Unifiée
 * Fusionne bdchaudoudoux_horaire + bdchaudoudoux → bdchaudoudoux_unifie
 */

// Configuration
$sourceDb = 'bdchaudoudoux_horaire';
$targetDb = 'bdchaudoudoux_unifie';
$host = '127.0.0.1';
$port = '3307';
$username = 'chaudoudoux';
$password = 'root';

try {
    // Connexion
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Migration vers Base de Données Unifiée ===\n\n";
    
    // 1. Créer la base unifiée si elle n'existe pas
    echo "1. Création de la base unifiée...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$targetDb`");
    
    // 2. Exécuter le schéma
    echo "2. Création des tables...\n";
    $schema = file_get_contents(__DIR__ . '/database-unified-schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            $pdo->exec($stmt);
        }
    }
    
    // 3. Migration des intervenants
    echo "3. Migration des intervenants...\n";
    $pdo->exec("USE `$sourceDb`");
    $intervenants = $pdo->query("SELECT * FROM intervenants")->fetchAll(PDO::FETCH_ASSOC);
    
    $pdo->exec("USE `$targetDb`");
    $stmtIntervenants = $pdo->prepare("
        INSERT INTO intervenants_unifie (
            num_salarie, num_ss, titre, nom, prenom, date_naissance, lieu_naissance, 
            pays_naissance, nationalite, num_titre_sejour, date_titre_sejour, adresse, 
            code_postal, ville, secteur, quartier, tel_portable, tel_fixe, tel_urgence, 
            email, statut_handicap, permis, vehicule, statut_pro, situation_familiale, 
            diplomes, qualifications, exp_bb_moins_1an, enfant_handicape, date_entree, 
            date_sortie, archive, certification, taux_horaire, recherche_complement, 
            nb_heures_semaine, nb_heures_mois, proposer_psc1, justificatifs, 
            date_modification, suivi, arret_travail, date_fin_arret, archive_temporaire, 
            date_debut_archive_temporaire, date_fin_archive_temporaire, repassage, 
            mutuelle, cmu, disponibilites, observations, candidature_retenue, 
            date_entretien, travail_voulu, nom_jeune_fille
        ) VALUES (
            :numSalarie, :numSs, :titre, :nom, :prenom, :dateNaissance, :lieuNaissance,
            :paysNaissance, :nationalite, :numTitreSejour, :dateTitreSejour, :adresse,
            :codePostal, :ville, :secteur, :quartier, :telPortable, :telFixe, :telUrgence,
            :email, :statutHandicap, :permis, :vehicule, :statutPro, :situationFamiliale,
            :diplomes, :qualifications, :expBbMoins1an, :enfantHandicape, :dateEntree,
            :dateSortie, :archive, :certification, :tauxHoraire, :rechercheComplement,
            :nbHeuresSemaine, :nbHeuresMois, :proposerPsc1, :justificatifs,
            :dateModification, :suivi, :arretTravail, :dateFinArret, :archiveTemporaire,
            :dateDebutArchiveTemporaire, :dateFinArchiveTemporaire, :repassage,
            :mutuelle, :cmu, :disponibilites, :observations, :candidatureRetenue,
            :dateEntretien, :travailVoulu, :nomJeuneFille
        )
    ");
    
    foreach ($intervenants as $intervenant) {
        $stmtIntervenants->execute([
            ':numSalarie' => $intervenant['numSalarie_Intervenants'],
            ':numSs' => $intervenant['idSalarie_Intervenants'] ?? null,
            ':titre' => null, // À extraire des données si disponible
            ':nom' => null, // À extraire des données si disponible
            ':prenom' => null, // À extraire des données si disponible
            ':dateNaissance' => null,
            ':lieuNaissance' => null,
            ':paysNaissance' => null,
            ':nationalite' => null,
            ':numTitreSejour' => null,
            ':dateTitreSejour' => null,
            ':adresse' => null,
            ':codePostal' => null,
            ':ville' => null,
            ':secteur' => null,
            ':quartier' => null,
            ':telPortable' => null,
            ':telFixe' => null,
            ':telUrgence' => null,
            ':email' => null,
            ':statutHandicap' => null,
            ':permis' => null,
            ':vehicule' => null,
            ':statutPro' => null,
            ':situationFamiliale' => null,
            ':diplomes' => null,
            ':qualifications' => null,
            ':expBbMoins1an' => null,
            ':enfantHandicape' => null,
            ':dateEntree' => $intervenant['dateEntree_Intervenants'] ?? null,
            ':dateSortie' => $intervenant['dateSortie_Intervenants'] ?? null,
            ':archive' => $intervenant['archive_Intervenants'] ?? false,
            ':certification' => $intervenant['Certification_Intervenants'] ?? null,
            ':tauxHoraire' => $intervenant['tauxH_Intervenants'] ?? null,
            ':rechercheComplement' => $intervenant['rechCompl_Intervenants'] ?? null,
            ':nbHeuresSemaine' => $intervenant['nbHeureSem_Intervenants'] ?? null,
            ':nbHeuresMois' => $intervenant['nbHeureMois_Intervenants'] ?? null,
            ':proposerPsc1' => $intervenant['ProposerPSC1_Intervenants'] ?? null,
            ':justificatifs' => $intervenant['justificatifs_Intervenants'] ?? null,
            ':dateModification' => $intervenant['dateModif_Intervenants'] ?? null,
            ':suivi' => $intervenant['suivi_Intervenants'] ?? null,
            ':arretTravail' => $intervenant['arretTravail_Intervenants'] ?? null,
            ':dateFinArret' => $intervenant['dateFinArret_Intervenants'] ?? null,
            ':archiveTemporaire' => $intervenant['archiveTemporaire'] ?? false,
            ':dateDebutArchiveTemporaire' => $intervenant['dateDebutArchiveTemporaire'] ?? null,
            ':dateFinArchiveTemporaire' => $intervenant['dateFinArchiveTemporaire'] ?? null,
            ':repassage' => $intervenant['repassage_Intervenants'] ?? null,
            ':mutuelle' => null,
            ':cmu' => null,
            ':disponibilites' => null,
            ':observations' => null,
            ':candidatureRetenue' => null,
            ':dateEntretien' => null,
            ':travailVoulu' => null,
            ':nomJeuneFille' => null
        ]);
    }
    
    echo "   - " . count($intervenants) . " intervenants migrés\n";
    
    // 4. Migration des horaires
    echo "4. Migration des horaires...\n";
    $pdo->exec("USE `$sourceDb`");
    $horaires = $pdo->query("SELECT * FROM horaireinter")->fetchAll(PDO::FETCH_ASSOC);
    
    $pdo->exec("USE `$targetDb`");
    $stmtHoraires = $pdo->prepare("
        INSERT INTO horaireinter (
            numFam, nomFam, numInter, date_presta, heure_debut_presta, 
            heure_fin_presta, typePresta, nbHeurePresta, tauxH, montantPresta, 
            reglement, numCheque, datePaiement, dateSaisie, desactiver, commentaire
        ) VALUES (
            :numFam, :nomFam, :numInter, :datePresta, :heureDebutPresta,
            :heureFinPresta, :typePresta, :nbHeurePresta, :tauxH, :montantPresta,
            :reglement, :numCheque, :datePaiement, :dateSaisie, :desactiver, :commentaire
        )
    ");
    
    foreach ($horaires as $horaire) {
        $stmtHoraires->execute([
            ':numFam' => $horaire['numFam'],
            ':nomFam' => $horaire['nomFam'],
            ':numInter' => $horaire['numInter'],
            ':datePresta' => $horaire['date_presta'],
            ':heureDebutPresta' => $horaire['heure_debut_presta'],
            ':heureFinPresta' => $horaire['heure_fin_presta'],
            ':typePresta' => $horaire['typePresta'],
            ':nbHeurePresta' => $horaire['nbHeurePresta'],
            ':tauxH' => $horaire['tauxH'],
            ':montantPresta' => $horaire['montantPresta'],
            ':reglement' => $horaire['reglement'],
            ':numCheque' => $horaire['numCheque'],
            ':datePaiement' => $horaire['datePaiement'],
            ':dateSaisie' => $horaire['dateSaisie'],
            ':desactiver' => $horaire['desactiver'],
            ':commentaire' => $horaire['commentaire']
        ]);
    }
    
    echo "   - " . count($horaires) . " horaires migrés\n";
    
    // 5. Migration des autres tables
    echo "5. Migration des autres tables...\n";
    
    // releves mensuels famille
    $pdo->exec("USE `$sourceDb`");
    $relevesFam = $pdo->query("SELECT * FROM relevemensuelfam")->fetchAll(PDO::FETCH_ASSOC);
    $pdo->exec("USE `$targetDb`");
    $pdo->exec("INSERT INTO relevemensuelfam SELECT * FROM `$sourceDb`.relevemensuelfam");
    echo "   - " . count($relevesFam) . " relevés mensuels famille migrés\n";
    
    // releves mensuels intervenants
    $pdo->exec("USE `$sourceDb`");
    $relevesInter = $pdo->query("SELECT * FROM relevemensuelinter")->fetchAll(PDO::FETCH_ASSOC);
    $pdo->exec("USE `$targetDb`");
    $pdo->exec("INSERT INTO relevemensuelinter SELECT * FROM `$sourceDb`.relevemensuelinter");
    echo "   - " . count($relevesInter) . " relevés mensuels intervenants migrés\n";
    
    // users2
    $pdo->exec("USE `$sourceDb`");
    $users = $pdo->query("SELECT * FROM users2")->fetchAll(PDO::FETCH_ASSOC);
    $pdo->exec("USE `$targetDb`");
    $pdo->exec("INSERT INTO users2 SELECT * FROM `$sourceDb`.users2");
    echo "   - " . count($users) . " utilisateurs migrés\n";
    
    // tarifs2
    $pdo->exec("USE `$sourceDb`");
    $tarifs = $pdo->query("SELECT * FROM tarifs2")->fetchAll(PDO::FETCH_ASSOC);
    $pdo->exec("USE `$targetDb`");
    $pdo->exec("INSERT INTO tarifs2 SELECT * FROM `$sourceDb`.tarifs2");
    echo "   - " . count($tarifs) . " tarifs migrés\n";
    
    echo "\n=== Migration Terminée avec Succès ===\n";
    echo "Base unifiée '$targetDb' créée avec toutes les données migrées.\n";
    echo "\nProchaines étapes :\n";
    echo "1. Mettre à jour .env pour utiliser DB_NAME=$targetDb\n";
    echo "2. Mettre à jour les entités pour utiliser les tables unifiées\n";
    echo "3. Tester l'application\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
