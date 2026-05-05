<?php
require_once './config.php';
ob_start();

$moisReleve = new DateTime();
$tarifs = $GLOBALS['pdo']->getTarifs($moisReleve->format('Y-m'));

$configFile = __DIR__ . '/../../configuration.json';

// Lit le contenu du fichier
$jsonData = file_get_contents($configFile);
// Convertit le JSON en tableau PHP associatif
$config = json_decode($jsonData, true);
$nbrPalierTarifGE = $config['nbrPalierTarifGE'];
$nbrPalierTarifM = $config['nbrPalierTarifM'];

if (isset($_POST) && $_POST != []) { // sauvegarder un nouveau tarif
    
    $alheureGE = '';
    if ($nbrPalierTarifGE == 0) {
        $alheureM = '[ ["$h >= 0","V"], ["$h < 0",0.00] ]';
    }
    for ($i = 1 ; $i <= $nbrPalierTarifGE; $i++) {
        
        if ($i == 1) {
            $alheureGE .= '[ ["$h >= '.$_POST['heures_max_GE_'.$i].'","V"] ';
        }
        if ($i == $nbrPalierTarifGE) {
            $alheureGE .= ', ["$h < '.$_POST['heures_max_GE_'.$i].'",'.$_POST['tarif_GE_'.$i].'] ]';
        } else {
            $alheureGE .= ', ["$h < '.$_POST['heures_max_GE_'.$i].' && $h >= '.$_POST['heures_min_GE_'.$i].'",'.$_POST['tarif_GE_'.$i].'] ';
        }

    }
    $alheureM = '';
    if ($nbrPalierTarifM == 0) {
        $alheureM = '[ ["$h >= 0","V"], ["$h < 0",0.00] ]';
    }
    for ($i = 1 ; $i <= $nbrPalierTarifM; $i++) {
        
        if ($i == 1) {
            $alheureM .= '[ ["$h >= '.$_POST['heures_max_M_'.$i].'","V"]';
        }
        if ($i == $nbrPalierTarifM) {
            $alheureM .= ', ["$h < '.$_POST['heures_max_M_'.$i].'",'.$_POST['tarif_M_'.$i].'] ]';
        } else {
            $alheureM .= ', ["$h < '.$_POST['heures_max_M_'.$i].' && $h >= '.$_POST['heures_min_M_'.$i].'",'.$_POST['tarif_M_'.$i].'] ';
        } 

    }
    $fraisGestion = '[ ["$age < 6", "$h < '.$_POST['gestion_heure_2'].'",'.$_POST['gestion_tarif_2'].'], ["$age >= 6", "$h < '.$_POST['gestion_heure_1'].'",'.$_POST['gestion_tarif_1'].'] ]';
    if ($GLOBALS['pdo']->postTarif($alheureGE, $alheureM, $fraisGestion, $_POST['parInter'], $_POST['maxParIntervention'], $_POST['KMenfants'], $_POST['abonnement'], $_POST['mois-debut'])) {
        header("Location: " . BASE_URL);
    } else {
        echo "<script>
            alert('Une erreur est survenue, veuillez réessayer.');
            window.history.back();
        </script>";
    }
}
?>

