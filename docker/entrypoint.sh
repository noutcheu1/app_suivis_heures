#!/bin/sh
set -e

echo "Attente de la base de données..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME_SECONDAIRE}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
  sleep 2
done
echo "Base de données disponible."

# ── Construction du schéma horaire via les migrations Doctrine ──────────────
# La base bdchaudoudoux_horaire n'est PAS importée depuis un dump : son schéma
# est entièrement géré par les migrations.
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# ── Compte admin par défaut (idempotent) ───────────────────────────────────
# username : 9999999999  /  password : admin
php -r "
\$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME_SECONDAIRE}', '${DB_USER}', '${DB_PASSWORD}');
\$pdo->exec(\"INSERT IGNORE INTO users_suivi (username, role, password) VALUES (
    '9999999999',
    'admin',
    '\\\$2y\\\$13\\\$n1Nvk59rJbNfdEqLJ.ytJeJUWlOCaaJ3q1VAY8kPpChFONGsoc6FG'
)\");
echo \"Compte admin verifie.\n\";
" 2>/dev/null || echo "Seed admin ignore (table users_suivi pas encore prete ?)"

exec "$@"
