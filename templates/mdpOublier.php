


<?php

require_once './config.php';
$title = "mot de passe oublier";

if (isset($_POST['password']) && isset($_POST['code'])) {
    if ($_POST['password'] == $_POST['password2']) {
        if (password_verify($_POST['code'], $_POST['codeAsh'])){
            if ($_SESSION['type'] == "INTER") { 
                $id = $_POST["id"];
            } else if ($_SESSION['type'] == "FAM") {
                $id = $GLOBALS['pdo']->getNumsFamille($_POST["id"])['numero_Famille'];  
            }
            
            $password =  password_hash($_POST["password"], PASSWORD_DEFAULT);
            if ($GLOBALS['pdo']->putNewMdp($id,$password)) { 
                header("Location: " . BASE_URL);
                exit;
            } 
        } else {
            $error = "le code de changement saisie n'est pas bon";
        }
    } else {
        $error = "Les mots de passe saisis ne sont pas identiques.";
    }

    
}

ob_start();
?>

<?php

if ($_SESSION['type'] == "INTER") { 

    $user = $GLOBALS['pdo']->getIntervenantNumSS($_POST["id"]);
} else if ($_SESSION['type'] == "FAM") {
    $id = $GLOBALS['pdo']->getNumsFamille($_POST["id"])['numero_Famille'];  
    $user = $GLOBALS['pdo']->getFamilleNumFam($id);
}

$nombreAleatoire = rand(100000, 999999);
$nombreAleatoireAsh =  password_hash($nombreAleatoire, PASSWORD_DEFAULT);
// echo $nombreAleatoire ;

if ($_SESSION['type'] == "INTER") { 
    $to = $user['email_Candidats']; 
} else if ($_SESSION['type'] == "FAM") {
    if (str_contains($user['emails_Parents'], '|')) {
        $emails = explode('|', $user['emails_Parents']);
        $to = $emails[0] . ", " . $emails[1];
    } else {
        $to = $user['emails_Parents'];
    }
    
}

$to = "noreplychaudoudoux@gmail.com"; // en developement empeche l'envoie des mails aux intervenant et au famille 
$subject = "Changement de mot de passe";
if ($_SESSION['type'] == "INTER") { 
    $message = "Bonjour ".$user['nom_Candidats']." ".$user['prenom_Candidats'].",\r\n\r\n";
} else if ($_SESSION['type'] == "FAM") {
    $message = "Famille ".$user['nom_Parents']." bonjour,\r\n\r\n";
}
 
$message .= "Nous avons reçu une demande de changement de votre mot de passe.\r\n\r\n";
$message .= "Pour y procéder, veuillez utiliser le code unique ci-dessous :\r\n";
$message .= "👉 Code temporaire : ".strval($nombreAleatoire)."\r\n\r\n";
$message .= "⚠️ Si vous n’êtes pas à l’origine de cette demande,\r\n";
$message .= "veuillez simplement ignorer ce mail et nous contacter pour nous en informer.\r\n\r\n";
$message .= "La Maison des Chaudoudoux \r\nEmail : mchaudoudoux@aol.com";

$headers = "From: noreplychaudoudoux@gmail.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();


mail($to, $subject, $message, $headers);
?>

<main>
    <h1>Changement de mot de passe</h1>

    <?php if (isset($error)): ?>
        <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/mdpOublier" method="POST">
        <?php if ($_SESSION['type'] == "INTER") { ?> 
            <label for="id">Numéro securité social :</label>  
            <input type="text" id="id" name="id" maxlength="21" value='<?= $_POST['id']?>' required readonly>
        <?php } else if ($_SESSION['type'] == "FAM") {  ?>
            <label for="id">Numéro client :</label>
            <input type="text" id="id" name="id" maxlength="7" value='<?= $_POST['id']?>' required readonly pattern="^[A-Z]{2,3}\d{4}$"
       title="Le format doit être 2 ou 3 lettres suivies de 4 chiffres">
        <?php } ?>

        <label for="password">Nouveau mot de passe :</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" required>
            <span id="togglePassword">
                <i class="fa fa-eye" aria-hidden="true"></i>
            </span>
        </div>
        <label for="mdp">Confirmer mot de passe :</label>
        <div class="password-wrapper">
            <input type="password" id="password2" name="password2" required>
        </div>
        <label for="id">Code temporaire (reçu par email) :</label>
        <input type="number" id="code" name="code" required>
        <input type="text" id="codeAsh" name="codeAsh" required hidden value='<?=$nombreAleatoireAsh ?>'>
        
        <button type="submit">Sauvegarder le nouveau mot de passe</button>
    </form>
</main>

<!-- Font Awesome for the eye icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding-right: 40px;
    box-sizing: border-box;
}

.password-wrapper span#togglePassword {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    cursor: pointer;
    color: #888;
}
</style>

<script>
const passwordField = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

togglePassword.addEventListener("click", function () {
    const icon = this.querySelector("i");
    const isPassword = passwordField.type === "password";

    passwordField.type = isPassword ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
});

</script>


<?php
$content = ob_get_clean();
include "templates/layout.php";
?>