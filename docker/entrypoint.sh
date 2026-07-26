#!/bin/bash
set -e

echo "[entrypoint] Starting DPRD Signage..."

# 1. Run database migrations.
echo "[entrypoint] Running database migrations..."
php /var/www/html/spark migrate --all 2>&1
echo "[entrypoint] Migrations done."

# 2. Start Apache in foreground.
echo "[entrypoint] Starting Apache..."
exec apache2-foreground
