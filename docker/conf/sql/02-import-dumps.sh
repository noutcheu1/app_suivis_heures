#!/bin/bash
set -e

DUMP_DIR="/dumps"

echo "[02] Import bdchaudoudoux..."
sed 's/DEFINER=[^ ]* //g' "${DUMP_DIR}/bdchaudoudoux (12).sql" \
    | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" bdchaudoudoux

echo "[02] Import bdchaudoudoux_horaire..."
sed 's/DEFINER=[^ ]* //g' "${DUMP_DIR}/bdchaudoudoux_horaire.sql" \
    | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" bdchaudoudoux_horaire

echo "[02] Imports terminés."
