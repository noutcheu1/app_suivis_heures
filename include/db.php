<?php

class PdoApp
{
    private $monPdo;
    /**
     * Crée l'instance de PDO qui sera sollicitée
     * par toutes les méthodes de la classe
     */
    public function __construct($serveur, $bdd, $user, $mdp)
    {
        // crée la chaîne de connexion mentionnant le type de sgbdr, l'hôte et la base
        $chaineConnexion = 'mysql:host=' . $serveur . ';dbname=' . $bdd;
        // demande que le dialogue se fasee en utilisant l'encodage utf-8
        // et le mode de gestion des erreurs soit les exceptions
        $params = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        // crée une instance de PDO (connexion avec le serveur MySql) 
        $this->monPdo = new PDO($chaineConnexion, $user, $mdp, $params);
    }
    /**
     * Ferme la connexion avec le serveur MySQL
     */
    public function _destruct()
    {
        $this->monPdo = null;

    }
    public function getMonPdo()
    {
        return $this->monPdo;
    }

    /**
     * Retourne les informations de tous les intervenants
     * @return PDO::FETCH_ASSOC le nom et le prénom sous la forme d'un tableau associatif
     */
    public function getTousLesIntervenants()
    {
        $requete = $this->monPdo->prepare(
            'SELECT intervenants.candidats_numcandidat_candidats AS id,
            candidats.titre_Candidats AS genre,
            candidats.nom_Candidats AS nom, 
            candidats.prenom_Candidats AS prenom,
            intervenants.numSalarie_Intervenants AS numSalarie_Intervenants
            FROM intervenants
            JOIN candidats ON intervenants.candidats_numcandidat_candidats = candidats.numcandidat_candidats 
            WHERE `archive_Intervenants` = 0 AND `numSalarie_Intervenants` != 99999'
        );
        $requete->execute();
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTousLesFamilles()
    {
        $requete = $this->monPdo->prepare(
            "SELECT 
            f.`numero_Famille`,
            f.`PM_Famille` AS `PM_Famille`,
            f.`PGE_Famille` AS `PGE_Famille`,
            p.`nom_Parents`
        FROM `famille` f
        LEFT JOIN `parents` p 
            ON p.`numero_Famille` = f.`numero_Famille`
        WHERE f.`archive_Famille` = 0 
          AND f.`numero_Famille` NOT IN ('9998','9999','M000','M0001','M0002','M99995') 
          AND !(f.`PM_Famille` = '' AND f.`PGE_Famille` = '')
        GROUP BY f.`numero_Famille`
        ORDER BY p.`nom_Parents` ASC;
        "
        );
        $requete->execute();
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTouteExceptionTarif($date, $type) {
    $req = "SELECT 
            rf.`numFam`,
            rf.`moisannee`,
            rf.`libelerSupl`,
            rf.`montantSupl`,
            f.PGE_Famille,
            f.PM_Famille,
            p.nom_Parents
        FROM `relevemensuelfam` rf
        LEFT JOIN famille f ON rf.numFam = f.numero_Famille
        LEFT JOIN parents p ON rf.numFam = p.numero_Famille
        WHERE 
            rf.`libelerSupl` IS NOT NULL 
            AND rf.`montantSupl` IS NOT NULL 
            AND rf.`moisannee` = ?
            AND rf.`typePresta` = ?
        GROUP BY p.nom_Parents 
        ORDER BY p.nom_Parents ASC";
    $cmd = $this->monPdo->prepare($req);
    
    // On lie les paramètres
    $cmd->bindValue(1, $date->format('Y-m'));
    $cmd->bindValue(2, $type);

    // Exécution
    $cmd->execute();
    return $cmd->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Retourne les informations d'un intervenant
     * @param $id
     * @return l'id, le nom et le prénom sous la forme d'un tableau associatif
     */
    public function getInfosIntervenant($id)
    {
        $requetePrepare = $this->monPdo->prepare(
            'SELECT intervenant.candidats_numcandidat_candidats AS id,
            candidats.titre_Candidats AS genre,
            candidats.nom_Candidats AS nom, 
            candidats.prenom_Candidats AS prenom,
            candidats.email_Candidats AS email,
            candidats.ville_Candidats AS "ville de résidence"
            FROM intervenants AS intervenant
            JOIN candidats ON intervenant.candidats_numcandidat_candidats = candidats.numcandidat_candidats
            WHERE intervenant.candidats_numcandidat_candidats = :id;'
        );
        $requetePrepare->bindParam(':id', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetch();
    }
    public function getInfosFamille($id)
    {
        $requetePrepare = $this->monPdo->prepare(
            "SELECT f.`numero_famille` AS `numero M`, 
             f.`PM_Famille` AS `PM`,
             f.`PGE_Famille` AS `PGE`,
            GROUP_CONCAT(DISTINCT p.email_Parents SEPARATOR ', ') AS 'emails des Parents' ,
             p.nom_Parents AS 'Nom de la famille', 
             GROUP_CONCAT(DISTINCT p.prenom_Parents SEPARATOR ', ') AS 'prenoms des Parents', 
             GROUP_CONCAT(DISTINCT e.prenom_Enfants SEPARATOR ', ') AS 'prenom des Enfants',
            f.ville_Famille AS 'Ville de résidence'
            FROM `famille` f 
            LEFT JOIN `parents` p ON p.`numero_Famille` = f.`numero_Famille`
            LEFT JOIN `enfants` e ON e.numero_Famille = f.`numero_Famille`
            WHERE f.`numero_Famille` = :id AND p.email_Parents LIKE '%@%'
            GROUP BY f.numero_Famille;"
        );
        $requetePrepare->bindParam(':id', $id, PDO::PARAM_STR);
        $requetePrepare->execute();
        return $requetePrepare->fetch();
    }

    /**
     * Retourne les administareurs
     * @return le nom et le prénom sous la forme d'un tableau associatif
     */
    // public function getAdmin($id, $mdp)
    // {
    //     $req = "select id, nom, prenom from users where id = ? and mdp = ?";
    //     $cmd = $this->monPdo->prepare($req);
    //     $cmd->bindValue(1, $id);
    //     $cmd->bindValue(2, $mdp);
    //     $cmd->execute();
    //     $ligne = $cmd->fetch();
    //     return $ligne;
    // }

    /**
     * Retourne l'intervenant d'un numSS si pas deja inscrit
     * @return le numSS, nom et le prénom sous la forme d'un tableau
     */
    public function getIntervenantNumSS($numSS)
    {
        $numSS2 = str_replace(".", "", $numSS);
        $req = "SELECT c.numSS_Candidats, c.nom_Candidats, c.prenom_Candidats, i.numSalarie_Intervenants, c.adresse_Candidats, c.telPortable_Candidats, c.ville_Candidats, c.cp_Candidats, c.email_Candidats
        FROM candidats c
        LEFT JOIN intervenants i 
            ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
        WHERE (c.numSS_Candidats = ? OR c.numSS_Candidats = ?)
          AND i.archive_Intervenants != 1;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numSS);
        $cmd->bindValue(2, $numSS2);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }

    /**
     * Retourne l'intervenant d'un numSS si pas deja inscrit
     * @return le numSS, nom et le prénom sous la forme d'un tableau
     */
    public function getIntervenantNumInter($numInter)
    {
        $req = "SELECT c.numSS_Candidats, c.nom_Candidats, c.prenom_Candidats, i.numSalarie_Intervenants, c.adresse_Candidats, c.telPortable_Candidats, c.ville_Candidats, c.cp_Candidats
        FROM candidats c
        LEFT JOIN intervenants i 
            ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
        WHERE i.numSalarie_Intervenants = ?
          AND i.archive_Intervenants != 1;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numInter);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    public function getFamilleNumFam($NumFam, $datePrem = null)
    {
        $req = "SELECT 
        f.numero_Famille, 
        p.nom_Parents, 
        GROUP_CONCAT(DISTINCT p.email_Parents SEPARATOR '|') AS emails_Parents,
        GROUP_CONCAT(DISTINCT p.telPortable_Parents SEPARATOR '|') AS tels_Parents,
        f.PM_Famille, 
        f.PGE_Famille, 
        f.cp_Famille, 
        f.adresse_Famille, 
        f.ville_Famille, 
        f.numAlloc_Famille, 
        MAX(e.dateNaiss_Enfants) AS dateNaiss_plus_jeune,
        IFNULL(TIMESTAMPDIFF(YEAR, MAX(e.dateNaiss_Enfants), ?), 0) AS age_plus_jeune
    FROM famille f
    LEFT JOIN parents p 
        ON p.numero_Famille = f.numero_Famille 
        AND p.email_Parents LIKE '%@%'
    LEFT JOIN enfants e 
        ON e.numero_Famille = f.numero_Famille 
        AND e.concernGarde_Enfants != 0
    WHERE f.archive_Famille != 1 
      AND f.numero_Famille = ?
    GROUP BY f.numero_Famille;";
        $cmd = $this->monPdo->prepare($req);
        if ($datePrem == null) {
            $cmd->bindValue(1, date('Y-m-d'));  
        } else {
            $cmd->bindValue(1, $datePrem->format('Y-m-d'));
        }
        
        $cmd->bindValue(2, $NumFam);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    public function getNumsFamille($numPGEouPM)
    {
        $req = "SELECT `numero_Famille`, `PM_Famille`, `PGE_Famille` FROM `famille` WHERE `PM_Famille` = ? OR `PGE_Famille` = ?";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numPGEouPM);
        $cmd->bindValue(2, $numPGEouPM);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getUser($numSS)
    {
        $req = "SELECT `identifiant`,`mdp` FROM `users2` WHERE `identifiant` = ?";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numSS);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getFamille($num)
    {
        $req = "SELECT `famille`.`numero_Famille`, `parents`.`nom_Parents` FROM `famille` LEFT JOIN `parents` ON `parents`.`numero_Famille` = `famille`.`numero_Famille` WHERE `famille`.`numero_Famille` = ? LIMIT 1";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $num);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getReleverMensuelInter($type, $date, $numInter)
    {
        $req = "SELECT * FROM `relevemensuelinter` WHERE `moisannee` = ? AND `numInter` = ? AND `typePresta` = ? LIMIT 1";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date);
        $cmd->bindValue(2, $numInter);
        $cmd->bindValue(3, $type);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    public function getReleverMensuelFam($type, $date, $numFam)
    {
        $req = "SELECT * FROM `relevemensuelfam` WHERE `moisannee` = ? AND `numFam` = ? AND `typePresta` = ? LIMIT 1";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date);
        $cmd->bindValue(2, $numFam);
        $cmd->bindValue(3, $type);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }

    public function getTarifs($date)
    {
        $req = "SELECT * FROM `tarifs2` WHERE `dateDebut` <= ? ORDER BY `dateDebut` DESC, `id` DESC LIMIT 1";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    public function postTarif($alheureGE, $alheureM, $fraisGestion, $parIntervention, $maxParIntervention, $KMenfants, $abonnement, $dateDebut)
    {
    $req = "INSERT INTO `tarifs2`(`alheureGE`, `alheureM`, `fraisGestion`, `parIntervention`, `maxParIntervention`, `KMenfants`, `abonnement`, `dateDebut`) VALUES (?,?,?,?,?,?,?,?)";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $alheureGE);
    $cmd->bindValue(2, $alheureM);
    $cmd->bindValue(3, $fraisGestion);
    $cmd->bindValue(4, $parIntervention);
    $cmd->bindValue(5, $maxParIntervention);
    $cmd->bindValue(6, $KMenfants);
    $cmd->bindValue(7, $abonnement);
    $cmd->bindValue(8, $dateDebut);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function getAdrFam($type, $numInter, $numFam, $date)
    {
        $req = "SELECT f.cp_Famille, f.adresse_Famille, f.ville_Famille,  c.cp_Candidats, c.adresse_Candidats, c.ville_Candidats
        FROM `horaireinter` hi
        JOIN intervenants i ON hi.numInter = i.numSalarie_Intervenants
        JOIN candidats c ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
        JOIN famille f ON hi.numFam = f.numero_Famille
        WHERE hi.desactiver = 0 AND hi.`numFam` = ? AND hi.`numInter` = ? AND hi.`typePresta` = ? AND hi.datePresta LIKE ?
        GROUP BY hi.`numFam`,hi.`numInter`,hi.`typePresta`";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numFam);
        $cmd->bindValue(2, $numInter);
        $cmd->bindValue(3, $type);
        $cmd->bindValue(4, $date); 
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getSaisieHeure($id)
    {
        $req = "SELECT * FROM `horaireinter` WHERE `id` = ? LIMIT 1";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $id);
        $cmd->execute();
        $ligne = $cmd->fetch();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getFamilles($premierJour, $dernierJour, $type, $idInter)
    {
        $req = "SELECT 
        hi.nomFam,
        f.cp_Famille,
        f.ville_Famille,
        f.adresse_Famille,
        hi.numFam 
    FROM horaireinter hi
    LEFT JOIN famille f ON hi.numFam = f.numero_Famille
    WHERE hi.datePresta >= ?
      AND hi.datePresta <= ?
      AND hi.typePresta = ?
      AND hi.numInter = ?
      AND desactiver = 0
    GROUP BY 
        hi.nomFam
    ORDER BY hi.nomFam ASC;
    ";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $premierJour->format('Y-m-d'));
        $cmd->bindValue(2, $dernierJour->format('Y-m-d'));
        $cmd->bindValue(3, $type);
        $cmd->bindValue(4, $idInter);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getIntervenants($premierJour, $dernierJour, $type, $idFam)
    {
        $req = "SELECT 
        CONCAT(c.nom_Candidats, ' ', c.prenom_Candidats) AS nomComplet, hi.numInter
    FROM horaireinter hi
	JOIN intervenants i 
        ON hi.numInter = i.numSalarie_Intervenants
    JOIN candidats c
        ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
    WHERE hi.datePresta >= ?
      AND hi.datePresta <= ?
      AND hi.typePresta = ?
      AND hi.numFam = ?
      AND hi.desactiver = 0
    GROUP BY nomComplet
    ORDER BY nomComplet ASC;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $premierJour->format('Y-m-d'));
        $cmd->bindValue(2, $dernierJour->format('Y-m-d'));
        $cmd->bindValue(3, $type);
        $cmd->bindValue(4, $idFam);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getPresationsInter($premierJour, $dernierJour,$nomFam,$idInter,$type)
    {
        $req = "SELECT 
        datePresta, 
        GROUP_CONCAT( TIMEDIFF(heureFinPresta, heureDebutPresta) SEPARATOR ',') AS durees,
        SUM(kmAvecEnfant) AS kmAvecEnfant
    FROM horaireinter
    WHERE datePresta >= ?
      AND datePresta <= ?
      AND nomFam = ?
      AND numInter = ?
      AND typePresta = ?
      AND desactiver = 0
    GROUP BY datePresta
    ORDER BY datePresta;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $premierJour->format('Y-m-d'));
        $cmd->bindValue(2, $dernierJour->format('Y-m-d'));
        $cmd->bindValue(3, $nomFam);
        $cmd->bindValue(4, $idInter);
        $cmd->bindValue(5, $type);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getPresationsFam($premierJour, $dernierJour,$idFam,$type) {
        $req = "SELECT 
        hi.datePresta, 
        GROUP_CONCAT( TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta) SEPARATOR ',') AS durees,
        SUM(hi.kmAvecEnfant) AS kmAvecEnfant, MIN(hi.validerFam) AS validerFam
    FROM horaireinter hi
    WHERE hi.datePresta >= ?
      AND hi.datePresta <= ?
      AND hi.numFam = ?
      AND hi.typePresta = ?
      AND hi.desactiver = 0
      AND hi.remarqueLe IS NULL
    GROUP BY hi.datePresta
    ORDER BY hi.datePresta;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $premierJour->format('Y-m-d'));
        $cmd->bindValue(2, $dernierJour->format('Y-m-d'));
        $cmd->bindValue(3, $idFam);
        $cmd->bindValue(4, $type);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getTouteIntervenantsPrestations($date)
    {
        $req = "SELECT CONCAT(c.nom_Candidats, ' ', c.prenom_Candidats) AS nomComplet, hi.numInter ,hi.typePresta, hi.nomFam, hi.numFam, SEC_TO_TIME(
            SUM(
                CASE 
                    WHEN TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta) < 0 
                    THEN TIME_TO_SEC(ADDTIME(TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta), '24:00:00'))
                    ELSE TIME_TO_SEC(TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta))
                END
            )
        ) AS dureeTotale, COUNT(*) AS nbrTrajet, IFNULL(SUM(hi.kmAvecEnfant), 0) AS nbrKmAvecEnfa , c.adresse_Candidats, c.cp_Candidats, c.ville_Candidats, f.ville_Famille, f.cp_Famille, f.adresse_Famille, hi.numFam
    FROM horaireinter hi 
    JOIN intervenants i 
        ON hi.numInter = i.numSalarie_Intervenants
    JOIN candidats c
        ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
    JOIN famille f
        ON hi.numFam = f.numero_Famille
    WHERE hi.desactiver = 0 AND (
                (hi.typePresta = 'ENFA' AND hi.datePresta BETWEEN ? AND ?)
                OR
                (hi.typePresta = 'MENA' AND hi.datePresta BETWEEN ? AND ?)
            )
    GROUP BY hi.numFam, hi.numInter, hi.typePresta;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date->format('Y-m-01'));  // début ENFA
$cmd->bindValue(2, $date->format('Y-m-t'));   // fin ENFA

if ((int)$date->format('d') >= 25) {
    // période MENA = du 25 de ce mois au 24 du mois suivant
    $cmd->bindValue(3, $date->format('Y-m-25'));

    $dateEnd = (clone $date)->modify('+1 month'); 
    $cmd->bindValue(4, $dateEnd->format('Y-m-24'));
} else {
    // période MENA = du 25 du mois précédent au 24 de ce mois
    $dateStart = (clone $date)->modify('-1 month');
    $cmd->bindValue(3, $dateStart->format('Y-m-25'));

    $cmd->bindValue(4, $date->format('Y-m-24'));
}

        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function preparationPaie($date)
    {
        $req = "SELECT 
        hi.numInter,    
        CONCAT(c.nom_Candidats, ' ', c.prenom_Candidats) AS nomComplet,
            hi.typePresta,
            SEC_TO_TIME(
                SUM(
                    CASE 
                        WHEN TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta) < 0 
                        THEN TIME_TO_SEC(
                            ADDTIME(TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta), '24:00:00')
                        )
                        ELSE TIME_TO_SEC(TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta))
                    END
                )
            ) AS dureeTotale,
            COUNT(*) AS nbrTrajet,
            IFNULL(SUM(hi.kmAvecEnfant), 0) AS kmAvecEnfant,
            GROUP_CONCAT(
                CASE 
                    WHEN f.cp_Famille NOT IN ('35000', '35200', '35700', '') 
                    THEN hi.numFam 
                    ELSE NULL 
                END 
                SEPARATOR '|'
            ) AS famhorsrennes
        FROM 
            horaireinter hi
            JOIN intervenants i ON hi.numInter = i.numSalarie_Intervenants
            JOIN candidats c ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
            JOIN famille f ON hi.numFam = f.numero_Famille
        WHERE 
            hi.desactiver = 0
            AND (
                (hi.typePresta = 'ENFA' AND hi.datePresta BETWEEN ? AND ?)
                OR
                (hi.typePresta = 'MENA' AND hi.datePresta BETWEEN ? AND ?)
            )
        GROUP BY 
            hi.numInter, 
            hi.typePresta;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date->format('Y-m-01'));  // début ENFA
