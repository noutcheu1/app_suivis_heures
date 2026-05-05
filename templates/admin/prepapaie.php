<?php
require_once './config.php';
$date = new DateTime("now");
$date->modify($_GET['mois'] . " month");
$Intervenants = $GLOBALS['pdo']->preparationPaie($date);

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
    <h1>Préparation de la paie de <?= $moisComplets[date('n', strtotime($_GET['mois'].' month'))] . " " . date('Y', strtotime($_GET['mois'].' month')) ?></h1>
    
    <ul class="menu">
    <li>
    <a href="#" id="exportLink" class="disabled" onclick="return false;">
      <img src="<?= BASE_URL ?>/assets/icons/save.png">
      Exporter en CSV
    </a>

    </li>
        <?php

        if (true) {
        ?>
        <li>
            <a href="<?= BASE_URL ?>/admin/prepapaie?mois=<?= $_GET['mois']-1 ?>">
                <img src="<?= BASE_URL ?>/assets/icons/flecheGaucheR.png">
                Mois precedente
            </a>
        </li>
        <?php } if ($_GET['mois'] < 0) { ?>
            <li> 
                <a href="<?= BASE_URL ?>/admin/prepapaie?mois=<?= $_GET['mois']+1 ?>">
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
            <th> Prestation </th> 
            <th> Nbre Hres déclaré par INTERVENANTE </th> 
            <th> nbre de trajets </th> 
            <th> nbre de km avec enfants </th> 
            <th> nbre de km sans enfants </th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $x = 0;
        foreach($Intervenants as $Intervenant) { ?>
        <tr>
            <td><?= "<a href='".BASE_URL."/admin/intervenants.php?id=". $Intervenant['numInter'] ."' >".htmlspecialchars($Intervenant['nomComplet'])."</a>" ?></td>
            <td> <?php 
            if ($Intervenant['typePresta'] == "MENA") {
              echo "MENAGES";
            } else if ($Intervenant['typePresta'] == "ENFA") {
              echo "GARDE D'ENFANTS";
            }
            htmlspecialchars($Intervenant['typePresta']) 
            
            ?> </td>

            <td><?php 
            list($hours, $minutes) = explode(":", $Intervenant['dureeTotale'] );
            $decimal = $hours + ($minutes / 60);
            echo str_replace('.', ',', $decimal);
            
            ?></td>
            <td><?= htmlspecialchars($Intervenant['nbrTrajet']) ?></td>
            <td><?= htmlspecialchars($Intervenant['kmAvecEnfant']) ?></td>
            
            <?php 
            if ($Intervenant['famhorsrennes'] != null) {
              echo "<td id='traget$x'> Calcul...";
              if (strpos($Intervenant['famhorsrennes'], '|') !== false) {
                $y = 0;
                $familles = explode('|', $Intervenant['famhorsrennes']);
                // echo $famille. "  " . $x . $y . "<br>";
                
                foreach($familles as $famille) {
                  
                  $adrs = $GLOBALS['pdo']->getAdrFam($Intervenant['typePresta'],$Intervenant['numInter'],$famille,date('Y-m', strtotime($_GET['mois'].' month'))."%");
                  // echo json_encode($adrs);
                  if ($y == 0) {
                    echo "<input value='".$adrs['adresse_Candidats'].", ".$adrs['cp_Candidats'].", ".$adrs['ville_Candidats']."' id=adrInter".$x." hidden>";
                  }
                  echo "<input value='".$adrs['adresse_Famille'].", ".$adrs['cp_Famille'].", ".$adrs['ville_Famille']."' id=adrFam".$x.$y." hidden>";
                  $y++;
                }
              } else {
                $adrs = $GLOBALS['pdo']->getAdrFam($Intervenant['typePresta'],$Intervenant['numInter'],$Intervenant['famhorsrennes'],date('Y-m', strtotime($_GET['mois'].' month'))."%");
                
                echo "<input value='".$adrs['adresse_Candidats'].", ".$adrs['cp_Candidats'].", ".$adrs['ville_Candidats']."' id=adrInter".$x." hidden>";
                echo "<input value='".$adrs['adresse_Famille'].", ".$adrs['cp_Famille'].", ".$adrs['ville_Famille']."' id=adrFam".$x."0 hidden>";
              }
              $x++;
            } else {
              echo "<td> 0,00";
            }
            ?>
            </td>
            
        </tr>
        <?php  } ?>
        <input id='nbrKmAcalculer' value=<?=$x;?> hidden>
        </tbody>
    </table> 
</main>

<?php
$content = ob_get_clean();
include "templates/layout.php";
?>

<script>
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++)
            row.push('"' + cols[j].innerText + '"');
        csv.push(row.join(","));        
    }

    // Création d'un fichier téléchargeable
    var csvFile = new Blob(["\uFEFF" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

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
const compare = (ids, asc) => (row1, row2) => {
  const tdValue = (row, ids) => row.children[ids].textContent;
  const tri = (v1, v2) => v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2);
  return tri(tdValue(asc ? row1 : row2, ids), tdValue(asc ? row2 : row1, ids));
};

const tbody = document.querySelector('tbody');
const thx = document.querySelectorAll('th');
const trxb = tbody.querySelectorAll('tr');

thx.forEach(th => {
  th.addEventListener('click', function() {
    // Déterminer l'index de la colonne
    const index = Array.from(thx).indexOf(th);
    
    // Alterner l'ordre de tri
    this.asc = !this.asc;
    
    let sortedRows;
    
      // Autres colonnes : tri classique
      sortedRows = Array.from(trxb).sort(compare(index, this.asc));
    
    // Réinsérer les lignes triées
    sortedRows.forEach(tr => tbody.appendChild(tr));
    
    // Supprimer les flèches de tous les th
    thx.forEach(th => {
      let arrow = th.querySelector('.arrow');
      if (arrow) arrow.remove();
    });
    
    // Ajouter la flèche à la colonne triée
    const arrow = document.createElement('span');
    arrow.classList.add('arrow');
    arrow.textContent = this.asc ? '▲' : '▼'; // ▲ = croissant, ▼ = décroissant
    th.appendChild(arrow);
  });
});
</script>

<script type="module">

import { getDistance } from "../geo-api.js"; // chemin correct

(async () => {
    
    for (let i = 0; i < document.getElementById('nbrKmAcalculer').value; i++) {
        const adresseCan = document.getElementById(`adrInter${i}`).value;
        const tragetTd = document.getElementById(`traget${i}`);
        // tragetTd.textContent = tragetTd.textContent + " Calcul..."; 
        let y = 0;
        let total = 0;
        while (document.getElementById(`adrFam${i}${y}`)) { 
          
          const adresseFam = document.getElementById(`adrFam${i}${y}`).value;
          let distance = await getDistance(adresseCan, adresseFam);
          distance = parseFloat(distance.replace(",", "."));
          if (distance > 15) {
            distance = 15;
          }
          
          total = total + distance; 
          y++;
        }
        
        tragetTd.textContent = total.toFixed(2).replace('.', ',') ;
        

    }
     // ✅ Une fois la boucle terminée, activer le bouton
     const exportBtn = document.getElementById("exportLink");
    exportBtn.classList.remove("disabled"); // enlève le style désactivé
    exportBtn.onclick = function() { 
        exportTableToCSV('export.csv'); 
        return false; 
    };
    
})();



</script>



<style> 
.disabled {
    pointer-events: none; /* bloque le clic */
    opacity: 0.5;        /* rend le lien "gris" */
    cursor: not-allowed;  /* curseur interdit */
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