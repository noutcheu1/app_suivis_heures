#!/bin/sh
set -e

echo "Attente de la base de données..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME_SECONDAIRE}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
  sleep 2
done
echo "Base de données disponible."

# Si vacances_config existe déjà (schéma déjà migré via dump ou run précédent),
# on marque les migrations correspondantes comme exécutées pour éviter les erreurs
VACANCES_EXISTS=$(php -r "
\$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME_SECONDAIRE}', '${DB_USER}', '${DB_PASSWORD}');
echo \$pdo->query(\"SHOW TABLES LIKE 'vacances_config'\")->rowCount();
" 2>/dev/null || echo "0")

if [ "${VACANCES_EXISTS}" = "1" ]; then
  echo "Schéma déjà migré, synchronisation des versions..."
  php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260521160154' --add --no-interaction 2>/dev/null || true
  php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260521161443' --add --no-interaction 2>/dev/null || true
fi

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

exec "$@"
