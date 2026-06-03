#!/bin/bash
set -e

echo "[entrypoint] Starting DPRD Signage..."

# ── 1. Jalankan migrasi database ────────────────────────────────
echo "[entrypoint] Running database migrations..."
php /var/www/html/spark migrate --all 2>&1
echo "[entrypoint] Migrations done."

# ── 2. Jalankan seeder settings WA (idempotent — aman diulang) ──
echo "[entrypoint] Seeding WA settings..."
php /var/www/html/spark db:seed WaSettingsSeeder 2>&1
echo "[entrypoint] Seeder done."

# ── 3. Start cron daemon ─────────────────────────────────────────
echo "[entrypoint] Starting cron daemon..."
service cron start

# ── 4. Start Apache (foreground) ─────────────────────────────────
echo "[entrypoint] Starting Apache..."
exec apache2-foreground
