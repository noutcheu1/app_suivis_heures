# docker/conf/sql/ Scripts d'initialisation Docker

Ce dossier est monté dans `docker-entrypoint-initdb.d/` du conteneur MariaDB.
MariaDB exécute automatiquement tous les fichiers `.sql` et `.sh` au **premier démarrage**,
dans l'**ordre alphabétique** des noms de fichier.

---

## Ordre d'exécution

```
01-init.sql          → Crée les bases + permissions
02-import-dumps.sh   → Importe les dumps production (si présents)
02_horaire.sql       → Crée les tables de l'app + migre les données
```

---

## Détail des fichiers

### `01-init.sql`
- Crée `bdchaudoudoux` et `bdchaudoudoux_horaire`
- Accorde tous les droits à l'utilisateur `chaudoudoux`
- S'exécute en premier, une seule fois

### `02-import-dumps.sh`
- Importe les dumps de production depuis `/dumps/` (volume Docker)
- Fichiers attendus :
  - `bdchaudoudoux (12).sql`  → base principale (intervenants, familles, etc.)
  - `bdchaudoudoux_horaire.sql` → base secondaire (horaireinter, tarifs2, users2, etc.)
- **Non bloquant** : si un dump est absent, l'import est ignoré avec un avertissement

### `02_horaire.sql`
- S'exécute après l'import des dumps
- Crée les nouvelles tables de l'application Symfony :
  - `users_suivi` authentification Symfony (remplace `users2`)
  - `tarifs_suivi` tarifs Doctrine (même structure que `tarifs2`)
- **Migre les données** :
  - `users2` → `users_suivi` (comptes existants, rôle détecté par le format de l'identifiant)
  - `tarifs2` → `tarifs_suivi` (données copiées à l'identique)

---

## Prérequis Dumps

Placer les dumps dans `docker/conf/dumps/` avant le premier `docker compose up` :

```
docker/conf/dumps/
├── bdchaudoudoux (12).sql       ← dump de bdchaudoudoux
└── bdchaudoudoux_horaire.sql    ← dump de bdchaudoudoux_horaire
```

Sans ces fichiers, les bases démarrent vides (uniquement le compte admin et les tarifs
d'exemple ne seront pas disponibles).

---

## Réinitialiser la base

Pour repartir d'une base propre :

```bash
docker compose down -v          # supprime les volumes
docker compose up -d            # repart de zéro
```
