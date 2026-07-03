<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Fuseau horaire de l'application (France) → horodatages cohérents partout
// (audit, expiration des codes, affichage) au lieu d'UTC.
date_default_timezone_set('Europe/Paris');

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
