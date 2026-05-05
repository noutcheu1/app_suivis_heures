<?php
require_once './config.php';
ob_start();
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra');

$joursFrancais = [
    'Sunday' => 'dimanche',
    'Monday' => 'lundi',
    'Tuesday' => 'mardi',
    'Wednesday' => 'mercredi',
    'Thursday' => 'jeudi',
    'Friday' => 'vendredi',
    'Saturday' => 'samedi'
];
$moisComplets = [
    1 => "JANVIER",
    2 => "FÉVRIER",
    3 => "MARS",
    4 => "AVRIL",
    5 => "MAI",
    6 => "JUIN",
    7 => "JUILLET",
    8 => "AOÛT",
    9 => "SEPTEMBRE",
    10 => "OCTOBRE",
    11 => "NOVEMBRE",
    12 => "DÉCEMBRE"
];



if ($_GET['type'] == "ENFA") {
    $premierJour = new DateTime(date('Y-m-01'));
} else if ($_GET['type'] == "MENA") {
    $premierJour = new DateTime(date('Y-m-25'));
    if (date('d') < 25) {
        $premierJour->modify('-1 month');
    } 
    
} if (isset($_GET['mois'])) { 
    $premierJour->modify($_GET['mois'].' month');
}
$dernierJour = clone $premierJour;
$dernierJour->modify('+1 month -1 day');

