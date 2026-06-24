# Guide complet — La Maison des Chaudoudoux

Documentation complète de l'application de **suivi des heures** (garde d'enfants et ménage
à domicile). Ce guide couvre **toutes les fonctionnalités**, par profil, avec les règles
métier et les cas particuliers.

> Pour un mémo rapide « comment faire X », voir [guide-utilisation.md](guide-utilisation.md).

---

## Table des matières
1. [Présentation générale](#1-présentation-générale)
2. [Connexion, comptes et sécurité](#2-connexion-comptes-et-sécurité)
3. [Espace Intervenant](#3-espace-intervenant)
4. [Espace Famille](#4-espace-famille)
5. [Espace Administrateur](#5-espace-administrateur)
6. [Le pointage par QR code](#6-le-pointage-par-qr-code)
7. [Relevés, signature et PDF](#7-relevés-signature-et-pdf)
8. [Tarification, km et facturation](#8-tarification-km-et-facturation)
9. [Règles métier importantes](#9-règles-métier-importantes)
10. [Glossaire](#10-glossaire)

---

## 1. Présentation générale

L'application gère le suivi des heures de prestations d'aide à domicile pour deux activités :
- **Garde d'enfants** (code **ENFA**)
- **Ménage** (code **MENA**)

Elle distingue **trois profils** :

| Profil | Rôle |
|---|---|
| **Administrateur** | Gère tout : intervenants, familles, planning, relevés, paie, facturation, configuration. Peut faire ce que font les deux autres profils. |
| **Intervenant(e)** | Déclare ses heures, consulte son planning, signe ses relevés. |
| **Famille** | Consulte/valide les heures, gère son QR code, voit ses factures et relevés. |

**Deux activités, deux périodes de relevé :**
- **Garde (ENFA)** : du **1er au dernier jour** du mois.
- **Ménage (MENA)** : du **25 du mois précédent au 24** du mois courant.

**Notion clé — PREST vs mandataire :** seules les familles et intervenants en mode
**prestataire (PREST)** dans le planning sont pris en compte. Les dossiers mandataires
n'apparaissent ni dans les listes, ni dans les relevés.

---

## 2. Connexion, comptes et sécurité

### Connexion
- Choix du profil (Intervenant / Famille / Admin), identifiant + mot de passe.
- **Session longue** : grâce au « se souvenir de moi », l'utilisateur reste connecté
  environ 1 an (pratique sur mobile).
- Un utilisateur déjà connecté est automatiquement redirigé vers son espace.

### Inscription
- L'utilisateur crée son compte avec son **numéro de dossier** (n° salarié ou n° famille
  déjà existant en base) et un **mot de passe** (8 caractères minimum).
- Vérification que le dossier existe avant création.

### Mot de passe oublié
- Saisie de l'identifiant → envoi d'un **code à 6 chiffres** par email (valable 1 heure).
- Saisie du code + nouveau mot de passe.

### Application mobile (PWA)
- Bannière **« Installer l'application »** sur mobile ; l'app s'ajoute à l'écran d'accueil
  et fonctionne comme une application native.

### Sécurité (côté technique)
- Contrôle d'accès par rôle au niveau du pare-feu **et** dans chaque page.
- Protection anti-CSRF sur les actions sensibles.
- Mots de passe chiffrés (bcrypt), jamais affichés ni journalisés.

---

## 3. Espace Intervenant

### 3.1 Tableau de bord (Accueil)
Affiche :
- **Statistiques du mois** (heures déclarées, validées, en attente, écarts, km),
- **Prochain créneau** prévu,
- **Familles** de l'intervenant,
- **Alertes** : relevés à signer, etc.

### 3.2 Profil
Informations personnelles, numéro de salarié, coordonnées.

### 3.3 Saisir mes heures (déclaration manuelle)
1. Type : **Garde** ou **Ménage** (les familles affichées dépendent du type et du planning).
2. Famille : une famille du planning **ou** « Occasionnelle » + saisie du nom.
3. Date, heure de début, heure de fin (sélecteur d'heures au quart d'heure).
4. Validation.

**Contrôles automatiques à la saisie :**
- Durée **strictement positive** (pas d'heure de fin ≤ heure de début).
- **Maximum 10 h** par jour.
- Pas de **chevauchement** avec une autre prestation le même jour.
- Pas de **doublon** (même famille / même jour).
- Pas de **date future**.
- Respect de la **limite de jours de saisie** configurée par l'admin.
- Blocage si le **relevé de la période est déjà signé**.

### 3.4 Mes heures
- Liste des prestations **regroupées par mois**.
- Modification / suppression possible tant que la période n'est pas **verrouillée**
  (au-delà du nombre de jours de saisie autorisé) ni **signée**.

### 3.5 Planning
Affiche les créneaux prévus de la semaine (issus du planning `proposer`).

### 3.6 Scanner QR / Pointage
Voir [section 6 — Le pointage par QR code](#6-le-pointage-par-qr-code).

### 3.7 Mes relevés
Voir [section 7 — Relevés, signature et PDF](#7-relevés-signature-et-pdf).

---

## 4. Espace Famille

### 4.1 Accueil (Mon espace)
Vue d'ensemble des prestations et informations de la famille.

### 4.2 Profil
Coordonnées de la famille, informations du logement, enfants…

### 4.3 Mon QR code
Affiche le QR code de la famille. L'intervenant le scanne pour pointer ses heures.

### 4.4 Déclarer / valider les heures
- La famille peut **déclarer** une prestation ou **valider** les heures effectuées par
  l'intervenant. Les écarts éventuels entre la déclaration intervenant et la déclaration
  famille sont visibles côté admin.

### 4.5 Mes relevés
Consultation des relevés mensuels (avec PDF).

### 4.6 Ma facture
Consulter, **enregistrer** (mode de règlement, n° de chèque…) et **imprimer** la facture.

### 4.7 Estimation de facture
Calcul prévisionnel du montant à payer.

### 4.8 Avis
Notation de la prestation : **ponctualité**, **régularité/relationnel**, **respect des
horaires**, **qualité du travail**.

### 4.9 Tarif et frais km
- Consultation du **tarif** appliqué à la famille.
- **Activation / désactivation des frais km** par service (Ménage / Garde).
- Gestion d'**exceptions** km.

---

## 5. Espace Administrateur

> L'admin peut tout faire ce que font intervenants et familles, **plus** la gestion globale.

### 5.1 Tableau de bord
Vue d'ensemble de l'activité.

### 5.2 Intervenants
- **Liste** des intervenants (prestataires actifs).
- **Détail** d'un intervenant = son **tableau de bord** complet (la page admin redirige
  vers le panel intervenant).
- **Familles par intervenant** (vue croisée).
- **Recherche** d'un intervenant.
- **Archivage** d'un intervenant.

### 5.3 Familles
- **Liste** complète + listes **filtrées par type** (Ménage / Garde).
- **Détail** d'une famille (coordonnées, enfants, planning, prestations).
- **Ajout de créneaux** au planning d'une famille.

### 5.4 Relevés des intervenants
- **Liste filtrable** par mois (période 25→24), avec :
  - filtres **signé / non signé**, **téléchargé / non téléchargé**, **dernier téléchargement**, **type** ;
  - **tri** sur chaque colonne (dont la colonne Statut) ;
  - **sélection multiple** (sauvegardée localement).
- Colonne **Statut** : pour chaque type (Ménage / Garde), indique si le relevé est
  **signé** et **à quelle date** (en vert si signé).
- Colonne **Téléchargé admin** : indique si l'admin a déjà récupéré le PDF (et quand).
- **Téléchargement par lot** des PDF des intervenants sélectionnés.

### 5.5 Préparation de la paie
- Calcul récapitulatif + **export CSV**.

### 5.6 Récapitulatif
- Vue de synthèse + **export CSV**.

### 5.7 Fiches vierges
- Génération de **fiches vierges en PDF**.

### 5.8 Signalements / Écarts
- Suivi des **écarts** entre heures déclarées par l'intervenant et validées par la famille.
- Détail des prestations par famille ; choix de la **source de facturation** d'une prestation.

### 5.9 Facturation
- **Liste des factures** des familles.
- **Détail** d'une facture, **impression** unitaire ou de **toutes** les factures.
- **Exceptions de facturation**.

### 5.10 Configuration
- **Paramètres généraux** : nombre de jours de saisie autorisés, paliers de tarifs
  (Garde / Ménage), fenêtre d'affichage du rappel « relevé à signer »…
- **Tarifs globaux** : taux horaires, frais de gestion, montant par intervention et plafond,
  km enfants, abonnement.
- **Tarif par famille** + **exonération km**.
- **Formulaires vacances** : messages affichés selon les périodes (création, activation, suppression).
- **Modèles d'email** *(voir 5.11)*.
- **Import** de données (CSV / SQL).

### 5.11 Modèles d'email (personnalisation)
Menu **Configuration → Modèles d'email**.
- L'admin modifie le **sujet** et le **message** de chaque email envoyé par l'application :
  - **Code mot de passe oublié** — variables : `{identifiant}`, `{code}`.
  - **Relevé signé (PDF)** — variable : `{libelle}` (Ménage / Garde d'enfants).
  - **Rappel relevé à signer** (préparé pour un usage futur) — variables : `{nom}`, `{mois}`.
- Les **variables** entre accolades sont remplacées automatiquement à l'envoi ; elles sont
  **cliquables pour les copier**.
- Bouton **« Revenir au texte par défaut »** par modèle.
- Si un modèle n'est pas personnalisé, le **texte par défaut** est utilisé.

---

## 6. Le pointage par QR code

Chaque famille possède un **QR code**. Il existe trois façons de pointer.

### 6.1 Scanner depuis l'application (intervenant connecté)
1. Menu **Scanner QR**.
2. Scanner le QR de la famille → bouton **Démarrer** à l'arrivée.
3. Bouton **Terminer** au départ. Heures arrondies au quart d'heure.

### 6.2 Scanner avec l'appareil photo (redirection)
Le QR encode l'adresse `/declarer/{famille}`. En le scannant avec l'appareil photo :
- **Si l'intervenant est connecté** → ouverture du compteur Démarrer/Terminer pour cette famille.
- **Sinon** → redirection vers le **pointage sans connexion** (ci-dessous).

### 6.3 Pointage SANS connexion (par numéro de téléphone)
Pour un intervenant **non connecté** :
1. Scanner le QR famille → page de pointage.
2. Saisir son **numéro de téléphone** ; le système retrouve l'intervenant et affiche son **nom**.
3. **Démarrer** (le type et le créneau prévu au planning du jour s'affichent).
4. Au départ, refaire l'opération → **Terminer** (heure de début + heure de fin récapitulées).

**Caractéristiques :**
- **Multi-appareils** : le début et la fin peuvent se faire sur des téléphones différents
  (l'état est conservé côté serveur).
- **Heure du téléphone** : l'heure utilisée est celle de l'appareil (évite tout décalage).
- **Sécurité** : l'intervenant doit être **assigné** à la famille (sinon → occasionnel),
  règle d'**un seul créneau par famille et par jour**, limitation des tentatives.
- **Famille non planifiée** : si l'intervenant n'est pas assigné à la famille scannée, le
  pointage est enregistré comme **occasionnel** (avec le vrai nom de la famille).

> Un **pointage en cours** (démarré mais pas terminé) n'apparaît dans **aucun relevé**
> tant qu'il n'a pas d'heure de fin.

---

## 7. Relevés, signature et PDF

### 7.1 Consultation et aperçu
- L'intervenant (et l'admin) accède au relevé d'un **mois** et d'un **type**.
- L'**aperçu** est une fiche éditable : tant que le relevé n'est pas signé, on peut
  corriger les heures directement.
- Au-delà de **5 familles**, le relevé est **paginé** (une fiche supplémentaire) pour
  rester lisible sur mobile.

### 7.2 Familles occasionnelles
Une prestation hors planning s'affiche **« Famille occasionnelle » + le nom** (jamais « 0 »),
de manière cohérente dans l'aperçu, le PDF, les listes et le téléchargement par lot.

### 7.3 Signature
- L'intervenant clique sur **Signer** (modal de confirmation).
- Le **PDF** du relevé est généré et **envoyé par email** à l'intervenant (copie à la structure).
- Une fois signé, le relevé est **verrouillé** (plus de modification).
- Le nom du fichier suit le format `releve_nom_service_mois.pdf`.

### 7.4 Notification « relevé à signer »
Affichée uniquement pendant la **période de fin de mois** (quelques jours avant/après),
selon la fenêtre configurée par l'admin.

### 7.5 PDF
- En-tête intervenant, tableau jours × familles, totaux d'heures et de km.
- **Heures au centième** (2h30 → 2,50).
- Gestion des **noms longs** (retour à la ligne, 3 premiers noms).
- Dates limites de signature : 25/26 (Ménage) ou 30/31 (Garde).

---

## 8. Tarification, km et facturation

### 8.1 Heures
- Calcul **au centième** (2h30 = 2,50).
- Le pointage automatique arrondit au **quart d'heure**.

### 8.2 Distances (km)
- Distance intervenant → famille calculée via une **API géographique**, puis **figée** à la
  déclaration (relevé instantané ensuite).
- Règle métier :
  - Famille **à Rennes** : **0 km** facturé, **sauf exonération** activée (alors distance réelle).
  - Famille **hors Rennes** : distance réelle.
  - Plafond de **15 km par trajet**.
  - Km comptés **par intervention** (et non par total d'heures).
- Une commande d'administration permet le **recalcul/backfill** des distances.

### 8.3 Facturation famille
Le montant se compose de : heures × taux + frais de gestion + km + abonnement + suppléments
éventuels. La famille peut renseigner son **mode de règlement** (chèque, CESU…).

### 8.4 Tarifs
- **Tarifs globaux** (par défaut) et **tarif spécifique par famille**.
- **Exonération km** activable par famille et par service.
- **Exceptions de facturation** gérées par l'admin.

---

## 9. Règles métier importantes

- **PREST uniquement** : familles/intervenants mandataires exclus partout.
- **Périodes** : Garde 1→fin de mois ; Ménage 25→24.
- **Saisie** : durée > 0, max 10 h/jour, pas de chevauchement, pas de doublon, pas de date future.
- **Verrouillage** : au-delà du nombre de jours de saisie configuré, ou dès que le relevé est signé.
- **Occasionnel** : `numFam` vide → affiché « Famille occasionnelle + nom », pas de calcul km.
- **Pointage en cours** : non comptabilisé tant qu'il n'a pas d'heure de fin.
- **Un créneau par famille et par jour** (anti-doublon de pointage).

---

## 9bis. Fonctionnalités complémentaires (souvent oubliées)

### Heures « hors structure » (autres employeurs)
En bas du relevé, l'intervenant indique le **total d'heures effectuées dans le mois pour
TOUS ses employeurs** qui le paient directement (information demandée par la structure).
- Saisie via l'écran de relevé ; stockée comme **heure « dehors »** du mois.
- N'entre pas dans le calcul des heures de prestation Chaudoudoux : c'est une **déclaration
  informative** obligatoire.

### Heures non déclarées par la famille
Côté admin/famille, on distingue les prestations **déclarées par l'intervenant** mais **pas
encore validées/déclarées par la famille** → utile pour relancer la famille.

### Questionnaire / avis qualité
La famille remplit un **questionnaire** (ponctualité, régularité, respect des horaires,
qualité). Ces avis sont enregistrés sur le relevé mensuel famille et consultables.

### Historique des relevés (famille)
La famille (et l'admin) peut consulter l'**historique** des relevés mensuels passés.

### Recherche
- **Recherche d'intervenant** et **recherche de famille** (barre de recherche) pour
  retrouver rapidement un dossier dans les listes admin.

### Import de données depuis Access
Menu **Configuration → Import**. L'admin importe des données issues d'Access vers la base
principale :
- **Import CSV** (fichier exporté d'Access, choix de la table et de la clé primaire),
- **Import SQL** (dump).
Sert à synchroniser/mettre à jour les données de référence (candidats, familles…).

### Archivage / désarchivage
- **Archiver** un intervenant ou une famille (sort des listes actives).
- **Désarchiver** un intervenant pour le réactiver.

### Calcul d'heures hybride
Le total des heures est calculé de façon cohérente entre le serveur (agrégations SQL) et
l'affichage (JavaScript), toujours **au centième**, pour éviter les écarts d'arrondi.

### Géolocalisation des distances
Le calcul des km utilise une **API de géocodage** (adresse → coordonnées) puis une distance
routière ; les appels sont **espacés** (respect des quotas) et le résultat **figé en base**.

---

## 9ter. Interfaces techniques (API & commandes)

### API interne (espace intervenant)
Utilisée par les écrans dynamiques (préfixe `/api/intervenants`) :
- **`/releve/{id}`** *(GET)* — données du relevé (alimente l'aperçu éditable).
- **`/{id}/signer`** *(GET)* — signature du relevé.
- **`/{id}/signer-email`** *(POST)* — signe **et envoie le PDF par email**.
- **`/{id}/ajout_heure_de_hors`** *(POST)* — enregistre les heures « hors structure ».

### Commandes d'administration (console)
- **`app:generer-horaires-test`** — génère des heures de test à partir du planning
  (`--semaines=N`, `--dry-run` pour simuler).
- **`app:calculer-distances`** — (re)calcule et **fige** les distances km des prestations.

### Accessibilité
Interface pensée pour un public **senior / novice** : libellés texte sur les menus,
**texte agrandi**, **contraste renforcé**, **zones cliquables ≥ 44 px**, notifications
non bloquantes (toasts) au lieu des alertes système.

---

## 10. Glossaire

| Terme | Définition |
|---|---|
| **ENFA** | Garde d'enfants. |
| **MENA** | Ménage. |
| **PREST** | Mode prestataire (seul mode pris en compte). |
| **Mandataire** | Mode non géré par l'application (exclu). |
| **Planning (`proposer`)** | Affectations prévues : qui travaille chez qui, quel jour, à quelle heure. |
| **Relevé mensuel** | Récapitulatif des heures d'un intervenant (ou d'une famille) pour un mois et un type. |
| **Famille occasionnelle** | Prestation ponctuelle pour une famille hors planning. |
| **Centième** | Format décimal des heures (2h30 = 2,50). |
| **Km trajet** | Distance facturable intervenant → famille. |
| **PWA** | Application installable sur le téléphone. |

---

*Document de référence — pour toute question, contactez l'administration de
La Maison des Chaudoudoux.*
