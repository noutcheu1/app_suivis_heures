# Cahier des charges Application Suivi Heures
**Association Chaudoudoux**
Version 1.0 Mai 2026

---

## Table des matières

1. [Contexte et objectifs](#1-contexte-et-objectifs)
2. [Périmètre fonctionnel](#2-périmètre-fonctionnel)
3. [Acteurs et rôles](#3-acteurs-et-rôles)
4. [Fonctionnalités détaillées](#4-fonctionnalités-détaillées)
5. [Modèle de données](#5-modèle-de-données)
6. [Règles métier](#6-règles-métier)
7. [Architecture technique](#7-architecture-technique)
8. [Configuration](#8-configuration)
9. [Points à améliorer et roadmap](#9-points-à-améliorer-et-roadmap)

---

## 1. Contexte et objectifs

### 1.1 Présentation

L'association **Chaudoudoux** propose des services à domicile : garde d'enfants (GE) et ménage (M). Elle met en relation des **familles** bénéficiaires avec des **intervenants** salariés.

L'application **Suivi Heures** est un outil web interne qui permet :
- aux intervenants de déclarer leurs heures travaillées
- aux familles de valider ces heures et de signer leur récapitulatif mensuel
- à l'administration de superviser l'ensemble, gérer les tarifs et préparer la paie

### 1.2 Contexte base de données

L'application s'intègre dans la base de données existante `bdchaudoudoux`, partagée avec d'autres applications de l'association. Les tables existantes (`famille`, `intervenants`, `candidats`, etc.) **ne doivent pas être modifiées**. Les nouvelles tables sont préfixées ou nommées de façon explicite pour éviter tout conflit.

### 1.3 Objectifs principaux

- Remplacer la saisie papier des relevés d'heures
- Automatiser le calcul des récapitulatifs et estimations de facture
- Conserver une traçabilité complète des heures validées et signalées
- Faciliter la préparation de la paie mensuelle

---

## 2. Périmètre fonctionnel

| Module | Description | Rôles concernés |
|---|---|---|
| Authentification | Connexion / déconnexion / mot de passe oublié | Tous |
| Saisie des heures | Déclaration des heures travaillées par jour | Intervenant |
| Validation des heures | Validation ou signalement par la famille | Famille |
| Relevé mensuel intervenant | Récapitulatif signable et téléchargeable | Intervenant, Admin |
| Récapitulatif mensuel famille | Estimation facture, paiement, avis, signature | Famille, Admin |
| Gestion intervenants | Profil, heures, relevés, préparation paie | Admin |
| Gestion familles | Profil, récapitulatifs, exceptions de facturation | Admin |
| Gestion tarifs | Modification des grilles tarifaires horodatées | Admin |
| Signalements | Visualisation et résolution des heures contestées | Admin |
| Export | CSV préparation paie, PDF relevés et récapitulatifs | Admin, Intervenant, Famille |

---

## 3. Acteurs et rôles

### 3.1 Intervenant

Salarié de l'association. Se connecte avec son **numéro de sécurité sociale** (15 chiffres, stocké dans `candidats.numSS_Candidats`).

**Droits :**
- Saisir ses heures dans la limite du délai configuré (`nbrJourSaisie`)
- Voir la liste des familles de son planning
- Consulter et signer ses relevés mensuels (mois actuel et précédent)
- Ajouter des heures travaillées hors structure
- Télécharger ses relevés en PDF

### 3.2 Famille

Client bénéficiaire. Se connecte avec son **code client PM** (`famille.PM_Famille`) ou son **code PGE** (`famille.PGE_Famille`).

**Droits :**
- Valider ou signaler les heures saisies par l'intervenant
- Consulter ses récapitulatifs mensuels
- Voir l'estimation de sa facture
- Choisir son mode de règlement
- Donner un avis sur les interventions et signer le récapitulatif
- Télécharger ses récapitulatifs en PDF

### 3.3 Administrateur

Personnel de l'association. Se connecte avec un **identifiant libre** (ex : `9.99.99.99.999.999.99`).

**Droits :** accès complet à toutes les fonctionnalités ci-dessous.

---

## 4. Fonctionnalités détaillées

### 4.1 Authentification

#### Connexion
- Formulaire `username` + `mot de passe`
- Le `username` est interprété selon le rôle stocké dans `users_suivi`
- Mot de passe hashé en **bcrypt** (`$2y$10$…`)

#### Mot de passe oublié
- L'utilisateur saisit son `username`
- Un **token temporaire** (64 caractères) est généré et stocké dans `users_suivi.tokenReinit`
- Ce token expire après **1 heure** (`users_suivi.tokenExpire`)
- Un email est envoyé à l'adresse correspondante (intervenant ou famille) via SMTP Gmail
- ⚠️ **Note :** l'envoi d'email est actuellement désactivé côté familles/intervenants (ligne 62 de `mdpOublier.php`)

#### Compte admin par défaut
```
username : 9.99.99.99.999.999.99
mot de passe : admin
```

---

### 4.2 Module Intervenant

#### Saisie des heures
- L'intervenant sélectionne une famille parmi celles de son planning (`proposer`)
- Il saisit : date, heure début, heure fin, type de prestation (GE ou M), kilomètres avec enfants
- **Contrainte :** saisie impossible au-delà de `nbrJourSaisie` jours après la date de la prestation
- Une confirmation supplémentaire est demandée avant validation
- Les heures saisies sont enregistrées dans `horaireinter`

#### Heures hors structure
- L'intervenant peut déclarer des heures effectuées en dehors du cadre habituel
- Ces heures sont stockées dans `relevemensuelinter.heureDehors`

#### Relevé mensuel
- Vue mensuelle regroupant toutes les heures saisies
- L'intervenant peut **signer** le relevé du mois actuel et du mois précédent
- Signature enregistrée dans `relevemensuelinter.signer` + `signerLe`
- Téléchargement en **PDF**

---

### 4.3 Module Famille

#### Validation des heures
- La famille consulte la liste des heures saisies par l'intervenant, regroupées par jour
- Pour chaque ligne, elle peut :
  - **Valider** → `horaireinter.validerFam = 1`, `validerLe = now()`
  - **Signaler** → `horaireinter.remarque` renseigné, les heures signalées apparaissent dans un tableau séparé
- Les jours avec des heures en attente de validation sont mis en évidence

#### Récapitulatif mensuel
- Vue mensuelle avec :
  - Total des heures validées
  - Estimation de facture calculée à partir de `tarifs_suivi`
  - Section règlement : type (`typeReglement`), numéro de chèque, nombre de CESU, montant CESU complémentaire
  - Exceptions de facturation (montants supplémentaires ou remises)
- Questionnaire de satisfaction : ponctualité, régularité/relation, respect des horaires, qualité du travail (note sur 5)
- Signature de la famille (`relevemensuelfam.signerLe`)
- Téléchargement en **PDF**

---

### 4.4 Module Administration

#### Gestion des intervenants
- Liste complète des intervenants avec accès au profil détaillé
- Le profil affiche les données de `intervenants` + `candidats` (via `vue_intervenants`)
- Consultation des heures saisies avec possibilité de **modifier**, **supprimer** et **ajouter** (sans limitation de délai)
- Consultation des relevés mensuels avec visualisation des **km de trajet** et téléchargement PDF

#### Récapitulatif global des heures
- Vue synthétique de toutes les heures saisies par mois
- Exportable en **CSV**

#### Préparation à la paie
- Vue dédiée regroupant les données nécessaires au calcul de la paie
- Exportable en **CSV**

#### Fiches de relevé vierges
- Génération de fiches vierges pour une tranche de mois (par défaut : 1 an)
- Téléchargement en PDF (génération côté serveur, quelques secondes)

#### Gestion des familles
- Liste complète des familles avec accès au profil détaillé
- Consultation des récapitulatifs mensuels avec :
  - Ajout et modification d'**exceptions de facturation**
  - Téléchargement PDF

#### Gestion des signalements
- Vue des heures signalées par les familles sur les déclarations des intervenants
- L'admin peut modifier les déclarations contestées (la famille doit revalider ensuite)
- Historique des anciens signalements

#### Exceptions de facturation
- Liste globale de toutes les exceptions
- L'admin peut en ajouter pour n'importe quelle famille
- La modification d'une exception renvoie directement vers le récapitulatif mensuel concerné

#### Gestion des tarifs
- Modification des grilles tarifaires (taux horaires GE et M, frais de gestion, km, abonnement)
- Les tarifs sont **horodatés** : chaque modification crée une nouvelle entrée dans `tarifs_suivi` avec une `dateDebut`
- Le tarif appliqué à un récapitulatif est celui dont la `dateDebut` est la plus récente ≤ au mois du récapitulatif
- Le nombre de tranches horaires est configurable via `configuration.json` (`nbrPalierTarifGE`, `nbrPalierTarifM`)

---

## 5. Modèle de données

### 5.1 Tables existantes (ne pas modifier)

| Table | Rôle |
|---|---|
| `famille` | Données des familles bénéficiaires |
| `intervenants` | Données des intervenants salariés |
| `candidats` | Dossiers de candidature (liés aux intervenants) |
| `enfants` | Enfants rattachés à une famille |
| `parents` | Contacts adultes rattachés à une famille |
| `proposer` | Planning : affectation intervenant/famille/créneau |
| `prestations` | Référentiel types de prestations (GE, M…) |
| `typeadh` | Référentiel types d'adhésion |
| `factures` | Factures émises aux familles |
| `tarifs` | Tarifs fixes libellés (frais dossier, adhésion…) |
| `formations` | Catalogue des formations |
| `suivre` | Liaison intervenant ↔ formation |
| `partage` | Garde partagée entre deux familles |
| `antimatching` | Paires famille/intervenant incompatibles |
| `besoinsfamille` | Créneaux souhaités par les familles |
| `disponibilitesintervenants` | Disponibilités des candidats/intervenants |
| `users` | Comptes utilisateurs des autres applications |
| `publipostagecontrats` | Données publipostage contrats |

### 5.2 Nouvelles tables (app suivi heures)

#### `users_suivi`
Comptes de connexion propres à l'application suivi heures.

| Colonne | Type | Description |
|---|---|---|
| `id` | int(11) PK AUTO | Identifiant technique |
| `username` | varchar(255) UNIQUE | Login : numéro SS (intervenant), code PM/PGE (famille), libre (admin) |
| `role` | enum | `admin` / `intervenant` / `famille` |
| `mdp` | varchar(255) | Hash bcrypt |
| `tokenReinit` | varchar(64) | Token temporaire mot de passe oublié |
| `tokenExpire` | datetime | Expiration du token (+1h) |
| `creeLe` | datetime | Date de création du compte |

#### `tarifs_suivi`
Grilles tarifaires horodatées pour le calcul des récapitulatifs.

| Colonne | Type | Description |
|---|---|---|
| `id` | int(15) PK AUTO | Identifiant technique |
| `alheureGE` | text | JSON des tranches taux horaire garde enfants |
| `alheureM` | text | JSON des tranches taux horaire ménage |
| `fraisGestion` | text | JSON frais de gestion (selon âge et heures) |
| `parIntervention` | decimal(5,2) | Frais fixes par intervention (€) |
| `maxParIntervention` | decimal(5,2) | Plafond frais par intervention (€) |
| `KMenfants` | decimal(5,2) | Remboursement km avec enfants (€/km) |
| `abonnement` | decimal(5,2) | Frais abonnement mensuel (€) |
| `dateDebut` | varchar(7) | Mois d'entrée en vigueur format `YYYY-MM` |

#### `horaireinter`
Heures saisies par les intervenants, validées par les familles.

| Colonne | Type | Description |
|---|---|---|
| `id` | int(15) PK AUTO | Identifiant technique |
| `numFam` | varchar(10) FK | Référence `famille.numero_Famille` |
| `nomFam` | varchar(50) | Nom famille (dénormalisé pour affichage) |
| `numInter` | int(5) FK | Référence `intervenants.numSalarie_Intervenants` |
| `datePresta` | date | Date de la prestation |
| `heureDebutPresta` | time | Heure de début |
| `heureFinPresta` | time | Heure de fin |
| `typePresta` | varchar(4) | `GE` (garde enfants) ou `M` (ménage) |
| `kmAvecEnfant` | decimal(5,1) | Km parcourus avec enfants (remboursés) |
| `ajouterLe` | datetime | Date de saisie |
| `modifierLe` | datetime | Date de dernière modification |
| `desactiver` | tinyint(1) | 1 = ligne supprimée logiquement |
| `validerFam` | tinyint(1) | 1 = validée par la famille |
| `validerLe` | datetime | Date de validation famille |
| `remarque` | text | Texte du signalement famille |
| `remarqueLe` | datetime | Date du signalement |

#### `relevemensuelinter`
Relevé mensuel de l'intervenant (signature + heures hors structure).

| Colonne | Type | Description |
|---|---|---|
| `moisannee` | varchar(7) PK | Mois concerné format `MM-YYYY` |
| `numInter` | int(5) PK FK | Référence `intervenants.numSalarie_Intervenants` |
| `typePresta` | varchar(4) PK | `GE` ou `M` |
| `heureDehors` | time | Heures travaillées hors structure |
| `heureDehorsAjouterLe` | datetime | Date d'ajout des heures hors structure |
| `signer` | tinyint(1) | 1 = relevé signé par l'intervenant |
| `signerLe` | datetime | Date de signature |

#### `relevemensuelfam`
Récapitulatif mensuel famille (règlement, avis, signature).

| Colonne | Type | Description |
|---|---|---|
| `numFam` | varchar(10) PK FK | Référence `famille.numero_Famille` |
| `moisannee` | varchar(7) PK | Mois concerné format `MM-YYYY` |
| `typePresta` | varchar(4) PK | `GE` ou `M` |
| `typeReglement` | varchar(15) | Mode de règlement choisi |
| `numCheque` | varchar(15) | Numéro de chèque |
| `nbrCESU` | int(11) | Nombre de CESU |
| `montantPrincipal` | decimal(5,2) | Montant principal (€) |
| `complementCESU` | varchar(15) | Type de complément CESU |
| `montantComplement` | decimal(5,2) | Montant complément (€) |
| `libelerSupl` | text | Libellé exception de facturation |
| `montantSupl` | decimal(5,2) | Montant exception (€) |
| `avisPonctualite` | int(11) | Note ponctualité (1-5) |
| `avisReguRela` | int(11) | Note régularité / relation (1-5) |
| `avisRespectHo` | int(11) | Note respect des horaires (1-5) |
| `avisQualiteTr` | int(11) | Note qualité du travail (1-5) |
| `signerLe` | datetime | Date de signature famille |

---

## 6. Règles métier

### 6.1 Délai de saisie des heures
Un intervenant peut saisir ses heures jusqu'à **N jours** après la date de prestation, où N est défini dans `configuration.json` (`nbrJourSaisie`, défaut : 7).

### 6.2 Application des tarifs
Le tarif appliqué à un récapitulatif du mois `M` est celui dont la `dateDebut` dans `tarifs_suivi` est **la plus récente ≤ M**.

```sql
SELECT t.* FROM tarifs_suivi t
JOIN (
  SELECT dateDebut, MAX(id) AS max_id
  FROM tarifs_suivi
  GROUP BY dateDebut
) AS latest ON t.id = latest.max_id
ORDER BY t.dateDebut DESC;
```

### 6.3 Taux horaire variable (GE > 16h)
Lorsque le nombre d'heures mensuel dépasse 16h, le taux horaire est spécifique à chaque famille et provient d'une source externe (Microsoft Access). Ce taux doit idéalement être importé dans `tarifs_suivi` ou dans une future table dédiée.

### 6.4 Affichage du taux horaire
Le taux horaire ne s'affiche sur la fiche récap famille qu'**après le 20 du mois** en cours.

### 6.5 Signalement
Quand une famille signale des heures, elles apparaissent dans un tableau séparé. L'admin peut les modifier ; la famille doit ensuite les revalider.

### 6.6 Revalidation après modification admin
Toute modification admin d'une heure signalée remet `horaireinter.validerFam` à `0` pour forcer une nouvelle validation famille.

---

## 7. Architecture technique

### 7.1 Stack

| Composant | Technologie |
|---|---|
| Backend | PHP (fichiers `.php`) |
| Base de données | MySQL / MariaDB via phpMyAdmin |
| Serveur local | XAMPP (Apache + PHP + MySQL) |
| Envoi d'emails | SMTP Gmail via `sendmail` (XAMPP) |
| Génération PDF | Côté serveur PHP |
| Configuration | `app-suivis-heures/configuration.json` |

### 7.2 Configuration email (XAMPP)

**`php.ini` :**
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = noreplychaudoudoux@gmail.com
sendmail_path = "C:\xampp\sendmail\sendmail.exe" -t
```

**`sendmail.ini` :**
```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=tls
auth_username=noreplychaudoudoux@gmail.com
auth_password=maiq shik xooh sbzw
force_sender=noreplychaudoudoux@gmail.com
```

### 7.3 Fichier de configuration applicative

`app-suivis-heures/configuration.json` :
```json
{
  "nbrJourSaisie": 7,
  "nbrPalierTarifGE": 4,
  "nbrPalierTarifM": 0
}
```

| Paramètre | Description |
|---|---|
| `nbrJourSaisie` | Délai max (en jours) pour saisir une heure après la prestation |
| `nbrPalierTarifGE` | Nombre de tranches horaires pour le tarif garde enfants |
| `nbrPalierTarifM` | Nombre de tranches horaires pour le tarif ménage |

---

## 8. Configuration

### 8.1 Installation base de données

Exécuter dans `bdchaudoudoux` les `CREATE TABLE` suivants :

- `users_suivi`
- `tarifs_suivi`
- `horaireinter`
- `relevemensuelinter`
- `relevemensuelfam`

Voir le fichier `schema.puml` pour le détail complet des colonnes.

### 8.2 Compte admin par défaut

```sql
INSERT INTO users_suivi (username, role, mdp)
VALUES (
  '9.99.99.99.999.999.99',
  'admin',
  '$2y$10$TeklSxuN91pAS7wiiUbuweakFvvsvNIg91XrCTAmY7vrPIkPC4WpS'
);
-- mot de passe en clair : admin
```

---

## 9. Points à améliorer et roadmap

### 9.1 Bugs et limitations connues

| Priorité | Description |
|---|---|
| Haute | Envoi d'emails désactivé pour familles/intervenants (`mdpOublier.php` ligne 62) |
| Haute | Taux horaire > 16h récupéré manuellement depuis Microsoft Access à automatiser |
| Moyenne | Taux horaire masqué avant le 20 du mois logique à revoir |
| Faible | CSS à améliorer : section "Frais / Remboursement" (admin) et "Questionnaire de satisfaction" (famille) |

### 9.2 Nouvelles fonctionnalités prévues

#### Congés et vacances
- Intervenants et familles pourront saisir leurs dates de vacances
- Ces dates s'afficheraient dans les fiches relevé intervenant et récap famille

#### Rappels automatiques par email
- **Familles GE :** email de rappel tous les weekends + fin de mois pour valider les heures
- **Familles M :** email de rappel en fin de mois uniquement
- Condition : envoyer uniquement s'il y a des heures en attente de validation

#### Affichage des tarifs enregistrés
- Afficher la liste des tarifs avec déduplication par mois (un seul par `dateDebut`)
- Requête suggérée :
```sql
SELECT t.* FROM tarifs_suivi t
JOIN (
  SELECT dateDebut, MAX(id) AS max_id
  FROM tarifs_suivi GROUP BY dateDebut
) AS latest ON t.id = latest.max_id
```

#### Fiche vierge famille
- Génération d'une fiche récap vierge pour les familles
- Bloquée par la problématique du taux horaire Access
- Les sections règlement et questionnaire doivent revenir à leur format d'origine
- Deux lignes de tarification : une issue de l'Access, une avec les tranches horaires

---

*Document généré le mai 2026 Association Chaudoudoux*



Fonctionnalité	Fichier
Estimation facture famille (calcul tarifs)	FamilleService::calculerMontantDu → return 0.0 toujours
Questionnaire satisfaction + sauvegarde avis	FamillesControllerMVC::donnerAvis → flash sans save
Calcul km trajet intervenant	RelevesControllerMVC::kmIntervenant → $kmTotal = 0
Signalements admin (tous)	AdminControllerMVC::signalements → $signalements = []
Exceptions facturation admin	AdminControllerMVC::exceptionsFacturation → $exceptions = []
Récapitulatif global heures + CSV	AdminControllerMVC::recapitulatifHeures/Csv → vide
Préparation paie + CSV	AdminControllerMVC::preparationPaie/Csv → vide
PDF relevés (intervenant + famille)	RelevesControllerMVC::*Pdf → texte brut
PDF fiches vierges	AdminControllerMVC::fichesViergesPdf → texte brut
Gestion tarifs admin	Aucun controller TarifRepository existe mais pas de routes CRUD