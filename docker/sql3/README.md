# docker/sql3/ — ARCHIVÉ — Ne pas utiliser

Ce dossier contient des **scripts expérimentaux de refactoring** qui n'ont jamais
été intégrés au Docker Compose et ne doivent **pas** être exécutés.

## Pourquoi ces scripts ne fonctionnent pas

| Fichier | Problème |
|---------|----------|
| `04-refactor-bdd.sql` | Crée `tarifs_unifiee`, `intervenants_unifie`, `famille_normalisee` — tables jamais utilisées par l'app |
| `05-migrate-tables-principales.sql` | Copie des tables depuis `bdchaudoudoux` via `CREATE TABLE AS SELECT` — casse les index et les FK |
| `06-migration-donnees.sql` | Insère dans `tarifs_unifiee` et `intervenants_unifie` — tables qui n'existent pas dans le vrai schéma |
| `07-migration-succes.sql` | Idem — références à des tables inexistantes |
| `02-bdchaudoudoux.sql` | Dump de 2.9 MB — non exécuté automatiquement |
| `03-bdchaudoudoux-horaire.sql` | Dump de 1.1 MB — non exécuté automatiquement |
| `02-import-dumps.sql` | Fichier vide (juste un SELECT message) |

## Ce qui est réellement exécuté au démarrage Docker

Voir `docker/conf/sql/` — c'est le seul dossier monté dans `docker-entrypoint-initdb.d/`.

Ordre d'exécution :
1. `01-init.sql`       — crée les DBs + permissions
2. `02-import-dumps.sh` — importe les dumps production
3. `02_horaire.sql`    — crée les nouvelles tables + migre les données
