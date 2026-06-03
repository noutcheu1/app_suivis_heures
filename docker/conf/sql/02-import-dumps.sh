#!/bin/bash
# ============================================================
# 02-import-dumps.sh — Import des dumps SQL de production
# S'exécute après 01-init.sql, avant 02_horaire.sql
# ============================================================
set -e

DUMP_DIR="/dumps"
DUMP_PRINCIPAL="${DUMP_DIR}/02-bdchaudoudoux.sql"
DUMP_HORAIRE="${DUMP_DIR}/bdchaudoudoux_horaire.sql"

# ── Vérification de la présence des dumps ──────────────────
if [ ! -f "${DUMP_PRINCIPAL}" ]; then
    echo "[02-import-dumps] AVERTISSEMENT : dump principal introuvable : ${DUMP_PRINCIPAL}"
    echo "[02-import-dumps] La base bdchaudoudoux sera vide (données de référence manquantes)."
else
    echo "[02-import-dumps] Import bdchaudoudoux..."
    sed 's/DEFINER=[^ ]* //g' "${DUMP_PRINCIPAL}" \
        | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" bdchaudoudoux
    echo "[02-import-dumps] bdchaudoudoux importée."
fi

if [ ! -f "${DUMP_HORAIRE}" ]; then
    echo "[02-import-dumps] AVERTISSEMENT : dump horaire introuvable : ${DUMP_HORAIRE}"
    echo "[02-import-dumps] La base bdchaudoudoux_horaire sera vide (horaireinter, tarifs2, users2 absents)."
else
    echo "[02-import-dumps] Import bdchaudoudoux_horaire..."
    sed 's/DEFINER=[^ ]* //g' "${DUMP_HORAIRE}" \
        | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" bdchaudoudoux_horaire
    echo "[02-import-dumps] bdchaudoudoux_horaire importée."
fi

echo "[02-import-dumps] Terminé."
