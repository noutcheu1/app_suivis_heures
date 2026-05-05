<?php
require_once './config.php';

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

$date = new DateTime("now");
$date->modify($_GET['mois'] . " month");

if (isset($_POST) && isset($_GET['new'])) {
    echo json_encode($_POST);
    if ($_POST['montant'] == "" || $_POST['montant'] == 0 ) {
        $_POST['libelle'] = null;
        $_POST['montant'] = null;
    }
    $numFam = $_POST['famille']; 
    $releverMensuel = $GLOBALS['pdo']->getReleverMensuelFam($_POST['type'], $date->format('Y-m'), $numFam); // cree si existe pas

    // Si le relevé n'existe pas et que le mois est après juillet 2025, on le crée
    if (!$releverMensuel) {
        $GLOBALS['pdo']->postReleverMensuelFam($_POST['type'], $date->format('Y-m'), $numFam);
    }

    if ($GLOBALS['pdo']->putSuplement($date->format('Y-m'),$_POST['type'],$numFam,$_POST['libelle'],$_POST['montant'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        echo "<script>
            alert('Une erreur est survenue, veuillez réessayer.');
            window.history.back();
        </script>";
    }
}

$exceptionsEnfa = $GLOBALS['pdo']->getTouteExceptionTarif($date, "ENFA");
// echo json_encode($exceptionsEnfa);
$exceptionsMena = $GLOBALS['pdo']->getTouteExceptionTarif($date, "MENA");
// echo json_encode($exceptionsMena);
$familles = $GLOBALS['pdo']->getTousLesFamilles();
// echo json_encode($familles);
ob_start();
?>



<main>
    <h1> Liste des exceptions de tarification de <?= $moisComplets[date('n', strtotime($_GET['mois'].' month'))] . " " . date('Y', strtotime($_GET['mois'].' month')) ?> </h1>
    
    
    <form action="<?= BASE_URL ?>/admin/exceptionTarif?mois=<?= $_GET['mois'] ?>&new=1" method="POST">

        <table>
            <tr>
                <th>Famille</th>
                <th>Type</th>
                <th>Libellé</th>
                <th>Montant</th>
                <th>Valider</th>
            </tr>
            <tr>
                <td>
                <select id="famille" name="famille" style="width: 100%;">
    <option value="" disabled selected>Choisir une famille</option>
    <?php foreach ($familles as $famille): ?>
        <option value='<?= $famille['numero_Famille'] ?>'>
            <?= $famille['nom_Parents'] ?>
        </option>
    <?php endforeach; ?>
</select>
                </td>
                <td>
                <select id="type" name="type" style="width: 100%;">
    <option value="">-- Choisir un type --</option>
    <option value="MENA">MENA</option>
    <option value="ENFA">ENFA</option>
</select>
                </td>
                <td>
                  <textarea name="libelle" style="width: 80%;"></textarea>
                </td>
                <td>
                  <input type="number" name="montant" step="0.01"> €
                </td>
                <td>
                  <button type="submit">Ajouter</button>
                </td>
            </tr>
        </table>
        
    </form>

    <ul class="menu">
    
<li>
    <a href="<?= BASE_URL ?>/admin/exceptionTarif?mois=<?= $_GET['mois']-1 ?>">
        <img src="<?= BASE_URL ?>/assets/icons/flecheGaucheR.png">
        Mois precedente
    </a>
</li>
<?php if ($_GET['mois'] < 0) { ?>
    <li> 
        <a href="<?= BASE_URL ?>/admin/exceptionTarif?mois=<?= $_GET['mois']+1 ?>">
            <img src="<?= BASE_URL ?>/assets/icons/flecheDroiteR.png">
            Mois suivante
        </a>
    </li>
<?php } ?>
        
    </ul>
    <h1> GARDE D'ENFANTS </h2>
    <table id="tableEnfa">
        <thead>
        <tr> 
            <th> Nom de famille </th> 
            <th> PGE </th> 
            <th> PM </th> 
            <th> Libellé </th> 
            <th> Montant </th> 
            <th> Modifier </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($exceptionsEnfa as $exceptionEnfa) { ?>
        <tr>
            <td> <a href='<?=BASE_URL?>/admin/familles.php?id=<?= $exceptionEnfa['numFam'] ?>'><?= $exceptionEnfa['nom_Parents']?></a></td>
            <td><?= htmlspecialchars($exceptionEnfa['PGE_Famille']) ?></td>
            <td><?= htmlspecialchars($exceptionEnfa['PM_Famille']) ?></td>
            <td><?= nl2br(htmlspecialchars($exceptionEnfa['libelerSupl'])) ?></td>
            <td><?= htmlspecialchars($exceptionEnfa['montantSupl']) ?></td>
            <td> 
            <a href="<?= BASE_URL ?>/mon-recap?type=ENFA&mois=<?= $_GET['mois']?>&fam=<?= $exceptionEnfa['numFam'] ?>" class="btn-vert">Modifier</a></td>
            
        </tr>
        <?php } ?>
        </tbody>
    </table>
    
    <h1> MENAGES </h2>

    <table id="tableMena">
        <thead>
        <tr> 
            <th> Nom de famille </th> 
            <th> PGE </th> 
            <th> PM </th> 
            <th> Libellé </th> 
            <th> Montant </th> 
            <th> Modifier </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($exceptionsMena as $exceptionMENA) { ?>
        <tr>
            <td><a href='<?=BASE_URL?>/admin/familles.php?id=<?= $exceptionMENA['numFam'] ?>'><?= $exceptionMENA['nom_Parents']?></a></td>
            <td><?= htmlspecialchars($exceptionMENA['PGE_Famille']) ?></td>
            <td><?= htmlspecialchars($exceptionMENA['PM_Famille']) ?></td>
            <td><?= nl2br(htmlspecialchars($exceptionMENA['libelerSupl'])) ?></td>
            <td><?= htmlspecialchars($exceptionMENA['montantSupl']) ?></td>
            <td> 
            <a href="<?= BASE_URL ?>/mon-recap?type=MENA&mois=<?= $_GET['mois']?>&fam=<?= $exceptionMENA['numFam'] ?>" class="btn-vert">Modifier</a></td>
            
        </tr>
        <?php } ?>
        </tbody>
    </table>
</main>

<?php
$content = ob_get_clean();
include "templates/layout.php";
?>

<style>
  th {
    cursor: pointer;
    position: relative;
    user-select: none;
  }

  th .arrow {
    font-size: 0.8em;
    margin-left: 5px;
  }
</style>

<script>
const tables = ['tableEnfa', 'tableMena'];

tables.forEach(tableId => {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const thx = table.querySelectorAll('th');
    const trxb = tbody.querySelectorAll('tr');

    thx.forEach(th => {
        th.addEventListener('click', function() {
            const index = Array.from(thx).indexOf(th);
            this.asc = !this.asc;

            let sortedRows;
            if (index === 1) {
                // tri date FR si besoin
                const parseDateFR = (str) => {
                    const parts = str.split('/');
                    if (parts.length !== 3) return null;
                    const [day, month, year] = parts.map(Number);
                    return new Date(year, month - 1, day);
                };
                sortedRows = Array.from(trxb).sort((row1, row2) => {
                    const v1 = row1.children[index].textContent.trim();
                    const v2 = row2.children[index].textContent.trim();
                    const d1 = parseDateFR(v1);
                    const d2 = parseDateFR(v2);
                    if (d1 && d2 && !isNaN(d1) && !isNaN(d2)) {
                        return this.asc ? d1 - d2 : d2 - d1;
                    }
                    return 0;
                });
            } else {
                sortedRows = Array.from(trxb).sort((row1, row2) => {
                    const v1 = row1.children[index].textContent;
                    const v2 = row2.children[index].textContent;
                    return v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? 
                        (this.asc ? v1 - v2 : v2 - v1) : 
                        (this.asc ? v1.localeCompare(v2) : v2.localeCompare(v1));
                });
            }

            sortedRows.forEach(tr => tbody.appendChild(tr));

            // supprimer toutes les flèches
            thx.forEach(th => {
                const arrow = th.querySelector('.arrow');
                if (arrow) arrow.remove();
            });

            // ajouter flèche
            const arrow = document.createElement('span');
            arrow.classList.add('arrow');
            arrow.textContent = this.asc ? '▲' : '▼';
            th.appendChild(arrow);
        });
    });
});

</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#famille').select2({
        placeholder: "Choisir une famille",
        allowClear: false,            // Pas de bouton "×"
        width: '100%'                // Prend toute la largeur
    });
});
</script>

<style> 
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