# Guide Administrateur La Maison des Chaudoudoux

Référence **complète** de l'espace administrateur. L'admin a tous les droits : il peut faire
ce que font les intervenants et les familles, **plus** la gestion globale ci-dessous.

> Accès : toutes les pages sont sous `/admin-mvc/...` et réservées au rôle **Administrateur**.

---

## 1. Tableau de bord
`/admin-mvc/dashboard`
Vue d'ensemble de l'activité (indicateurs globaux). Point d'entrée après connexion admin.

---

## 2. Gestion des intervenants

### 2.1 Liste des intervenants
`/admin-mvc/intervenants`
Liste de tous les intervenants **prestataires actifs** (les mandataires sont exclus).

### 2.2 Détail d'un intervenant
`/admin-mvc/intervenant/{id}`
Ouvre le **tableau de bord complet** de l'intervenant (la page redirige vers le panel
intervenant : stats du mois, prochain créneau, familles, dernières prestations…).
→ L'admin voit exactement ce que voit l'intervenant.

### 2.3 Familles par intervenant
`/admin-mvc/intervenants-familles`
Vue **croisée** : pour chaque intervenant, la liste des familles chez qui il travaille
(et les types Ménage / Garde).

### 2.4 Recherche d'un intervenant
`/intervenants-mvc/recherche`
Barre de recherche pour retrouver rapidement un dossier.

### 2.5 Archiver un intervenant
`/intervenants-mvc/archiver/{id}` *(action)*
Sort l'intervenant des listes actives (réversible : désarchivage possible).

---

## 3. Gestion des familles

### 3.1 Liste des familles
`/admin-mvc/familles`
Toutes les familles prestataires actives.

### 3.2 Familles par type
`/admin-mvc/familles/{type}` (ex. `prestMenage`, `prestGardeEnfants`)
Listes **filtrées** : uniquement les familles faisant du **Ménage** ou de la **Garde**.
Affichage simplifié (pensé pour un usage rapide).

### 3.3 Détail d'une famille
`/admin-mvc/famille/{numFam}`
Fiche complète : coordonnées, parents, enfants, planning, prestations, tarif.

### 3.4 Ajouter un créneau au planning
`/admin-mvc/famille/{numFam}/ajouter-planning` *(action)*
Ajoute une affectation (intervenant, jour, horaires, type) au planning de la famille.

### 3.5 QR codes des familles (impression)
`/admin-mvc/qrcodes-familles`
Menu **Familles → QR codes (impression)**. Sert à imprimer les **étiquettes QR** que les
intervenants scannent chez chaque famille.
- **Liste avec sélection** : cocher les familles voulues (clic **n'importe où sur la ligne**),
  **recherche**, **tri** par colonne, et **case maître** (tout / rien sur les lignes visibles).
- **Filtre « Imprimé / Non imprimé »** : chaque famille indique si son QR a **déjà été imprimé**
  (badge ✓ / ⏳). Pratique pour n'imprimer que les nouvelles.
- Bouton **Imprimer la sélection** → une page génère **automatiquement un PDF** :
  **21 QR par page**, au format étiquettes **Avery L7160** (63,5 × 38,1 mm, grille 3 × 7).
  Chaque étiquette = **QR + nom de la famille + « Scanne-moi… »**.
- Les familles imprimées passent alors à **✓ Imprimé** (le filtre se met à jour).

> 🔒 Sécurité : le QR encode un **jeton unique** (pas le numéro de client), non devinable →
> impossible d'énumérer les familles depuis l'URL. Imprimer à **100 % / Taille réelle** pour
> l'alignement des étiquettes.

> 💡 La fiche d'une famille affiche aussi, quand c'est pertinent, une carte **« Remplacements à
> prévoir »** (voir *Vacances & Congés → Congés*).

---

## 4. Relevés des intervenants

### 4.1 Liste des relevés
`/admin-mvc/releves-intervenants`
Tableau central de suivi des relevés mensuels (période 25 → 24). Pour chaque intervenant :
- **Heures** par type (Ménage / Garde / total),
- **Dernière saisie**,
- **Statut** : par type, indique si le relevé est **signé** et **à quelle date** (vert = signé),
- **Téléchargé admin** : si l'admin a déjà récupéré le PDF (et quand),
- **PDF individuel** (consulter / télécharger),
- **Type de prestation** d'après le planning.

**Filtres** : signé / non signé, téléchargé / non téléchargé, dernier téléchargement, type.
**Tri** : sur chaque colonne (dont Statut). **Sélection multiple** sauvegardée localement.

### 4.2 Téléchargement par lot
`/admin-mvc/releves-batch`
Génère/télécharge les PDF des intervenants **sélectionnés** en une fois (Ménage et/ou Garde),
dans l'**ordre de sélection**.

---

## 5. Paie & exports

### 5.1 Préparation de la paie
`/admin-mvc/prepa-paie`
Récapitulatif des heures/éléments nécessaires à la paie pour un mois donné.
- Export : `/admin-mvc/prepa-paie/csv` → fichier **CSV**.

### 5.2 Récapitulatif des heures
`/admin-mvc/recapitulatif`
Vue de synthèse des heures.
- Export : `/admin-mvc/recapitulatif/csv` → fichier **CSV**.

### 5.3 Fiches vierges
`/admin-mvc/fiches-vierges` → `/admin-mvc/fiches-vierges/pdf` *(POST)*
Génère des **fiches d'heures vierges en PDF** (à imprimer/distribuer).

---

## 6. Écarts et prestations

### 6.1 Prestations du mois (écarts)
`/admin-mvc/prestations`
Suivi des **écarts** entre heures **déclarées par l'intervenant** et **validées par la
famille** : on repère les familles avec divergences.

### 6.2 Détail des prestations d'une famille
`/admin-mvc/prestations/{numFam}`
Détail des prestations d'une famille (comparaison déclaration intervenant / famille).

### 6.3 Choisir la source de facturation
`/admin-mvc/prestation/{id}/source` *(action)*
Pour une prestation en écart, l'admin choisit quelle **source** (déclaration intervenant ou
famille) fait foi pour la facturation.