<main>
    
    <h1>Modifier les TARIFS (par défaut le tarif du mois en cours s'affiche)</H1> 

    <form action="<?= BASE_URL ?>/admin/modifTarif" method="POST">

        <table>
            <tr> <th> Mois de prise en compte </th> <td> <input type="month" name="mois-debut" required> </td> </tr>
            <?php 
            // tarif taux horaire garde d'enfants
            for ($i = $nbrPalierTarifGE ; $i >= 1; $i--) {
                echo "<tr> <th> Taux horaire GARDE D'ENFANTS <br>";
                $tarifsAlheure = json_decode($tarifs['alheureGE']);
                
                if (count($tarifsAlheure) <= $i+1 || $i == $nbrPalierTarifGE) {
                    
                    if ($i == $nbrPalierTarifGE) { 
                        preg_match_all('/\d+/', $tarifsAlheure[count($tarifsAlheure)-1][0], $h);
                        echo "Si < <input required style='width: 30%;' type='number' value=". $h[0][0]." id='heures_max_GE_$i' name='heures_max_GE_$i' oninput=updatePatterns('max_GE_$i')>H  ";
                        echo "</th> <td>";
                        echo "<input required type='number' step='0.01' value=". number_format($tarifsAlheure[count($tarifsAlheure)-1][1], 2) ." name='tarif_GE_$i'> €/heure";
                        echo "</td> </tr>";
                    } else {
                        echo "Si >= <input required style='width: 30%;' type='number' value= id='heures_min_GE_$i' name='heures_min_GE_$i' oninput=updatePatterns('min_GE_$i')>H et < <input required style='width: 30%;' type='number' value= id='heures_max_GE_$i' name='heures_max_GE_$i' oninput=updatePatterns('max_GE_$i')>H  ";
                        echo "</th> <td>";
                        echo "<input required type='number' step='0.01' name='tarif_GE_$i' value=> €/heure";
                        echo "</td> </tr>";
                    }
                    
                } else {
                    preg_match_all('/\d+/', $tarifsAlheure[$i][0], $h);
                    echo "Si >= <input required style='width: 30%;' type='number' value=". $h[0][1] ." id='heures_min_GE_$i' name='heures_min_GE_$i' oninput=updatePatterns('min_GE_$i')>H et < <input required style='width: 30%;' type='number' value=". $h[0][0]." id='heures_max_GE_$i' name='heures_max_GE_$i' oninput=updatePatterns('max_GE_$i')>H  ";
                    echo "</th> <td>";
                    echo "<input required type='number' step='0.01' name='tarif_GE_$i' value=". number_format($tarifsAlheure[$i][1], 2) ."> €/heure";
                    echo "</td> </tr>";
                }

            } ?>
            <?php 
            // tarif taux horaire menage
            for ($i = $nbrPalierTarifM ; $i >= 1; $i--) {
                echo "<tr> <th> Taux horaire MENAGE <br>";
                $tarifsAlheure = json_decode($tarifs['alheureM']);
                
                if (count($tarifsAlheure) <= $i+1 || $i == $nbrPalierTarifM) {
                    
                    if ($i == $nbrPalierTarifM) { 
                        preg_match_all('/\d+/', $tarifsAlheure[count($tarifsAlheure)-1][0], $h);
                        echo "Si < <input required style='width: 30%;' type='number' value=". $h[0][0]." id='heures_max_M_$i' name='heures_max_M_$i' oninput=updatePatterns('max_M_$i')>H  ";
                        echo "</th> <td>";
                        echo "<input required type='number' step='0.01' value=". number_format($tarifsAlheure[count($tarifsAlheure)-1][1], 2) ." name='tarif_M_$i'> €/heure";
                        echo "</td> </tr>";
                    } else {
                        echo "Si >= <input required style='width: 30%;' type='number' value= id='heures_min_M_$i' name='heures_min_M_$i' oninput=updatePatterns('min_M_$i')>H et < <input required style='width: 30%;' type='number' value= id='heures_max_M_$i' name='heures_max_M_$i' oninput=updatePatterns('max_M_$i')>H  ";
                        echo "</th> <td>";
                        echo "<input required type='number' step='0.01' name='tarif_M_$i' value=> €/heure";
                        echo "</td> </tr>";
                    }
                    
                } else {
                    preg_match_all('/\d+/', $tarifsAlheure[$i][0], $h);
                    echo "Si >= <input required style='width: 30%;' type='number' value=". $h[0][1] ." id='heures_min_M_$i' name='heures_min_M_$i' oninput=updatePatterns('min_M_$i')>H et < <input required style='width: 30%;' type='number' value=". $h[0][0]." id='heures_max_M_$i' name='heures_max_M_$i' oninput=updatePatterns('max_M_$i')>H  ";
                    echo "</th> <td>";
                    echo "<input required type='number' step='0.01' name='tarif_M_$i' value=". number_format($tarifsAlheure[$i][1], 2) ."> €/heure";
                    echo "</td> </tr>";
                }

            } 
            $tarifsGestion1 = json_decode($tarifs['fraisGestion'])[1];
            $tarifsGestion2 = json_decode($tarifs['fraisGestion'])[0];
            preg_match_all('/\d+/', $tarifsGestion1[1], $h1);
            preg_match_all('/\d+/', $tarifsGestion2[1], $h2);
            ?>
            <tr> <th> Frais de gestion GARDE D'ENFANTS <br> Si enfant >= 6 ans et < 
                <input required style='width: 30%;' type='number' value="<?= $h1[0][0] ?>" name='gestion_heure_1'> H</th> 
                <td> <input required type='number' step='0.01' name='gestion_tarif_1' value="<?= $tarifsGestion1[2] ?>"> € </td> 
            </tr>
            <tr> <th> Frais de gestion GARDE D'ENFANTS <br> Si enfant < 6 ans et < 
                <input required style='width: 30%;' type='number' value="<?= $h2[0][0] ?>" name='gestion_heure_2'> h</th> 
                <td> <input required type='number' step='0.01' name='gestion_tarif_2' value="<?= $tarifsGestion2[2] ?>"> € </td> 
            </tr>
            <tr> <th> Frais par intervention </th> <td> <input required name='parInter' type="number" step="0.01" value="<?= $tarifs['parIntervention'] ?>"> € </td> </tr>
            <tr> <th> Plafond frais d'intervention si habite a Rennes et GARDE D'ENFANTS </th> <td> <input required type="number" step="0.01" name="maxParIntervention" value="<?= $tarifs['maxParIntervention'] ?>"> € </td> </tr>
            <tr> <th> KM avec enfants </th> <td> <input required type="number" step="0.01" name="KMenfants" value="<?= $tarifs['KMenfants'] ?>"> €/Km </td> </tr>
            <tr> <th> Abonnement mensuel</th> <td> <input required type="number" step="0.01" name="abonnement" value="<?= $tarifs['abonnement'] ?>"> € </td> </tr>
            
        </table>
        <button type="submit">Valider</button>
    </form>
</main>


<style>
    table {
        width: 60%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }

    th {
        background-color: #f4f4f4;
    }

    #responsive {
        display: none;
    }
    input[type='number'] {
            width: 60%;
            box-sizing: border-box;
            padding: 10px;
        }
    
    }
    form select {
        width: 40%;
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
    #famille, #type {
    width: 100%;
}
/* Pour Chrome, Safari, Edge et Opera */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Pour Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

</style>

<script>
function updatePatterns($id) {
    const num = parseInt($id.split("_")[2]);
    const type = $id.split("_")[1];
    const maxmin = $id.split("_")[0];
    if (maxmin == "max") {
      const max = document.getElementById('heures_max_'+type+'_'+num);
      const min = document.getElementById('heures_min_'+type+'_'+(num-1));
      max.min = (parseInt(max.value)).toString();
      min.min = (parseInt(max.value)).toString();
      max.max = (parseInt(max.value)).toString();
      min.max = (parseInt(max.value)).toString();
    } else if (maxmin == "min") {
      const max = document.getElementById('heures_max_'+type+'_'+(num+1));
      const min = document.getElementById('heures_min_'+type+'_'+num);
      max.min = (parseInt(min.value)).toString();
      min.min = (parseInt(min.value)).toString();
      max.max = (parseInt(min.value)).toString();
      min.max = (parseInt(min.value)).toString();
    }
}
</script>


<?php
$content = ob_get_clean();
include "templates/layout.php";
?>