if (isset($_POST) && isset($_GET['signer'])) {
    if ($GLOBALS['pdo']->putSignerFam($dernierJour->format('Y-m'),$_GET['type'],$_SESSION["username"],$_POST['Ponctualite'],$_POST['ReguRela'],$_POST['RespectHo'],$_POST['QualiteTr'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "<script>
            alert('Une erreur est survenue, veuillez réessayer.');
            window.history.back();
        </script>";
    }
}

if (isset($_GET['fam']) && $_SESSION["username"] == "9.99.99.99.999.999.99") {
    $user0 = $GLOBALS['pdo']->getFamilleNumFam($_GET['fam'], $premierJour); 
} else {
    $user0 = $GLOBALS['pdo']->getFamilleNumFam($_SESSION["username"], $premierJour);
}
    
$user = $user0['numero_Famille'];

if (isset($_POST) && isset($_GET['suplement'])) {
    if ($_POST['montant'] == "" || $_POST['montant'] == 0 ) {
        $_POST['libelle'] = null;
        $_POST['montant'] = null;
    }
    if ($GLOBALS['pdo']->putSuplement($dernierJour->format('Y-m'),$_GET['type'],$user,$_POST['libelle'],$_POST['montant'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "<script>
            alert('Une erreur est survenue, veuillez réessayer.');
            window.history.back();
        </script>";
    }
}

if (isset($_POST) && isset($_GET['paiement'])) {
    if ($_POST['moyen_paiement'] != "") {
        $typePaiment = $_POST['moyen_paiement'];
        if ($_POST['montant'] == "") {
            $montantPrincipal = NULL;
        } else {
            $montantPrincipal = $_POST['montant'];
        }
        
        $numCheque = NULL;
        $nbrCESU = NULL;
        $typeComplement = NULL;
        $montantComplement = NULL; 
        $moisReleve = $dernierJour->format('Y-m');
        if ($typePaiment == "cheque") {
            $numCheque = $_POST['numCheque'];
        } else if ($typePaiment == "cesu") {
            $nbrCESU = $_POST['nbrCESU'];
            $typeComplement = $_POST['complement'];
            if ($typeComplement == "cheque") {
                $numCheque = $_POST['numCheque'];
                $montantComplement = $_POST['montantCC'];
            } else if ($typeComplement == "prelevement") {
                $montantComplement = $_POST['montantCP'];
            }
        }
        if (!$GLOBALS['pdo']->putTypePaiment($moisReleve,$_GET['type'],$user,$typePaiment,$montantPrincipal,$numCheque,$nbrCESU,$typeComplement,$montantComplement)) {
            echo "<script>
                alert('Une erreur est survenue, veuillez réessayer.');
                window.history.back();
            </script>";
        }
    }
}





// Récupérer le relevé mensuel
$moisReleve = $dernierJour->format('Y-m');
$releverMensuel = $GLOBALS['pdo']->getReleverMensuelFam($_GET['type'], $moisReleve, $user);

// Si le relevé n'existe pas et que le mois est après juillet 2025, on le crée
if (!$releverMensuel && $moisReleve > "2025-07") {
    $GLOBALS['pdo']->postReleverMensuelFam($_GET['type'], $moisReleve, $user);
    $releverMensuel = $GLOBALS['pdo']->getReleverMensuelFam($_GET['type'], $moisReleve, $user);
} elseif ($moisReleve <= "2025-07") {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$tarifs = $GLOBALS['pdo']->getTarifs($moisReleve);



$Intervenants = $GLOBALS['pdo']->getIntervenants($premierJour,$dernierJour,$_GET['type'],$user);

$prestations = $GLOBALS['pdo']->getPresationsFam($premierJour,$dernierJour,$user,$_GET['type']);

$totalKmAvecEnfan = 0;
$totalHeure = 0;
$nbrPresta = 0;
foreach ($prestations as $item2) {
    if ($item2['kmAvecEnfant'] != null) {
        $totalKmAvecEnfan += $item2['kmAvecEnfant'];
    }
    if (strpos($item2['durees'], ',') !== false) {
        // Séparer les différentes durées
        $durees = explode(',', $item2['durees']);

        $formattedDurations = [];
        foreach ($durees as $duree) {
            list($h, $m, $s) = explode(":", $duree);
            if ($item2['validerFam'] == 1) {
                $totalHeure += $h * 3600 + $m * 60 + $s;
            }
            $formattedDurations[] = (int)$h . "h" . str_pad($m, 2, "0", STR_PAD_LEFT);
            $nbrPresta += 1;
        }
        $prestationF[$item2['datePresta']] = [implode(" - ", $formattedDurations),$item2['validerFam']];
    } else {
        list($h, $m, $s) = explode(":", $item2['durees']);
        if ($item2['validerFam'] == 1) {
            $totalHeure += $h * 3600 + $m * 60 + $s;
        }
        
        $prestationF[$item2['datePresta']] = [(int)$h . "h" . str_pad($m, 2, "0", STR_PAD_LEFT),$item2['validerFam']];
        $nbrPresta += 1;
    }
}












// corp de la feuilles

?>
<main>
    <h1>
    <?php 
    if ($_SESSION["username"] == "9.99.99.99.999.999.99") {
        echo "Voici la/les fiche/s de recapitulatif d'heures de ";
        echo "<a href='".BASE_URL."/admin/familles.php?id=". $_GET['fam'] ."'>". $user0['nom_Parents']."</a><br>";
        
    } else {
        echo "Famille " . $user0['nom_Parents'] .", voici votre fiche de recapitulatif d'heures <br>";
    }
    echo $moisComplets[(int)$dernierJour->format('n')] . " " . $dernierJour->format('Y')."<br>";
    
    if ($_GET['type'] == "MENA") {
        echo "Ménage";
    } else if ($_GET['type'] == "ENFA") {
        echo "Garde d'enfant";
    }
    
    ?>
    </h1>
    
    
    <ul class="menu">
        <li>
            <a href="<?= BASE_URL ?>/mon-recap?type=<?= $_GET['type']?>&mois=<?= $_GET['mois'] - 1 ?><?php if ($_SESSION["username"] == "9.99.99.99.999.999.99") { ?>&fam=<?= $_GET['fam'] ?><?php } ?>">
                <img src="<?= BASE_URL ?>/assets/icons/flecheGaucheR.png">
                Mois precedente
            </a>
        </li>
        <?php if ($_GET['mois'] < 0) { ?>
            <li> 
                <a href="<?= BASE_URL ?>/mon-recap?type=<?= $_GET['type']?>&mois=<?= $_GET['mois'] + 1 ?><?php if ($_SESSION["username"] == "9.99.99.99.999.999.99") { ?>&fam=<?= $_GET['fam'] ?><?php } ?>">
                    <img src="<?= BASE_URL ?>/assets/icons/flecheDroiteR.png" >
                    Mois suivante
                </a>
            </li>
        <?php } ?>
    </ul>
    
    <table id="monTableau1">
    <?php if ($_GET['type'] == "ENFA") {?>
            <tr> <th colspan='4' style="text-align: left;" class="no-borders"> <u> ENFANT GARDÉ LE PLUS JEUNE :</u> &nbsp; &nbsp;
            <?php 
            if ($user0['age_plus_jeune'] < 3) {
                echo "[-3 ANS] ";
            } else if ($user0['age_plus_jeune'] < 6) {
                echo "[+3 et -6 ANS] ";
            } else {
                echo "[+6 ANS] ";
            }
            echo date("d/m/Y", strtotime($user0['dateNaiss_plus_jeune'])) ;
            ?>
             </th> </tr> <?php } ?>
            <tr> <th style="text-align: left;" class="no-borders"> <u> NOM DE L'INTERVENANTE : </u> &nbsp; &nbsp;
            <?php 
            for ($i = 0; $i <= 3; $i++) {
                
                if (isset($Intervenants[$i])) {
                    echo $Intervenants[$i][0];
                    if ($Intervenants[$i][1] == 0) {
                        echo " OCCASIONNELLE";
                    }
                    echo "&nbsp; &nbsp;";
                }
            }
            ?> </th>
            </tr>
            <tr> <th colspan='5' style="text-align: left;" class="no-borders"> <br> <u> NOMBRE D'HEURES EFFECTUÉES :</u> DU 
            <?= $premierJour->format('d')." ".$moisComplets[$premierJour->format('n')]." ".$premierJour->format('Y')." au ".$dernierJour->format('d')." ".$moisComplets[$dernierJour->format('n')]." ".$dernierJour->format('Y') ?> 
            <br>
            Durée minimum d'une intervention à Rennes 1h30 en dehors de Rennes 2h
            <br> 
            
            <table>
            <?php 
            $Xjour = clone $premierJour;
            $Xjour2 = clone $premierJour;
            for ($i = 0; $i <= 3; $i++) {
                echo "<tr>";
                for ($y = 1; $y <= 8; $y++) {
                    if (strtoupper($joursFrancais[date('l', strtotime($Xjour->format('Y-m-d')))][0]) == "D" && $i*8+$y != 32) {
                        echo "<th style='width: 12%;background-color: #e0e0e0;'>";
                    } else {
                        echo "<th style='width: 12%;'>";
                    }
                    
                    if ($Xjour == $premierJour) {
                        echo strtoupper($joursFrancais[date('l', strtotime($Xjour->format('Y-m-d')))][0])." ".$Xjour->format('d/m');
                    } 
                    else if ($Xjour == $dernierJour) {
                        echo strtoupper($joursFrancais[date('l', strtotime($Xjour->format('Y-m-d')))][0])." ".$dernierJour->format('d/m');
                        $y = 7;
                    } 
                    else if ($i*8+$y == 32) {
                        echo "TOTAL";
                    } 
                    else {
                        echo strtoupper($joursFrancais[date('l', strtotime($Xjour->format('Y-m-d')))][0])." ".$Xjour->format('d');
                    }
                    echo "</th>";
                    $Xjour->modify('+1 day');
                }   
                echo "</tr><tr>";
                for ($y = 1; $y <= 8; $y++) {
                    if ($Xjour2 == $dernierJour) {
                        $y = 7;
                    } 
                    if (isset($prestationF[$Xjour2->format('Y-m-d')])) {
                        if ($prestationF[$Xjour2->format('Y-m-d')][1] == 0) {
                            echo "<td style='background-color: #ff8888;border-bottom: 3px solid black;'>";
                        } else {
                            if (strtoupper($joursFrancais[date('l', strtotime($Xjour2->format('Y-m-d')))][0]) == "D" && $i*8+$y != 32) {
                                echo "<td style='background-color: #e0e0e0;border-bottom: 3px solid black;'>";
                            } else {
                                echo "<td style='border-bottom: 3px solid black;'>";
                            }
                        }
                    } else {
                        if (strtoupper($joursFrancais[date('l', strtotime($Xjour2->format('Y-m-d')))][0]) == "D" && $i*8+$y != 32) {
                            echo "<td style='background-color: #e0e0e0;border-bottom: 3px solid black;'>";
                        } else {
                            echo "<td style='border-bottom: 3px solid black;'>";
                        }
                    }
                    if ($i*8+$y == 32) { 
                        list($h, $m, $s) = explode(":", gmdate("H:i:s", $totalHeure));
                        echo  (int)$h . "h" . str_pad($m, 2, "0", STR_PAD_LEFT);
                    } else if (isset($prestationF[$Xjour2->format('Y-m-d')])) {
                        if ($prestationF[$Xjour2->format('Y-m-d')][1] == 1) {
                            echo $prestationF[$Xjour2->format('Y-m-d')][0];
                        } else {
                            echo "<b><a href='". BASE_URL ."/valider-heures?mois=".$_GET['mois']."'>à valider</a></b>";
                        } 
                        
                    } else {
                        echo "<br>";
                    }
                    echo "</td>";
                    $Xjour2->modify('+1 day');
                }   
                echo "</tr>";
            } ?>
            </table>
            </th> </tr>















            <!-- zone des tarifications -->
            
            
            <tr> <td colspan='5' class="no-borders"> 
            <table>
            <tr> <td style='width: 12%;' class="no-borders" rowspan="15"> </td> <th class="no-borders"> SOMME A PAYER </th> <th> Tarif </th> <th> Montant </th> <th> Règlement par : </th> <td style='width: 12%;' class="no-borders"> </td> </tr>
            <tr> <th> Nbre d'Heures réalisées </th> <td> <?php 
            $TotalCout = 0;
            list($h, $m, $s) = explode(":", gmdate("H:i:s", $totalHeure));
            echo  (int)$h . "h" . str_pad($m, 2, "0", STR_PAD_LEFT)." x ";
            
            if ($_GET['type'] == "ENFA") {
                $tarifAheureGE = json_decode($tarifs['alheureGE']);

                foreach ($tarifAheureGE as $t) {
                    if (eval("return (".$t[0].");")) {
                        if ($t[1] == "V") {
                            echo "24,40 €/H";
                            $tarif = 24.4;
                        } else {
                            echo number_format($t[1], 2)." €/H";
                            $tarif = $t[1];
                        }
                        
                        break; // on sort dès qu'on trouve le tarif
                    }
                }
            } else {
                $tarifAheureM = json_decode($tarifs['alheureM']);

                foreach ($tarifAheureM as $t) {
                    if (eval("return (".$t[0].");")) {
                        if ($t[1] == "V") {
                            echo "23,40 €/H";
                            $tarif = 23.4;
                        } else {
                            echo number_format($t[1], 2)." €/H";
                            $tarif = $t[1];
                        }
                        
                        break; // on sort dès qu'on trouve le tarif
                    }
                }
            }
            $TotalCout += $totalHeure*($tarif / 3600); // le $totalHeure est en seconde
            ?> </td> <td> <?= number_format($totalHeure*($tarif / 3600), 2, '.', '')." €" ?> </td> 
            
            <td rowspan="15"> 
            <?php if ($_GET['mois'] >= -1 && $releverMensuel['montantPrincipal'] == null || $_SESSION["username"] == "9.99.99.99.999.999.99" ) { ?>
            
            <form id="paiementForm" action="<?= BASE_URL ?>/mon-recap?type=<?= $_GET['type'] ?>&mois=<?= $_GET['mois'] ?>&paiement=1<?php if (isset($_GET['fam'])) {echo "&fam=".$_GET['fam'];} ?>" method="POST">
                <label for="moyen_paiement">Moyen de paiement :</label>
                <select name="moyen_paiement" id="moyen_paiement" required>
                    <option value="" disabled selected>à Choisir</option>
                    <option value="cheque" <?php if ($releverMensuel['typeRèglement'] == "cheque") {echo "selected";} ?> >Chèque</option> 
                    <option value="prelevement" <?php if ($releverMensuel['typeRèglement'] == "prelevement") {echo "selected";} ?>>Prélèvement</option>
                    <option value="cesu" <?php if ($releverMensuel['typeRèglement'] == "cesu") {echo "selected";} ?>>CESU</option>
                </select>

                <div id="paiementDetails"></div>

                <input type="submit" value="Valider">
            </form> <?php    
        } else if ($releverMensuel['typeRèglement'] == null) { ?>
                Non renseigne 
            <?php } if ($_SESSION["username"] == "9.99.99.99.999.999.99" || $releverMensuel['montantPrincipal'] != null) { 
                if ($releverMensuel['typeRèglement'] == "cheque") {
                    echo "Chèque <br> N° : ".$releverMensuel['numChèque']."  <br> Montant : ".$releverMensuel['montantPrincipal']." €";
                } else if ($releverMensuel['typeRèglement'] == "prelevement") {
                    echo "Prélèvement <br> Montant : ".$releverMensuel['montantPrincipal']." €";
                } else if ($releverMensuel['typeRèglement'] == "cesu") {
                    echo "CESU <br> Nbr : ".$releverMensuel['nbrCESU']." <br> Montant total : ".$releverMensuel['montantPrincipal']." €";
                    if ($releverMensuel['complementCESU'] != "aucun") {
                        if ($releverMensuel['complementCESU'] == "cheque") {
                            echo "<br><br> Complement chèque <br> N° : ".$releverMensuel['numChèque']."  <br> Montant : ".$releverMensuel['montantComplement']." €";
                        } else if ($releverMensuel['complementCESU'] == "prelevement") {
                            echo "<br><br> Complement prélèvement <br> Montant : ".$releverMensuel['montantComplement']." €";
                        }
                    }
                } 
            }?>
            </td> </tr>
            <?php if ($_GET['type'] == "ENFA") { ?>
            <tr> <th> Frais de gestion </th>
            <?php
            $tarifGestion = json_decode($tarifs['fraisGestion']);
            // echo json_encode($tarifGestion);
            $age = $user0['age_plus_jeune'];
            foreach ($tarifGestion as $t) {
                if (eval("return (".$t[0].");")) {
                    
                    echo "<td> inférieur à ".explode('<', $t[1])[1]."h = ".$t[2]." € </td>";
                    if (eval("return (".$t[1].");")) { 
                        echo  "<td> ".$t[2]." € </td>";
                        $TotalCout += $t[2];
                    } else {
                        echo  "<td> 0.00 € </td>";
                    }
                    
                    
                    break; // on sort dès qu'on trouve le tarif
                }
            }

            
            ?> 
            
            </tr> <?php } ?>
            <tr> <th> Nbre d'interventions<br> <span style='font-weight: normal;'></span> </th> <td> <?= $nbrPresta." x ".$tarifs['parIntervention']."€ /intervention" ?> 
            <?php if ($_GET['type'] == "ENFA") {
                echo "<br> max ".$tarifs['maxParIntervention']."€ si vous habitez à Rennes ";
            } ?>
            
            </td>
            <?php 
            echo "<td>";
            if ($nbrPresta > intdiv($tarifs['maxParIntervention'], $tarifs['parIntervention']) && $_GET['type'] == "ENFA" && ($user['cp_Famille'] == "35000" || $user['cp_Famille'] == "35200" || $user['cp_Famille'] == "35700")) {
                echo $tarifs['maxParIntervention']." €";
                $TotalCout += $tarifs['maxParIntervention'];
            } else {
                echo number_format(($nbrPresta * $tarifs['parIntervention']), 2, '.', '') . " €";
                $TotalCout += $nbrPresta * $tarifs['parIntervention'];
            }
            echo "</td>";
            ?>
            </tr>
            <?php if ($_GET['type'] == "ENFA") { ?>
            <tr> <th> Transport avec les enfants </th> <td> <?= $totalKmAvecEnfan . " Km x ".$tarifs['KMenfants']." €/Km" ?> </td> <td> <?= number_format(($totalKmAvecEnfan*0.35), 2, '.', '') . " €"; ?> </td> </tr>
            <?php $TotalCout += $totalKmAvecEnfan*$tarifs['KMenfants']; } 
            $TotalCout += $tarifs['abonnement']; 
            if ($totalHeure == 0) {
                $TotalCout = 0; 
            }
            ?>
            <tr> <td class="no-borders"> </td> <th> Abonnement mensuel </th> <td> <?= $tarifs['abonnement'] ?> € </td> </tr>
            <?php if ($_SESSION["username"] == "9.99.99.99.999.999.99") { ?>
            <form method="post" action="<?= BASE_URL ?>/mon-recap?type=<?= $_GET['type'] ?>&mois=<?= $_GET['mois'] ?>&suplement=1&fam=<?=$_GET['fam']?>">
                <tr id="suplement1"> <th rowspan="2"> Frais / Remboursement </th> 
                <td> Libellé : <textarea name="libelle"><?= htmlspecialchars($releverMensuel['libelerSupl']) ?></textarea> </td> 
                <td> <input type="number" name="montant" step="0.01" value="<?= $releverMensuel['montantSupl'] ?>"> € </td> </tr>
                <tr id="suplement2"> <td colspan="2"> <input type="submit" value="Envoyer"> </td> </tr>
            </form>
            <?php } if ($releverMensuel['montantSupl'] != null) { ?>
                <tr> <td class="no-borders"> </td> <th> <?= $releverMensuel['libelerSupl'] ?> </th> <td> <?= $releverMensuel['montantSupl'] ?> € </td> </tr>
            <?php 
            $TotalCout += $releverMensuel['montantSupl'];
        }  ?>
            <tr> <td class="no-borders"> </td> <th style="color:#B62222;font-weight:bold;"> 
            <?php if ($TotalCout < 0) { ?>
                AVOIR RESTANT
            <?php } else { ?>
                TOTAL à RÉGLER 
            <?php } ?>
            </th> <th  style="color:#B62222;font-weight:bold;"> <?= number_format($TotalCout, 2, '.', '') . " €" ?> </th> </tr>
            </table>
            </td> </tr>





















            <!-- pied du recapitulatif -->

            <tr> <td colspan='5' class="no-borders">
            <table> 
                <tr> <td style='width: 12%;' class="no-borders" rowspan="8"> </td> <th colspan='4'> QUESTIONNAIRE DE SATISFACTION </th> <td style='width: 12%;' class="no-borders" rowspan="8"> </td> </tr> 
                <tr> <th style='width: 19%;'> Ponctualité </th> <th style='width: 19%;'> Régularité / Relationnel </th> <th style='width: 19%;'> Respect des horaires </th> <th style='width: 19%;'> Qualité du travail </th> </tr>
                <tr> 
                <?php if ($_SESSION["username"] != "9.99.99.99.999.999.99" && $releverMensuel['signerLe'] == Null) {?>
                <form action="<?= BASE_URL ?>/mon-recap?type=<?= $_GET['type'] ?>&mois=<?= $_GET['mois'] ?>&signer=1" method="POST" >
                    <td>
                    <select name="Ponctualite" required>
                        <option value="" disabled selected>à Choisir</option>
                        <option value="4">Très satisfait</option>
                        <option value="3">Satisfait</option>
                        <option value="2">Moyennement satisfait</option>
                        <option value="1">Pas satisfait</option>
                    </select> 
                    </td> 
                    <td>
                    <select name="ReguRela" required>
                        <option value="" disabled selected>à Choisir</option>
                        <option value="4">Très satisfait</option>
                        <option value="3">Satisfait</option>
                        <option value="2">Moyennement satisfait</option>
                        <option value="1">Pas satisfait</option>
                    </select>  
                    </td> 
                    <td>
                    <select name="RespectHo" required>
                        <option value="" disabled selected>à Choisir</option>
                        <option value="4">Très satisfait</option>
                        <option value="3">Satisfait</option>
                        <option value="2">Moyennement satisfait</option>
                        <option value="1">Pas satisfait</option>
                    </select>  
                    </td> 
                    <td>
                    <select name="QualiteTr" required>
                        <option value="" disabled selected>à Choisir</option>
                        <option value="4">Très satisfait</option>
                        <option value="3">Satisfait</option>
                        <option value="2">Moyennement satisfait</option>
                        <option value="1">Pas satisfait</option>
                    </select>  
                    </td> 
                <br>
                </tr>
                <tr> <td class="no-borders" style="text-align: left;"> Date : </td> <td class="no-borders" style="text-align: left;"> Signature :  </td> </tr>
             </table><input type="submit" value="Signer"></form>
             <?php } else {
                 $avis = [
                    null => "&nbsp;",
                    1 => "Pas satisfait",
                    2 => "Moyennement satisfait",
                    3 => "Satisfait",
                    4 => "Très satisfait"
                ];
                 echo "<td>".$avis[$releverMensuel['avisPonctualite']]."</td><td>".$avis[$releverMensuel['avisReguRela']]."</td><td>".$avis[$releverMensuel['avisRespectHo']]."</td><td>".$avis[$releverMensuel['avisQualiteTr']]."</td>";
             ?>
             </tr>
                <tr> 
                    <?php if ($releverMensuel['signerLe'] != Null) { ?>
                        <td class="no-borders" style="text-align: left;"> Date : <?php $dateSignature = new DateTime($releverMensuel['signerLe']); echo $dateSignature->format('d-m-Y'); ?> </td> 
                        <td class="no-borders" style="text-align: left;" colspan='2'> Signature : <?= "<i>".$user0['nom_Parents']."</i>" ?>  </td> 
                    <?php } else { ?>
                        <td class="no-borders" style="text-align: left;"> Date : </td> 
                        <td class="no-borders" style="text-align: left;" colspan='2'> Signature :   </td> 
                    <?php } ?>
                </tr>
             </table>
             <?php }?>
             
             </td> </tr>
    </table>
    <ul class="menu">
        <li>
            <a href="#" id="test">
                <img src="<?= BASE_URL ?>/assets/icons/save.png">
                Télécharger
            </a>
        </li>
    </ul>
</main>
    

    <!-- en tete de la feuille -->
<table id="monTableau0" style="display: none;"> 
    <tr> <th style="font-size: 16px;" colspan="2"> CETTE FEUILLE DOIT ÊTRE OBLIGATOIREMENT RETOURNÉE AU PLUS TARD LE <?= $dernierJour->format('d')-2 . " " . $moisComplets[$dernierJour->format('n')] ?> <br> À L'ASSOCIATION ACCOMPAGNÉE DE VOTRE RÈGLEMENT (SAUF EN CAS DE PRÉLÈVEMENT)</th> </tr>
    <tr> <th class="no-borders" colspan="2" style="font-size: 16px;"> <br>
    <?php if ($_GET['type'] == "ENFA") {
        echo "GARDE D'ENFANTS";
    } else if ($_GET['type'] == "MENA") {
        echo "MENAGES";
    }
    echo " / RECAPITULATIF D'HEURES : " . $moisComplets[$dernierJour->format('n')] . " " . $dernierJour->format('Y');
    
    ?>
    </th> </tr>
    <tr> 
        <th class="no-borders" style="text-align: left;"> 
        <u>NUMERO CLIENT :</u> <?php 
        if ($_GET['type'] == "ENFA") { 
            echo $user0['PGE_Famille']."<br> <u>NUMERO CAF :</u> ".$user0['numAlloc_Famille']; 
        } else if ($_GET['type'] == "MENA") {
            echo $user0['PM_Famille'];
        }?> <br>
        <u>NUMERO TEL :</u> <?= $user0['tels_Parents'] ?> <br>
        </th> 
        <th class="no-borders" style="text-align: left;">
            <?= $user0['nom_Parents']."<br>".$user0['adresse_Famille']."<br>".$user0['cp_Famille']."  ".$user0['ville_Famille'] ?>
         </th> 
    </tr>
</table>





<!-- generateur du pdf -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
async function genererPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    document.getElementById('monTableau0').style.display = '';
    <?php if ($_SESSION["username"] == "9.99.99.99.999.999.99") {?>
    document.getElementById('paiementForm').style.display = 'none';
    document.getElementById('suplement1').style.display = 'none';
    document.getElementById('suplement2').style.display = 'none';
    <?php } ?>
    async function ajouterPage(tableaux) {
        // Créer un conteneur invisible
        const wrapper = document.createElement('div');
        wrapper.style.position = 'absolute';
        wrapper.style.left = '-9999px';
        wrapper.style.top = '0';
        wrapper.style.width = '800px';
        document.body.appendChild(wrapper);

        // Ajouter les tableaux clonés
        tableaux.forEach(id => {
            const original = document.getElementById(id);
            if (original) {
                const clone = original.cloneNode(true);

                // Styles compacts
                clone.style.fontSize = '12px';
                clone.querySelectorAll('td, th').forEach(cell => {
                    cell.style.padding = '4px 10px';
                });

                wrapper.appendChild(clone);
            }
        });

        // Convertir en image
        const canvas = await html2canvas(wrapper, { scale: 1 });
        const imgData = canvas.toDataURL('image/png');

        const imgProps = doc.getImageProperties(imgData);
        const pdfWidth = doc.internal.pageSize.getWidth() - 20;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        doc.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);

        document.body.removeChild(wrapper);
    }

    // Boucle : tableau0 + tableauX
    let index = 1;
    while (document.getElementById('monTableau' + index)) {
        if (index > 1) doc.addPage(); // ajouter une page sauf pour la première
        await ajouterPage(['monTableau0', 'monTableau' + index]);
        index++;
    }

    // Sauvegarde
    doc.save('Famille <?= $user0['nom_Parents'] ?> releve heures <?php
if ($_GET['type'] == "MENA") {
    echo "MENAGE";
} else if ($_GET['type'] == "ENFA") {
    echo "G ENFANT";
} ?> <?= $moisComplets[(int)$dernierJour->format('n')] . " " . $dernierJour->format('Y') ?>.pdf');
document.getElementById('monTableau0').style.display = 'none';
<?php if ($_SESSION["username"] == "9.99.99.99.999.999.99") {?>
document.getElementById('paiementForm').style.display = '';
document.getElementById('suplement1').style.display = '';
document.getElementById('suplement2').style.display = '';
<?php } ?>
}


document.getElementById('test').addEventListener('click', function(e) {
    e.preventDefault(); // empêche le lien de recharger la page
    genererPDF(); // appelle ta fonction
    
});
</script>














<?php
$content = ob_get_clean();
include "templates/layout.php";
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const paiementDetails = document.getElementById('paiementDetails');
  const moyenPaiement = document.getElementById('moyen_paiement');

  // --- 1️⃣ Fonction qui gère l'affichage selon le moyen de paiement ---
  function afficherDetailsPaiement() {
    const value = moyenPaiement.value;
    paiementDetails.innerHTML = ''; // Réinitialise le contenu

    if (value === 'prelevement') {
      paiementDetails.innerHTML = `
        Montant : <input type="number" id="montant" name="montant" value="<?= $releverMensuel['montantPrincipal'] ?>" step="0.01" min="0"> € <br>
        <button type="button" onclick="document.getElementById('montant').value = <?= number_format($TotalCout < 0 ? 0 : $TotalCout, 2, '.', '') ?>;" style="background: #eee; color: #B62222; font-weight: bold; border: 1px solid #999; border-radius: 4px; cursor: pointer;">Utiliser le TOTAL</button>
      `;
    } else if (value === 'cheque') {
      paiementDetails.innerHTML = `
        N° : <input name="numCheque" value="<?= $releverMensuel['numChèque'] ?>"> <br>
        Montant : <input type="number" id="montant" name="montant" value="<?= $releverMensuel['montantPrincipal'] ?>" step="0.01" min="0"> € <br>
        <button type="button" onclick="document.getElementById('montant').value = <?= number_format($TotalCout < 0 ? 0 : $TotalCout, 2, '.', '') ?>;" style="background: #eee; color: #B62222; font-weight: bold; border: 1px solid #999; border-radius: 4px; cursor: pointer;">Utiliser le TOTAL</button>
      `;
    } else if (value === 'cesu') {
      paiementDetails.innerHTML = `
        Nbres : <input type="number" name="nbrCESU" value="<?= $releverMensuel['nbrCESU'] ?>"> <br>
        Montant total : <input type="number" name="montant" value="<?= $releverMensuel['montantPrincipal'] ?>" step="0.01" min="0"> € <br>
        Complément : <br>
        <input type="radio" name="complement" id="radioAucun" value="aucun" checked> Aucun
        <input type="radio" name="complement" id="radioCheque" value="cheque" <?php if ($releverMensuel['complementCESU'] == "cheque") {echo "checked";} ?>> Chèque 
        <input type="radio" name="complement" id="radioPrelevement" value="prelevement" <?php if ($releverMensuel['complementCESU'] == "prelevement") {echo "checked";} ?>> Prélèvement <br>
        
        <div id='complementPrelevement' style="display:none;">
          Montant : <input type="number" name="montantCP" value="<?= $releverMensuel['montantComplement'] ?>" step="0.01" min="0"> €
        </div>
        <div id='complementCheque' style="display:none;">
          N° : <input name="numCheque" value="<?= $releverMensuel['numChèque'] ?>"> <br>
          Montant : <input type="number" name="montantCC" value="<?= $releverMensuel['montantComplement'] ?>" step="0.01" min="0"> €
        </div>
      `;

      // --- 2️⃣ Sous-fonctions pour gérer les radios ---
      const radioAucun = document.getElementById('radioAucun');
      const radioCheque = document.getElementById('radioCheque');
      const radioPrelevement = document.getElementById('radioPrelevement');
      const divCheque = document.getElementById('complementCheque');
      const divPrelevement = document.getElementById('complementPrelevement');

      function afficherComplement() {
        divCheque.style.display = radioCheque.checked ? 'block' : 'none';
        divPrelevement.style.display = radioPrelevement.checked ? 'block' : 'none';
      }

      radioAucun.addEventListener('change', afficherComplement);
      radioCheque.addEventListener('change', afficherComplement);
      radioPrelevement.addEventListener('change', afficherComplement);
    }
    afficherComplement();
  }

  // --- 3️⃣ On attache juste l'écouteur ---
  moyenPaiement.addEventListener('change', afficherDetailsPaiement);

  // --- 4️⃣ (Optionnel) On exécute une fois au chargement ---
  afficherDetailsPaiement();
  
});


</script>














<style>
    table {
      width: 100%;             /* occupe 100% de la largeur */
      border-collapse: collapse; /* supprime les doubles bordures */
      text-align: center;      /* centre le texte dans les cellules */
    }
    th, td {
      border: 1px solid #333; /* bordures visibles */
      padding: 10px;          /* espace interne */
    }
    th {
      background-color: transparent; /* couleur d’arrière-plan pour l’en-tête */
    }
    /* Classe spéciale : pas de bordure */
    .no-borders {
      border: none; /* supprime toutes les bordures */
    }
    /* Quand l’écran est plus haut que large (portrait) */
    @media screen and (max-aspect-ratio: 1/1) {
        main {
            width: 200%; 
        max-width: none; 
        }
        header {
            width: 200%; /* Le header est deux fois plus large que la page */
            position: relative; /* nécessaire pour le placement du contenu */
}

.header-content {
    width: 50%; /* Premier 100% de la page = 50% du header de 200% */
    margin-left: 0; /* commence au bord gauche de la page */
    text-align: center; /* texte centré dans cette zone */
    box-sizing: border-box;
}

        
    }
    select {
        width: 100%;
        box-sizing: border-box;
        padding: 10px;
        font-size: 16px;
        border-radius: 8px;
        border: 1px solid #bbb;
        background-color: #fff;
        color: #333;
        cursor: pointer;
        appearance: none; /* Désactive le style natif de l’OS */
        -webkit-appearance: none;
        -moz-appearance: none;
        transition: all 0.3s ease;
    }
  </style>