---

## 7. Facturation

### 7.1 Liste des factures
`/admin-mvc/factures`
Toutes les factures des familles.

### 7.2 Détail d'une facture
`/admin-mvc/factures/{numFam}/{moisAnnee}`
Détail du calcul (heures, taux, frais, km, abonnement, suppléments).

### 7.3 Imprimer une facture
`/admin-mvc/factures/{numFam}/{moisAnnee}/imprimer`
Version imprimable d'une facture.

### 7.4 Imprimer toutes les factures
`/admin-mvc/factures/imprimer-toutes`
Génère l'impression de **toutes** les factures d'un mois en une fois.

### 7.5 Exceptions de facturation
`/admin-mvc/exceptions-facturation`
Gestion des cas particuliers de facturation (suppléments, libellés, montants spécifiques).

---

## 8. Configuration

### 8.1 Paramètres généraux
`/admin-mvc/configuration`
- **Nombre de jours de saisie** autorisés (au-delà, les heures se verrouillent).
- **Paliers de tarifs** (Garde / Ménage).
- **Fenêtre du rappel « relevé à signer »** (jours avant/après la fin de mois).

### 8.2 Tarifs
`/admin-mvc/tarifs` → `/admin-mvc/tarifs/creer` *(POST)*
Tarifs **globaux** : taux horaires (Garde / Ménage), frais de gestion, montant par
intervention et plafond, km enfants, abonnement. Historisés par date de début.
*(Le tarif par famille et l'exonération km se gèrent depuis la fiche famille.)*

### 8.3 Vacances & Congés
Menu **Configuration → Vacances & Congés**. Une seule section, **deux onglets** :

**📋 Onglet Congés** — `/admin-mvc/conges`
La **source de vérité** des absences (familles + intervenants), déclarées **au fil de l'eau**
depuis leurs espaces, indépendamment des campagnes.
- Liste filtrable (**Familles / Intervenants**) : période, détails, **origine** (Libre / Campagne),
  **statut**.
- **Validation — la règle dépend de l'origine du congé intervenant :**
  | Origine | Qui / comment | Statut à la création |
  |---|---|---|
  | **Libre** (hors campagne) | déclaré au fil de l'eau depuis « Mes congés » | **⏳ En attente** → l'admin doit **Valider ✓ / Refuser ✕** |
  | **Campagne** | réponse au formulaire pendant une campagne | **✓ Validé d'office** (l'admin a lancé la campagne) |
  Les congés **famille** sont **validés d'office** dans tous les cas.