$cmd->bindValue(2, $date->format('Y-m-t'));   // fin ENFA

if ((int)$date->format('d') >= 25) {
    // période MENA = du 25 de ce mois au 24 du mois suivant
    $cmd->bindValue(3, $date->format('Y-m-25'));

    $dateEnd = (clone $date)->modify('+1 month'); 
    $cmd->bindValue(4, $dateEnd->format('Y-m-24'));
} else {
    // période MENA = du 25 du mois précédent au 24 de ce mois
    $dateStart = (clone $date)->modify('-1 month');
    $cmd->bindValue(3, $dateStart->format('Y-m-25'));

    $cmd->bindValue(4, $date->format('Y-m-24'));
}

        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    /**
     * Retourne l'utilisateur d'un numSS
     * @return le numSS et le mdp sous la forme d'un tableau
     */
    public function getToutePresations($idInter)
    {
        $req = "SELECT *
    FROM horaireinter
    WHERE numInter = ?
    ORDER BY `datePresta` DESC";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $idInter);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getPresationsAvalider($date, $numFam, $null = true)
    {
        $req = "SELECT hi.`id`,CONCAT(c.nom_Candidats, ' ', c.prenom_Candidats) AS nomComplet, hi.`datePresta`, hi.`heureDebutPresta`,hi.`heureFinPresta`,TIMEDIFF(hi.heureFinPresta, hi.heureDebutPresta) AS duree,hi.`typePresta`,hi.`kmAvecEnfant`,hi.`remarque` 
        FROM `horaireinter` hi 
            JOIN intervenants i 
                ON hi.numInter = i.numSalarie_Intervenants
            JOIN candidats c
                ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats 
        WHERE hi.`validerFam` = 0 AND hi.`desactiver`= 0 
        AND (
                (hi.typePresta = 'ENFA' AND hi.datePresta BETWEEN ? AND ?)
                OR
                (hi.typePresta = 'MENA' AND hi.datePresta BETWEEN ? AND ?)
            )   
        AND hi.`numFam` = ?
        ";
        // Ajout de la condition dynamique sur la remarque
    if ($null) {
        $req .= " AND hi.`remarqueLe` IS NULL";
    } else {
        $req .= " AND hi.`remarqueLe` IS NOT NULL";
    }
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $date->format('Y-m-01'));  // début ENFA
$cmd->bindValue(2, $date->format('Y-m-t'));   // fin ENFA

if ((int)$date->format('d') >= 25) {
    // période MENA = du 25 de ce mois au 24 du mois suivant
    $cmd->bindValue(3, $date->format('Y-m-25'));

    $dateEnd = (clone $date)->modify('+1 month'); 
    $cmd->bindValue(4, $dateEnd->format('Y-m-24'));
} else {
    // période MENA = du 25 du mois précédent au 24 de ce mois
    $dateStart = (clone $date)->modify('-1 month');
    $cmd->bindValue(3, $dateStart->format('Y-m-25'));

    $cmd->bindValue(4, $date->format('Y-m-24'));
}
        $cmd->bindValue(5, $numFam);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getPresationsSignaler($ancien)
    {
    $conditionRemarque = ($ancien == 0) ? "IS NOT NULL" : "IS NULL";

    $req = "SELECT hi.`id`,
                   CONCAT(c.nom_Candidats, ' ', c.prenom_Candidats) AS nomCompletInter,
                   hi.numInter, hi.nomFam, hi.numFam, hi.`remarque`,
                   hi.`datePresta`, hi.`heureDebutPresta`, hi.`heureFinPresta`,
                   hi.`typePresta`, hi.`kmAvecEnfant`
            FROM `horaireinter` hi
            JOIN intervenants i ON hi.numInter = i.numSalarie_Intervenants
            JOIN candidats c ON i.candidats_numcandidat_candidats = c.numCandidat_Candidats
            WHERE hi.`desactiver` = 0
              AND hi.`remarque` IS NOT NULL
              AND hi.`remarqueLe` $conditionRemarque";

    $cmd = $this->monPdo->prepare($req);
    $cmd->execute();
    return $cmd->fetchAll();
    }

    /**
     * Retourne les familles lier a l'intervenant
     * @return le num_famille et nom_famille sous la forme d'un tableau
     */
    public function getFamillesIntervenantNumSS($numSS)
    {
        $req = "SELECT 
        famille.numero_Famille, 
        parents.nom_parents, 
        GROUP_CONCAT(DISTINCT proposer.idPresta_Prestations ORDER BY proposer.idPresta_Prestations SEPARATOR ', ') AS prestations
    FROM candidats
    LEFT JOIN intervenants 
        ON intervenants.candidats_numcandidat_candidats = candidats.numCandidat_Candidats
    LEFT JOIN proposer 
        ON proposer.numSalarie_Intervenants = intervenants.numSalarie_Intervenants
    LEFT JOIN famille 
        ON proposer.numero_Famille = famille.numero_Famille
    LEFT JOIN parents 
        ON parents.numero_Famille = famille.numero_Famille
    WHERE candidats.numSS_Candidats = ?
      AND (proposer.dateFin_Proposer = '0000-00-00' OR proposer.dateFin_Proposer > CURDATE())
      AND famille.numero_Famille != 9999
    GROUP BY famille.numero_Famille, parents.nom_parents 
    ORDER BY `parents`.`nom_parents` ASC;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numSS);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    public function getIntervenantsFamille($numFam)
    {
        $req = "SELECT 
        proposer.numSalarie_Intervenants,
		candidats.nom_Candidats,
		candidats.prenom_Candidats,
        GROUP_CONCAT(DISTINCT proposer.idPresta_Prestations ORDER BY proposer.idPresta_Prestations SEPARATOR ', ') AS prestations
    FROM famille
    LEFT JOIN proposer 
        ON proposer.numero_Famille = famille.numero_Famille
	LEFT JOIN intervenants
		ON intervenants.numSalarie_Intervenants = proposer.numSalarie_Intervenants
    LEFT JOIN candidats
		ON candidats.numCandidat_Candidats = intervenants.candidats_numcandidat_candidats
    WHERE famille.numero_Famille = ?
      AND (proposer.dateFin_Proposer = '0000-00-00' OR proposer.dateFin_Proposer > CURDATE())
    GROUP BY proposer.numSalarie_Intervenants
    ORDER BY candidats.nom_Candidats ASC;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numFam);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    /**
     * Retourne les familles lier a l'intervenant
     * @return le num_famille et nom_famille sous la forme d'un tableau
     */
    public function getFamillesIntervenantNumInter($numInter)
    {
        $req = "SELECT 
        famille.numero_Famille, 
        parents.nom_parents, 
        GROUP_CONCAT(DISTINCT proposer.idPresta_Prestations ORDER BY proposer.idPresta_Prestations SEPARATOR ', ') AS prestations
    FROM candidats
    LEFT JOIN intervenants 
        ON intervenants.candidats_numcandidat_candidats = candidats.numCandidat_Candidats
    LEFT JOIN proposer 
        ON proposer.numSalarie_Intervenants = intervenants.numSalarie_Intervenants
    LEFT JOIN famille 
        ON proposer.numero_Famille = famille.numero_Famille
    LEFT JOIN parents 
        ON parents.numero_Famille = famille.numero_Famille
    WHERE intervenants.numSalarie_Intervenants = ?
      AND (proposer.dateFin_Proposer = '0000-00-00' OR proposer.dateFin_Proposer > CURDATE())
      AND famille.numero_Famille != 9999
    GROUP BY famille.numero_Famille, parents.nom_parents 
    ORDER BY `parents`.`nom_parents` ASC;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue(1, $numInter);
        $cmd->execute();
        $ligne = $cmd->fetchAll();
        return $ligne;
    }
    /**
    * Insère un nouvel utilisateur dans la base de données.
    *
    * @param string $numSS L'identifiant de l'utilisateur (numéro de sécurité sociale)
    * @param string $password Le mot de passe de l'utilisateur
    * @return bool True si l'insertion a réussi, false sinon
    */
    public function postUser($numSS, $password)
    {
    $req = "INSERT INTO `users2` (`identifiant`, `mdp`) VALUES (?, ?);";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $numSS);
    $cmd->bindValue(2, $password);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }

    /**
    * Insère un nouvel utilisateur dans la base de données.
    *
    * @param string $numSS L'identifiant de l'utilisateur (numéro de sécurité sociale)
    * @param string $password Le mot de passe de l'utilisateur
    * @return bool True si l'insertion a réussi, false sinon
    */
    public function postReleverMensuelInter($type, $date, $numInter)
    {
    $req = "INSERT INTO `relevemensuelinter` (`moisannee`, `numInter`, `typePresta`, `heureDehors`, `heureDehorsAjouterLe`, `signer`, `signerLe`) VALUES (?, ?, ?, NULL, NULL, '0', NULL);";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $date);
    $cmd->bindValue(2, $numInter);
    $cmd->bindValue(3, $type);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function postReleverMensuelFam($type, $date, $numFam)
    {
    $req = "INSERT INTO `relevemensuelfam` (`moisannee`, `numFam`, `typePresta`) VALUES (?, ?, ?);";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $date);
    $cmd->bindValue(2, $numFam);
    $cmd->bindValue(3, $type);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }

    /**
    * Insère un nouvel utilisateur dans la base de données.
    *
    * @param string $numSS L'identifiant de l'utilisateur (numéro de sécurité sociale)
    * @param string $password Le mot de passe de l'utilisateur
    * @return bool True si l'insertion a réussi, false sinon
    */
    public function putSignerInter($date, $type, $numInter)
    {
    $req = "UPDATE relevemensuelinter
    SET 
        signer = 1,
        signerLe =  NOW()
    WHERE numInter = ? AND moisannee = ? AND typePresta = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $numInter);
    $cmd->bindValue(2, $date);
    $cmd->bindValue(3, $type); 
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putSignerFam($date, $type, $numFam,$Ponctualite,$ReguRela,$RespectHo,$QualiteTr)
    {
    $req = "UPDATE `relevemensuelfam` 
    SET `avisPonctualite` = ?, 
    `avisReguRela` = ?, 
    `avisRespectHo` = ?, 
    `avisQualiteTr` = ?, 
    `signerLe` = NOW() 
    WHERE `numFam` = ? 
    AND `moisannee` = ? 
    AND `typePresta` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $Ponctualite);
    $cmd->bindValue(2, $ReguRela);
    $cmd->bindValue(3, $RespectHo);
    $cmd->bindValue(4, $QualiteTr);
    $cmd->bindValue(5, $numFam);
    $cmd->bindValue(6, $date);
    $cmd->bindValue(7, $type); 
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putSuplement($date, $type, $numFam, $libeler, $montant)
    {
    $req = "UPDATE `relevemensuelfam` 
    SET `libelerSupl` = ?, 
    `montantSupl` = ?
    WHERE `numFam` = ? 
    AND `moisannee` = ? 
    AND `typePresta` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $libeler);
    $cmd->bindValue(2, $montant);
    $cmd->bindValue(3, $numFam);
    $cmd->bindValue(4, $date);
    $cmd->bindValue(5, $type); 
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putNewMdp($numSS,$password)
    {
    $req = "UPDATE `users2` SET `mdp` = ? 
    WHERE `users2`.`identifiant` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $password);
    $cmd->bindValue(2, $numSS);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    /**
    * Insère un nouvel utilisateur dans la base de données.
    *
    * @param string $numSS L'identifiant de l'utilisateur (numéro de sécurité sociale)
    * @param string $password Le mot de passe de l'utilisateur
    * @return bool True si l'insertion a réussi, false sinon
    */
    public function putArchiverHoraire($id)
    {
    $req = "UPDATE `horaireinter` SET `desactiver` = '1' WHERE `id` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $id);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }

    public function putRecupererHoraire($id)
    {
    $req = "UPDATE `horaireinter` SET `desactiver` = '0' WHERE `id` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $id);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putValiderHoraire($id)
    {
    $req = "UPDATE `horaireinter` SET `validerFam`= '1',`validerLe`= NOW() WHERE `id` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $id);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putRemarqueHoraire($id, $remarque)
    {
    $req = "UPDATE `horaireinter` SET `remarque` = IFNULL(CONCAT(`remarque`, ' // ', ?), ?), `remarqueLe`= NOW() WHERE `id` = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $remarque);
    $cmd->bindValue(2, $remarque);
    $cmd->bindValue(3, $id);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    /**
    * Insère un nouvel utilisateur dans la base de données.
    *
    * @param string $numSS L'identifiant de l'utilisateur (numéro de sécurité sociale)
    * @param string $password Le mot de passe de l'utilisateur
    * @return bool True si l'insertion a réussi, false sinon
    */
    public function putHeureDehors($date, $type, $numInter, $heureDehors)
    {
    $req = "UPDATE relevemensuelinter
    SET 
        heureDehors = ?,
        heureDehorsAjouterLe = NOW()
    WHERE numInter = ? AND moisannee = ?;";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $heureDehors);
    $cmd->bindValue(2, $numInter);
    $cmd->bindValue(3, $date);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    public function putTypePaiment($moisReleve,$typePresta,$numFam,$typePaiment,$montantPrincipal,$numCheque,$nbrCESU,$typeComplement,$montantComplement)
    {
    $req = "UPDATE `relevemensuelfam` 
    SET `typeRèglement`=?,`numChèque`=?,`nbrCESU`=?,`montantPrincipal`=?,
    `complementCESU`=?,`montantComplement`=? 
    WHERE `numFam`=? AND `moisannee`=? AND `typePresta`=?";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $typePaiment);
    $cmd->bindValue(2, $numCheque);
    $cmd->bindValue(3, $nbrCESU);
    $cmd->bindValue(4, $montantPrincipal);
    $cmd->bindValue(5, $typeComplement);
    $cmd->bindValue(6, $montantComplement);
    $cmd->bindValue(7, $numFam);
    $cmd->bindValue(8, $moisReleve);
    $cmd->bindValue(9, $typePresta);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    /**
 * Insère un nouvel enregistrement de taux horaire dans la base de données.
 *
 * @param string $numFam Numéro de la famille
 * @param string $nomFam Nom de la famille
 * @param int $numInt Numéro de l'intervenant
 * @param string $date Date de la prestation (format YYYY-MM-DD)
 * @param string $hDebut Heure de début de la prestation (format HH:MM:SS)
 * @param string $hFin Heure de fin de la prestation (format HH:MM:SS)
 * @param string $type Type de prestation
 * @return bool True si l'insertion a réussi, false sinon
 */
    public function postHoraireInter($numFam, $nomFam, $numInt, $date, $hDebut, $hFin, $type, $trajet = null)
    {
    $req = "INSERT INTO horaireinter (numFam, nomFam, numInter, datePresta, heureDebutPresta, heureFinPresta, typePresta, ajouterLe, kmAvecEnfant)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?);";
    $cmd = $this->monPdo->prepare($req);
    $cmd->bindValue(1, $numFam);
    $cmd->bindValue(2, $nomFam);
    $cmd->bindValue(3, $numInt);
    $cmd->bindValue(4, $date);
    $cmd->bindValue(5, $hDebut);
    $cmd->bindValue(6, $hFin);
    $cmd->bindValue(7, $type);
    $cmd->bindValue(8, $trajet);
    $success = $cmd->execute();

    return $success; // true si l'insertion a réussi
    }
    
    /**
 * Insère un nouvel enregistrement de taux horaire dans la base de données.
 *
 * @param string $numFam Numéro de la famille
 * @param string $nomFam Nom de la famille
 * @param int $numInt Numéro de l'intervenant
 * @param string $date Date de la prestation (format YYYY-MM-DD)
 * @param string $hDebut Heure de début de la prestation (format HH:MM:SS)
 * @param string $hFin Heure de fin de la prestation (format HH:MM:SS)
 * @param string $type Type de prestation
 * @return bool True si l'insertion a réussi, false sinon
 */
public function putHoraireInter($numFam, $nomFam, $date, $hDebut, $hFin, $type, $trajet, $id)
{
$req = "UPDATE `horaireinter` 
SET
`numFam`=?,
`nomFam`=?,
`datePresta`=?,
`heureDebutPresta`=?,
`heureFinPresta`=?,
`typePresta`=?,
`kmAvecEnfant`=?,
`modifierLe`= NOW(),
`remarqueLe`= NULL
WHERE `id` = ?";
$cmd = $this->monPdo->prepare($req);
$cmd->bindValue(1, $numFam);
$cmd->bindValue(2, $nomFam);
$cmd->bindValue(3, $date);
$cmd->bindValue(4, $hDebut);
$cmd->bindValue(5, $hFin);
$cmd->bindValue(6, $type);
if ($type == "ENFA") {
    $cmd->bindValue(7, $trajet);
} else {
    $cmd->bindValue(7, null);
}
$cmd->bindValue(8, $id);

$success = $cmd->execute();

return $success; // true si l'insertion a réussi
}

    /**
     * Retourne les administareurs
     * @return l'id sous la forme d'un tableau associatif
     */
    // public function checkAdmin($id)
    // {
    //     $req = "select id from users where id = ?";
    //     $cmd = $this->monPdo->prepare($req);
    //     $cmd->bindValue(1, $id);
    //     $cmd->execute();
    //     $ligne = $cmd->fetch();
    //     return $ligne;
    // }


    /**
     * Retourne les familles dont le nom commence par le terme recherché
     * @param $term
     * @return PDO::FETCH_ASSOC un tableau associatif contenant les noms des familles
     *         dont le nom commence par le terme recherché
     */
    // public function chercherFamilles($term)
    // {
    //     $req = "SELECT DISTINCT parents.nom_Parents AS nom
    //             FROM parents
    //             JOIN proposer ON proposer.numero_Famille = parents.numero_Famille
    //             WHERE parents.nom_Parents LIKE :term
    //             AND (
    //                 proposer.dateFin_Proposer > NOW() 
    //                 OR proposer.dateFin_Proposer = '0000-00-00'
    //             )
    //             AND proposer.numSalarie_Intervenants = ". $_SESSION["numSalarie_Intervenants"] ."
    //             ORDER BY nom
    //             LIMIT 10;";

    //     $cmd = $this->monPdo->prepare($req);
    //     $cmd->bindValue(':term', $term . '%', PDO::PARAM_STR);
    //     $cmd->execute();
    //     $lignes = $cmd->fetchAll(PDO::FETCH_ASSOC);
    //     return $lignes;
    // }


}