# Scripts d'initialisation Docker pour le projet Chaudoudoux

## Fichiers d'initialisation

### `01-init.sql`
- Crée les bases de données `bdchaudoudoux` et `bdchaudoudoux_horaire`
- Configure les permissions complètes pour l'utilisateur `chaudoudoux`
- Applique les droits de création de vues et procédures stockées
- Exécuté automatiquement au premier démarrage du container MariaDB

### `03-refactor-bdd.sql`
- **Refactoring complet de la base de données**
- Crée les tables unifiées et normalisées
- Supprime la redondance et respecte les formes normales
- Structure finale optimisée pour l'application

### `04-migration-donnees-finale.sql`
- **Migration des données vers la nouvelle structure**
- Fusion des tables `intervenants` et `candidats`
- Import des familles et tarifs
- 4,259 intervenants et 835 familles migrés

### `04-migration-donnees-succes.sql`
- **Finalisation et validation**
- Création des tables de normalisation
- Configuration des tarifs par défaut
- Validation de la structure finale

### `02-import-dumps.sql`
- Script de référence pour l'importation des dumps
- Les dumps SQL sont importés automatiquement via le volume Docker

## Déploiement

Les dumps SQL sont copiés dans le répertoire `docker/sql/` et importés automatiquement :
- `bdchaudoudoux (12).sql` → base `bdchaudoudoux`
- `bdchaudoudoux_horaire.sql` → base `bdchaudoudoux_horaire`

## Permissions

L'utilisateur `chaudoudoux` a tous les droits nécessaires sur les deux bases :
- ALL PRIVILEGES
- CREATE VIEW
- CREATE ROUTINE

## Configuration Docker Compose

Le volume `./docker/sql/:/docker-entrypoint-initdb.d/` assure l'exécution automatique de tous les scripts SQL au démarrage.
