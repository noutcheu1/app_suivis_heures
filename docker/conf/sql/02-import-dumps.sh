#!/bin/bash
# ============================================================
# 02-import-dumps.sh Import du dump de la base PRINCIPALE uniquement
# S'exécute après 01-init.sql
#
# IMPORTANT :
#   - bdchaudoudoux (principal/legacy) → alimentée par son dump (lecture seule).
#   - bdchaudoudoux_horaire (base du projet) → N'EST PAS importée depuis un dump.
#     Son schéma est construit à 100 % par les migrations Doctrine
#     (doctrine:migrations:migrate, lancé au démarrage du conteneur PHP).
# ============================================================
set -e

DUMP_DIR="/dumps"
DUMP_PRINCIPAL="${DUMP_DIR}/02-bdchaudoudoux.sql"

# ── Import de la base principale (legacy) ──────────────────
if [ ! -f "${DUMP_PRINCIPAL}" ]; then
    echo "[02-import-dumps] AVERTISSEMENT : dump principal introuvable : ${DUMP_PRINCIPAL}"
    echo "[02-import-dumps] La base bdchaudoudoux sera vide (données de référence manquantes)."
else
    echo "[02-import-dumps] Import bdchaudoudoux..."
    sed 's/DEFINER=[^ ]* //g' "${DUMP_PRINCIPAL}" \
        | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" bdchaudoudoux
    echo "[02-import-dumps] bdchaudoudoux importée."
fi

echo "[02-import-dumps] bdchaudoudoux_horaire : non importée (gérée par les migrations Doctrine)."
echo "[02-import-dumps] Terminé."
