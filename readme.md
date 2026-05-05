# Application de Suivi des Heures

## 📌 Présentation theorique
Cette application permet de suivre, gérer et exporter les heures travaillées par des intervenants, avec un système d'authentification et de rôles (Admin, Intervenant et Famille).  
Elle est développée principalement en PHP avec un front HTML/CSS et du JS.


---


## 👤 Rôles et permissions
- Admin  
  - Consulter les intervenants et les familles existants
  - Voir, modifier et supprimer des saisie dheures d'intervenant  
  - Examiner des recapitulatifs mensuel des heures saisies
  - Visualiser et télécharger les fiches d'heures des intervenants  
  - Télécharger des fiches d'heures vierge pour les intervenants 
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
   - type intervenant login: 9.99.99.99.999.999.99 mdp: admin !!! penser a renforcer les mdp admins en production
2. Se connecter avec un compte intervenant
   - type intervenant login: numero securité social mdp: ?
3. Se connecter avec un compte famille
   - type intervenant login: numero client (PGE ou PM) mdp: ?

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


