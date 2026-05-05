#!/bin/bash
set -e

echo "🚀 Starting Symfony Docker EntryPoint..."

# ─────────────────────────────────────────────
# 1. Permissions Symfony (cache + logs)
# ─────────────────────────────────────────────
echo "🔧 Fixing permissions..."

cd /var/www/html || exit 1

mkdir -p var/cache var/log

chown -R www-data:www-data var || true
chmod -R 775 var || true

rm -rf var/cache/*

echo "✔ Permissions OK"

# ─────────────────────────────────────────────
# 2. Attente base de données
# ─────────────────────────────────────────────
echo "⏳ Waiting for database..."

until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "DB not ready..."
  sleep 2
done

echo "✔ Database ready"

# ─────────────────────────────────────────────
# 3. Migrations (SAFE MODE)
# ─────────────────────────────────────────────
echo "📦 Running migrations..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "✔ Migrations applied"

# ─────────────────────────────────────────────
# 4. Cache Symfony (important en prod)
# ─────────────────────────────────────────────
echo "⚡ Clearing cache..."

php bin/console cache:clear --env=dev || true

echo "✔ Cache OK"

# ─────────────────────────────────────────────
# 5. Lancement Apache
# ─────────────────────────────────────────────
echo "🌐 Starting Apache..."

exec apache2-foreground