- Un congé **validé** peut être **remis en attente** (🔓) pour le rendre à nouveau modifiable.
- Clic sur une personne → sa page de congés (l'admin peut créer/modifier/supprimer pour elle).

> Seuls les congés **validés** comptent pour les plannings et les remplacements. Un congé
> **Libre** d'un intervenant reste sans effet **tant que l'admin ne l'a pas validé** ; un congé
> issu d'une **campagne** est pris en compte immédiatement.

**📣 Onglet Campagnes** — `/admin-mvc/vacances` (+ `creer`, `{id}/toggle`, `{id}/supprimer`)
Une campagne = un **envoi groupé** d'un formulaire aux familles et intervenants pour **collecter
leurs dates** sur une période (ex. « Vacances d'été »).
- **Créer** : titre, période concernée, **date limite de réponse**, date d'ouverture, message.
- **Statut** (brouillon → envoyée → clôturée), **activer/désactiver**, **supprimer**.
- L'envoi des emails à la date d'ouverture se fait via la commande cron
  `app:envoyer-campagnes-vacances` (voir le README).
- Familles/intervenants répondent via un **lien à jeton** (sans connexion) **ou** depuis leur espace.

### 8.3 bis Remplacements (par campagne)
`/admin-mvc/vacances/{id}/remplacements` (bouton **Remplacements** sur une campagne)
Aide à la décision pour couvrir les absences **ménage** :
- **Postes à pourvoir** : pour chaque intervenant absent, la/les famille(s) impactée(s) et les
  remplaçant·es possibles (même service, **même ville en tête**, moins chargé·e d'abord).
- **Disponibles** : qui est **libre** et **sur quelle période** — « disponible **à partir du** … »,
  en tenant compte de **ses propres congés** (fenêtres d'indisponibilité affichées).
- Statuts par poste : à pourvoir · aucun dispo (⚠ prioritaire) · suspendu (la famille préfère
  suspendre) · famille absente.

### 8.4 Modèles d'email
`/admin-mvc/emails` (+ `enregistrer`, `reinitialiser`)
Personnalisation du **sujet** et du **message** de chaque email :
- **Code mot de passe oublié** `{identifiant}`, `{code}`,
- **Relevé signé (PDF)** `{libelle}`,
- **Rappel relevé à signer** (préparé) `{nom}`, `{mois}`.
Variables cliquables pour copier ; bouton **« Revenir au texte par défaut »** ;
repli automatique sur le texte par défaut si non personnalisé.

### 8.5 Import de données (Access)
`/admin-mvc/import` (+ `import/csv`, `import/sql`)
Import vers la base principale :
- **CSV** (export Access : table cible + clé primaire),
- **SQL** (dump).

---

## 9. Récapitulatif des droits admin

| Domaine | Actions clés |
|---|---|
| **Intervenants** | Lister, voir le détail (dashboard), familles par intervenant, rechercher, archiver |
| **Familles** | Lister, filtrer par type, voir le détail, ajouter au planning, **imprimer les QR (étiquettes)** |
| **Relevés** | Liste filtrable/triable, statut signé, téléchargement par lot |
| **Paie** | Préparation paie + CSV, récapitulatif + CSV, fiches vierges PDF |
| **Écarts** | Voir les écarts, détail famille, choisir la source de facturation |
| **Facturation** | Liste, détail, imprimer une / toutes, exceptions |
| **Vacances & Congés** | Valider/refuser les congés intervenant, campagnes, **remplacements** |
| **Configuration** | Paramètres, tarifs, modèles d'email, import |

---

## 10. Rappels métier pour l'admin

- **PREST uniquement** : tout ce qui est mandataire est exclu (listes, relevés, plannings).
- **Périodes** : Garde 1→fin de mois ; Ménage 25→24.
- L'admin peut **saisir/modifier des heures pour n'importe quel intervenant** depuis sa page de suivi.
- Un relevé **signé** est verrouillé (même l'admin respecte cette règle, sauf intervention directe en base).
- Les **familles occasionnelles** apparaissent « Famille occasionnelle + nom » (pas de km).
- **Congés** : un congé intervenant **Libre** (hors campagne) doit être **validé** par l'admin
  pour compter ; un congé issu d'une **campagne** est **validé d'office**. Les congés **familles**
  sont validés d'office. Seuls les congés **validés** alimentent le matching des remplacements.
- **QR de pointage** : ils encodent un **jeton unique** (pas le numéro de client) → pensez à
  **réimprimer** les QR si vous régénérez, et à imprimer à **100 %** pour l'alignement Avery.

---

*Pour les aspects intervenant et famille, voir [guide-complet.md](guide-complet.md).*
