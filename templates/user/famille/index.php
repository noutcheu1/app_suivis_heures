<?php
require_once './config.php';


ob_start();
$user = $GLOBALS['pdo']->getFamilleNumFam($_SESSION["username"]);
?>
<main>
    <h1>Famille <?php 
    echo htmlspecialchars($user['nom_Parents']); ?> bonjour</h1>
    <p>Bienvenue sur votre tableau de bord.</p>
    
    <ul class="menu">
        <li>
            <a href="<?= BASE_URL ?>/valider-heures?mois=0">
                <img src="<?= BASE_URL ?>/assets/icons/calendar.png">
                Valider des heures
            </a>
        </li>
        <?php if ($user['PGE_Famille'] !== "" && $user['PGE_Famille'] !== "PGE_ _ _ _") { ?>
        <li>
            <a href="<?= BASE_URL ?>/mon-recap?type=ENFA&mois=0">
                <img src="<?= BASE_URL ?>/assets/icons/folderENFA.png">
                Mes recapitulatif d'heures Garde d'enfant
            </a>
        </li>
        <?php } if ($user['PM_Famille'] !== "" && $user['PM_Famille'] !== "PM_ _ _ _") { ?> 
        <li>
            <a href="<?= BASE_URL ?>/mon-recap?type=MENA&mois=0">
                <img src="<?= BASE_URL ?>/assets/icons/folderMENA.png">
                Mes recapitulatif d'heures Menage
            </a>
        </li>
        <?php } ?>
    </ul>

</main>

<?php
$content = ob_get_clean();
include "templates/layout.php";
?>