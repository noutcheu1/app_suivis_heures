<?php
require_once './config.php';

if (isset($_GET['valid'])) {
  if ($GLOBALS['pdo']->putValiderHoraire($_GET['valid'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
  } else {
    echo "<script>
        alert('Une erreur est survenue, veuillez réessayer.');
        window.history.back();
    </script>";
  }
} else if (isset($_POST['remarque'])) {
  if ($GLOBALS['pdo']->putRemarqueHoraire($_POST['intervenant_id'],$_POST['remarque'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
  } else {
    echo "<script>
        alert('Une erreur est survenue, veuillez réessayer.');
        window.history.back();
    </script>";
  }
}
$user = $GLOBALS['pdo']->getFamilleNumFam($_SESSION["username"]);

$date = new DateTime("now");
$date->modify($_GET['mois'] . " month");
$Intervenants = $GLOBALS['pdo']->getPresationsAvalider($date, $_SESSION['username']);
// echo json_encode($Intervenants);
ob_start();

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
?>



<main>
    <h1>Famille <?= htmlspecialchars($user['nom_Parents']); ?>, Liste des interventions à valider de <?= $moisComplets[date('n', strtotime($_GET['mois'].' month'))] . " " . date('Y', strtotime($_GET['mois'].' month')) ?></h1>
    
    <ul class="menu">
        <li>
            <a href="<?= BASE_URL ?>/valider-heures?mois=<?= $_GET['mois']-1 ?>">
                <img src="<?= BASE_URL ?>/assets/icons/flecheGaucheR.png">
                Mois precedente
            </a>
        </li>
        <?php if ($_GET['mois'] < 0) { ?>
            <li> 
                <a href="<?= BASE_URL ?>/valider-heures?mois=<?= $_GET['mois']+1 ?>">
                    <img src="<?= BASE_URL ?>/assets/icons/flecheDroiteR.png">
                    Mois suivante
                </a>
            </li>
        <?php } ?>
    </ul>
    <table id="table">
        <thead>
        <tr> 
            <th> Intervenante </th> 
            <th> Type </th> 
            <th> Date </th> 
            <th> Heures de debut </th> 
            <th> Heures de fin </th> 
            <?php if (in_array('ENFA', array_column($Intervenants, 'typePresta'))) { ?>
            <th> nbre de km avec enfants </th> 
            <?php } ?>
            <th> Valider </th> 
            <th> Signaler une erreur </th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $x = 0;
        foreach($Intervenants as $Intervenant) { ?>
        <tr>
            <td><?= htmlspecialchars($Intervenant['nomComplet']) ?></td>
            <td> <?php 
            if ($Intervenant['typePresta'] == "MENA") {
              echo "MENAGES";
            } else if ($Intervenant['typePresta'] == "ENFA") {
              echo "GARDE D'ENFANTS";
            }
            htmlspecialchars($Intervenant['typePresta'])
            ?> </td>
            <td><?=
              (new DateTime($Intervenant['datePresta']))->format('d/m/Y');
            ?></td>
            <td><?= (new DateTime($Intervenant['heureDebutPresta']))->format('H:i') ?></td>
            <td><?= (new DateTime($Intervenant['heureFinPresta']))->format('H:i') ?></td>
            <?php if (in_array('ENFA', array_column($Intervenants, 'typePresta'))) { ?>
              <?php if ($Intervenant['typePresta'] == "MENA") { 
                echo "<td style='background-color: #a0a0a0;'>";
              } else {
                echo "<td>";
              }
                ?>
            
            <?php
            if ($Intervenant['kmAvecEnfant'] == null && $Intervenant['typePresta'] == "ENFA") {
              echo "0.0 km";
            } else if ($Intervenant['typePresta'] == "ENFA") {
              echo $Intervenant['kmAvecEnfant']." km";
            }
            
            ?>
             </td> <?php } ?>
             <td> <a href="<?= BASE_URL ?>/valider-heures?valid=<?=$Intervenant['id']?>" class="btn-vert">Valider </a> </td>
             <td> 
                <input type="checkbox" id="toggleCheck<?=$Intervenant['id']?>" onclick="handleToggle(<?=$Intervenant['id']?>)"/> 
                <span id="zone<?=$Intervenant['id']?>" hidden> <br>
                    <form id="form<?=$Intervenant['id']?>" action="<?= BASE_URL ?>/valider-heures" method="POST">
                    <textarea name="remarque" id="extraArea<?=$Intervenant['id']?>" placeholder="Écrivez votre remarque..."></textarea>
                    <input type="hidden" name="intervenant_id" value="<?=$Intervenant['id']?>">
                    
                    <a href="#" class="btn-rouge" onclick="document.getElementById('form<?=$Intervenant['id']?>').submit(); return false;">Envoyer</a>
                  </form>
                </span>
              </td>
        </tr>
        <?php } ?>
        <input id='nbrKmAcalculer' value='<?= $x ?>' hidden>
        </tbody>
    </table> 
    <?php 
    $erreurs = $GLOBALS['pdo']->getPresationsAvalider($date, $_SESSION['username'], false);
    // echo json_encode($erreurs);
    if (json_encode($erreurs) != "[]") {
      echo "<h1> Interventions signalées (Vos remarques sont en cours d'étude)</h1>";
    ?>
    <table id="table2">
        <thead>
        <tr> 
            <th> Intervenante </th> 
            <th> Type </th> 
            <th> Date </th> 
            <th> Heures de debut </th> 
            <th> Heures de fin </th> 
            <?php if (in_array('ENFA', array_column($erreurs, 'typePresta'))) { ?>
            <th> nbre de km avec enfants </th> 
            <?php } ?>
            <th> Votre remarque </th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $x = 0;
        foreach($erreurs as $erreur) { ?>
        <tr>
            <td><?= htmlspecialchars($erreur['nomComplet']) ?></td>
            <td> <?php 
            if ($erreur['typePresta'] == "MENA") {
              echo "MENAGES";
            } else if ($erreur['typePresta'] == "ENFA") {
              echo "GARDE D'ENFANTS";
            }
            htmlspecialchars($erreur['typePresta'])
            ?> </td>
            <td><?=
              (new DateTime($erreur['datePresta']))->format('d/m/Y');
            ?></td>
            <td><?= (new DateTime($erreur['heureDebutPresta']))->format('H:i') ?></td>
            <td><?= (new DateTime($erreur['heureFinPresta']))->format('H:i') ?></td>
            <?php if (in_array('ENFA', array_column($erreurs, 'typePresta'))) { ?>
              <?php if ($erreur['typePresta'] == "MENA") { 
                echo "<td style='background-color: #a0a0a0;'>";
              } else {
                echo "<td>";
              }
                ?>
            
            <?php
            if ($erreur['kmAvecEnfant'] == null && $erreur['typePresta'] == "ENFA") {
              echo "0 km";
            } else if ($erreur['typePresta'] == "ENFA") {
              echo $erreur['kmAvecEnfant']." km";
            }
            
            ?>
             </td> <?php } ?>
             <td> <?= $erreur['remarque'] ?> </td>
             
             
        </tr>
        <?php } ?>
        <input id='nbrKmAcalculer' value='<?= $x ?>' hidden>
        </tbody>
    </table> 
    <?php } ?>
    
</main>

<?php
$content = ob_get_clean();
include "templates/layout.php";
?>


<script>

function handleToggle(id) {
    const checkbox = document.getElementById('toggleCheck' + id);
    const zone = document.getElementById('zone' + id);

    if (checkbox.checked) {
        zone.hidden = false; // Affiche le span
    } else {
        zone.hidden = true;  // Cache le span
    }
}
</script>

<style> 
input[type='checkbox'] {
    width: 25px;
    height: 25px;
    box-sizing: border-box;
    border-radius: 5px;
    border: 2px solid #bbb;
    background-color: #fff;
    cursor: pointer;
    appearance: none;  /* Désactive le style natif */
    -webkit-appearance: none;
    -moz-appearance: none;
    transition: all 0.2s ease;
    position: relative;
}

/* État coché */
input[type='checkbox']:checked {
    background-color: #0d47a1; /* Couleur quand coché */
    border-color: #0d47a1;
}

/* Croix "X" blanche */
input[type='checkbox']:checked::before,
input[type='checkbox']:checked::after {
    content: '';
    position: absolute;
    left: 11px;
    top: 5px;
    width: 2px;
    height: 14px;
    background: white;
    border-radius: 1px;
}

input[type='checkbox']:checked::before {
    transform: rotate(45deg);
}

input[type='checkbox']:checked::after {
    transform: rotate(-45deg);
}


.btn-vert {
  display: inline-block;
  padding: 4px 10px;
  margin-left: 6px;
  font-size: 0.9rem;
  font-weight: bold;
  color: #fff;
  background-color: #4CAF50; /* vert doux */
  border-radius: 6px;
  text-decoration: none;
  transition: background-color 0.2s ease, transform 0.15s ease;
}

.btn-vert:hover {
  background-color: #43a047; /* vert un peu plus foncé */
  transform: scale(1.05);
}

.btn-vert:active {
  background-color: #388e3c;
  transform: scale(0.97);
}

.btn-rouge {
  display: inline-block;
  padding: 4px 10px;
  margin-left: 6px;
  font-size: 0.9rem;
  font-weight: bold;
  color: #fff;
  background-color: #f44336; /* rouge doux */
  border-radius: 6px;
  text-decoration: none;
  transition: background-color 0.2s ease, transform 0.15s ease;
}

.btn-rouge:hover {
  background-color: #e53935; /* rouge un peu plus foncé */
  transform: scale(1.05);
}

.btn-rouge:active {
  background-color: #c62828; /* rouge foncé */
  transform: scale(0.97);
}

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
  background: linear-gradient(135deg, #1e3d58, #3b6b9a);
  color: #fff; /* texte blanc pour contraster avec le dégradé */
  padding: 8px; /* un peu d’espace */
  border: 1px solid #ccc; /* optionnel pour délimiter les cellules */
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
}

</style>