#!/bin/bash
# =============================================================================
# Domain migration: paekarn.com → paekan.com (รันบน VPS)
#
# วิธีใช้:
#   export APP_BASE='/home/pcj/domains/paekarn.com/paekarnv2'
#   export PAEKAN_PUBLIC_HTML='/home/pcj/domains/paekan.com/public_html'
#   export PAEKARN_PUBLIC_HTML='/home/pcj/domains/paekarn.com/public_html'
#   export DB_NAME='...' DB_USER='...' DB_PASS='...'
#   export ENABLE_PAEKARN_REDIRECT='1'   # ตั้ง 1 หลังทดสอบ paekan.com แล้ว
#   bash scripts/migrate-domain-paekan.sh
# =============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_BASE="${APP_BASE:-/home/pcj/domains/paekarn.com/paekarnv2}"
PAEKAN_PUBLIC_HTML="${PAEKAN_PUBLIC_HTML:-/home/pcj/domains/paekan.com/public_html}"
PAEKARN_PUBLIC_HTML="${PAEKARN_PUBLIC_HTML:-/home/pcj/domains/paekarn.com/public_html}"

echo "============================================"
echo " Domain migration: paekan.com"
echo " APP_BASE=$APP_BASE"
echo " PAEKAN_PUBLIC_HTML=$PAEKAN_PUBLIC_HTML"
echo "============================================"

if [ ! -d "$APP_BASE" ]; then
  echo "ERROR: APP_BASE not found: $APP_BASE"
  exit 1
fi

echo "=== Step 1: setup paekan.com public_html ==="
export APP_BASE PAEKAN_PUBLIC_HTML
bash "$SCRIPT_DIR/setup-paekan-domain.sh"

echo "=== Step 2: sync static assets to paekan public_html ==="
if [ -d "$PAEKAN_PUBLIC_HTML" ]; then
  rsync -av \
    --exclude='uploads/' \
    --exclude='index.php' \
    --exclude='install.php' \
    --exclude='cron.php' \
    "$APP_BASE/public/" "$PAEKAN_PUBLIC_HTML/" || echo "WARN: rsync to paekan public_html failed"
fi

echo "=== Step 2b: ensure uploads symlink + .htaccess after rsync ==="
bash "$SCRIPT_DIR/setup-paekan-domain.sh"

echo "=== Step 3: DB settings (app_url, email_from) ==="
if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
  bash "$SCRIPT_DIR/migrate_domain_settings.sh"
else
  echo "  SKIP: DB credentials not set"
fi

if [ "${ENABLE_PAEKARN_REDIRECT:-0}" = "1" ]; then
  echo "=== Step 4: 301 redirect paekarn.com → paekan.com ==="
  if [ -d "$PAEKARN_PUBLIC_HTML" ]; then
    cp "$SCRIPT_DIR/redirect-paekarn.htaccess" "$PAEKARN_PUBLIC_HTML/.htaccess"
    echo "  ✓ .htaccess redirect installed"
  else
    echo "  WARN: PAEKARN_PUBLIC_HTML not found: $PAEKARN_PUBLIC_HTML"
  fi
else
  echo "=== Step 4: redirect SKIPPED (set ENABLE_PAEKARN_REDIRECT=1 after testing paekan.com) ==="
fi

echo ""
echo "============================================"
echo " ✓ migrate-domain-paekan เสร็จ"
echo " ทดสอบ: https://paekan.com"
echo " หลัง OK: ENABLE_PAEKARN_REDIRECT=1 แล้วรันใหม่"
echo "============================================"
