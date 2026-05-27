<?php
require_once './config.php';
global $http_status_code;
// Si le code d'erreur n'est pas défini, on utilise 500 par défaut
if (!isset($_SERVER['REDIRECT_STATUS']) || !is_numeric($_SERVER['REDIRECT_STATUS'])) {
	http_response_code(500);
}


$http_status_code = [
	// ~~~ codes https officiellement connus
	100 => 'Continuer',
	101 => 'Changement de protocole',
	102 => 'En cours de traitement', // WebDAV; RFC 2518
	103 => 'Indices précoces', // RFC 8297
	200 => 'OK',
	201 => 'Créé',
	202 => 'Accepté',
	203 => 'Information non-autoritaire', // depuis HTTP/1.1
	204 => 'Aucun contenu',
	205 => 'Réinitialiser le contenu',
	206 => 'Contenu partiel', // RFC 7233
	207 => 'Multi-statut', // WebDAV; RFC 4918
	208 => 'Déjà rapporté', // WebDAV; RFC 5842
	226 => 'IM utilisé', // RFC 3229
	300 => 'Choix multiples',
	301 => 'Déplacé de façon permanente',
	302 => 'Trouvé', // Précédemment "Déplacé temporairement"
	303 => 'Voir ailleurs', // depuis HTTP/1.1
	304 => 'Non modifié', // RFC 7232
	305 => 'Utiliser le proxy', // depuis HTTP/1.1
	306 => 'Changer de proxy',
	307 => 'Redirection temporaire', // depuis HTTP/1.1
	308 => 'Redirection permanente', // RFC 7538
	400 => 'Mauvaise requête',
	401 => 'Non autorisé', // RFC 7235
	402 => 'Paiement requis',
	403 => 'Interdit',
	404 => 'Non trouvé',
	405 => 'Méthode non autorisée',
	406 => 'Non acceptable',
	407 => 'Authentification proxy requise', // RFC 7235
	408 => 'Délai d\'attente de la requête',
	409 => 'Conflit',
	410 => 'Parti',
	411 => 'Longueur requise',
	412 => 'Précondition échouée', // RFC 7232
	413 => 'Charge utile trop importante', // RFC 7231
	414 => 'URI trop long', // RFC 7231
	415 => 'Type de média non supporté', // RFC 7231
	416 => 'Plage non satisfaisante', // RFC 7233
	417 => 'Attente échouée',
	418 => 'Je suis une théière', // RFC 2324, RFC 7168
	421 => 'Requête mal dirigée', // RFC 7540
	422 => 'Entité non traitable', // WebDAV; RFC 4918
	423 => 'Verrouillé', // WebDAV; RFC 4918
	424 => 'Dépendance échouée', // WebDAV; RFC 4918
	425 => 'Trop tôt', // RFC 8470
	426 => 'Mise à niveau requise',
	428 => 'Précondition requise', // RFC 6585
	429 => 'Trop de requêtes', // RFC 6585
	431 => 'Champs d\'en-tête de requête trop volumineux', // RFC 6585
	451 => 'Indisponible pour des raisons légales', // RFC 7725
	500 => 'Erreur interne du serveur',
	501 => 'Non implémenté',
	502 => 'Mauvaise passerelle',
	503 => 'Service indisponible',
	504 => 'Délai d\'attente de la passerelle',
	505 => 'Version HTTP non supportée',
	506 => 'La variante négocie aussi', // RFC 2295
	507 => 'Stockage insuffisant', // WebDAV; RFC 4918
	508 => 'Boucle détectée', // WebDAV; RFC 5842
	510 => 'Non étendu', // RFC 2774
	511 => 'Authentification réseau requise', // RFC 6585
	// et codes https officieusement connus
	103 => 'Point de contrôle',
	218 => 'Tout va bien', // Apache Web Server
	419 => 'Page expirée', // Laravel Framework
	420 => 'Échec de méthode', // Spring Framework
	420 => 'Calmez-vous', // Twitter
	430 => 'Champs d\'en-tête de requête trop volumineux', // Shopify
	450 => 'Bloqué par le contrôle parental Windows', // Microsoft
	498 => 'Jeton invalide', // Esri
	499 => 'Jeton requis', // Esri
	509 => 'Limite de bande passante dépassée', // Apache Web Server/cPanel
	526 => 'Certificat SSL invalide', // Cloudflare et gorouter de Cloud Foundry
	529 => 'Site surchargé', // Qualys dans les SSLLabs
	530 => 'Site gelé', // Plateforme web Pantheon
	598 => 'Erreur de délai de lecture réseau', // Convention informelle
	440 => 'Délai de connexion expiré', // IIS
	449 => 'Réessayer avec', // IIS
	451 => 'Redirection', // IIS
	444 => 'Aucune réponse', // nginx
	494 => 'En-tête de requête trop volumineux', // nginx
	495 => 'Erreur de certificat SSL', // nginx
	496 => 'Certificat SSL requis', // nginx
	497 => 'Requête HTTP envoyée vers un port HTTPS', // nginx
	499 => 'Requête fermée par le client', // nginx
	520 => 'Le serveur web a retourné une erreur inconnue', // Cloudflare
	521 => 'Le serveur web est hors service', // Cloudflare
	522 => 'Délai de connexion expiré', // Cloudflare
	523 => 'L\'origine est inaccessible', // Cloudflare
	524 => 'Un délai d\'attente s\'est produit', // Cloudflare
	525 => 'Échec de la négociation SSL', // Cloudflare
	526 => 'Certificat SSL invalide', // Cloudflare
	527 => 'Erreur Railgun', // Cloudflare	
];


$error_code = http_response_code();
$title = "code ". $error_code ." - ".$http_status_code[$error_code];

ob_start();
?>



<main>
<h1>Erreur <?= $error_code ?> - <?= $http_status_code[$error_code] ?></h1>

<ul class="menu">
        <li>
            <a href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/assets/icons/home.png" alt="Accueil">
                Acceuil
            </a>
        </li>
        <li>
            <a href="javascript:history.back()" onclick="history.back();">
                <img src="<?= BASE_URL ?>/assets/icons/go_back.png" alt="Retour En Arrière">
                Retour
            </a>
        </li>
    </ul>
</main>


<?php
$content = ob_get_clean();
include "templates/layout.php";
?>