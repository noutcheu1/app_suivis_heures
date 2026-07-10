# Application de Suivi des Heures

## 📌 Présentation theorique
Cette application permet de suivre, gérer et exporter les heures travaillées par des intervenants, avec un système d'authentification et de rôles (Admin, Intervenant et Famille).  
Elle est développée principalement en PHP avec un front HTML/CSS et du JS.


---




ON SAISI RAPIDE SUR LE TELEPHONE



## 👤 Rôles et permissions
- Admin  
  - Consulter les intervenants et les familles existants
  - Voir, modifier et supprimer des saisie dheures d'intervenant  
  - Examiner des recapitulatifs mensuel des heures saisies
  - Visualiser et télécharger les fiches d'heures des intervenants  
  - Télécharger des fiches d'heures (vierge) pour les intervenants 
  - Visualiser et télécharger les recapitulatif d'heures des familles
  - Generer les litiges entre les declarations intervenant et les signalements famille
  - Ajouter des exceptions de tarification pour les familles
  - Modifier les tarifs des familles

- Intervenant 
  - Saisir ses heures 
  - Visualiser et télécharger ses fiches d'heures 

- Famille 
  - Valider ou signaler les heures saisie par l'intervenant
  - Visualiser et télécharger ses recapitulatif d'heures 
  - Choisir son moyen de reglement

---

## ⚙ Fonctionnement général

1. Authentification  
   - Les sessions PHP stockent les informations utilisateur dans $_SESSION  
   - Vérification du rôle avant l’accès aux pages protégées

2. Suivi des heures  
   - Les heures sont saisies via l’interface
   - Les heures sont validé par les familles
   - Vue en feuille d'heures pour un aperçu mensuel (formats intervenant et famille)
   - Export en PDf via les bibliothèques JavaScript jspdf et html2canvas
  

---

## 📦 Dépendances
- PHP 8+
- MySQL ou MariaDB
- Composer pour les dépendances PHP

---

## 🔧 Installation


2. Installer les dépendances PHP

   ```bash
   composer install
   ```

3. Configurer la base de données

   * Importer la base de données de chaudoudoux dans MySQL
   * modifier le fichier .env avec vos identifiants et le nom de la base (ne rien changer si en local pour le login)

4. Lancer l’application

   * Via XAMPP ou MAMP (PHP + MySQL activés)

---

## 🚀 Utilisation prévue

1. Se connecter avec un compte Admin
   - type intervenant login: 9999999999e mdp: admin !!! penser a renforcer les mdp admins en production
2. Se connecter avec un compte intervenant
   - type intervenant login: numero securité social mdp: ?
3. Se connecter avec un compte famille
   - type intervenant login: numero client (PGE ou PM) mdp: ?

---

## ⏰ Tâches planifiées (cron)

Deux commandes console doivent tourner **automatiquement** (les autres commandes sont ponctuelles).

| Commande | Fréquence | Rôle |
|---|---|---|
| `app:rappels-pointage` | **toutes les 10 min** | emails « fin de pointage proche » (~15 min avant) et « oubli » (~20 min après). L'option `--fenetre` (défaut **10**) doit **égaler** la fréquence du cron. Anti-doublon intégré. |
| `app:envoyer-campagnes-vacances` | **1×/jour** (ex. 08:00) | envoie les emails des campagnes de congés dont la date d'ouverture est atteinte, puis passe la campagne en « envoyée ». |

> ⚠️ Ne **pas** planifier `app:generer-horaires-test` (données de test) ni `app:calculer-distances` (maintenance ponctuelle).

### Windows (XAMPP) — Planificateur de tâches
Windows n'a pas `cron`. Créer 2 tâches dans le **Planificateur de tâches** :

* **Programme** : `C:\xampp\php\php.exe`
* **Arguments** (rappels, déclencheur « toutes les 10 minutes ») :
  ```
  C:\xampp\htdocs\app_suivis_heures\bin\console app:rappels-pointage --env=prod --no-interaction
  ```
* **Arguments** (campagnes, déclencheur « tous les jours à 08:00 ») :
  ```
  C:\xampp\htdocs\app_suivis_heures\bin\console app:envoyer-campagnes-vacances --env=prod --no-interaction
  ```

### Linux — crontab (`crontab -e`)
```cron
*/10 * * * *  /usr/bin/php /var/www/app_suivis_heures/bin/console app:rappels-pointage --env=prod --no-interaction
0 8 * * *     /usr/bin/php /var/www/app_suivis_heures/bin/console app:envoyer-campagnes-vacances --env=prod --no-interaction
```

### Prérequis (sinon aucun email ne part)
* `MAILER_DSN` dans `.env` = un vrai serveur SMTP (ex. `smtp://user:pass@host:587`) — **pas** `null://null`.
* `DEFAULT_URI` = l'URL publique de l'app (liens et QR codes dans les emails).

---

## 📝 Notes

* Styles centralisés dans `assets/css/style.css`
* Pages modulaires avec `include/header.php` et `include/footer.php`

---

## ⚠️ Difficultés rencontrées
* Support mobile : affichage non optimal du tableau des heures sur petits écrans
* Sécurité : injection SQL potentielle dans les champs non filtrés
* Archivage : pas de solution de sauvegarde/backup des rapports
* Validation des formulaires : éviter la saisie de données invalides (ex : chevauchement de plages horaires).
* Optimisation SQL : certaines requêtes deviennent lentes avec un grand volume de données.
* Gestion des déconnexions : perte de données si un utilisateur est déconnecté avant d’enregistrer son travail.
* Support multilingue : toutes les chaînes de texte sont codées en dur en français, rendant difficile une traduction future. 


