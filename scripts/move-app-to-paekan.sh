#!/bin/bash
# =============================================================================
# ย้าย app ทั้งหมดจาก paekarn.com → paekan.com (รันบน VPS ครั้งเดียว)
#
#   OLD: /home/pcj/domains/paekarn.com/paekarnv2
#   NEW: /home/pcj/domains/paekan.com/paekarnv2
#
# วิธีใช้ manual:
#   export DB_NAME='...' DB_USER='...' DB_PASS='...'
#   bash scripts/move-app-to-paekan.sh
# =============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

OLD_APP="/home/pcj/domains/paekarn.com/paekarnv2"
NEW_APP="/home/pcj/domains/paekan.com/paekarnv2"
OLD_PUBLIC="/home/pcj/domains/paekarn.com/public_html"
NEW_PUBLIC="/home/pcj/domains/paekan.com/public_html"

echo "============================================"
echo " Move app → paekan.com"
echo " OLD_APP=$OLD_APP"
echo " NEW_APP=$NEW_APP"
echo "============================================"

mkdir -p "$(dirname "$NEW_APP")"
mkdir -p "$NEW_PUBLIC"

if [ -d "$OLD_APP/app" ] && [ ! -d "$NEW_APP/app" ]; then
  echo "=== mv app folder ==="
  mv "$OLD_APP" "$NEW_APP"
  echo "  ✓ moved $OLD_APP → $NEW_APP"
elif [ -d "$OLD_APP/app" ] && [ -d "$NEW_APP/app" ]; then
  echo "=== merge uploads/storage/config from old app ==="
  if [ -d "$OLD_APP/public/uploads" ]; then
    mkdir -p "$NEW_APP/public/uploads"
    rsync -av "$OLD_APP/public/uploads/" "$NEW_APP/public/uploads/"
    echo "  ✓ uploads merged"
  fi
  if [ -d "$OLD_APP/storage" ]; then
    mkdir -p "$NEW_APP/storage"
    rsync -av "$OLD_APP/storage/" "$NEW_APP/storage/"
    echo "  ✓ storage merged"
  fi
  if [ -f "$OLD_APP/app/Config/database.local.php" ] && [ ! -f "$NEW_APP/app/Config/database.local.php" ]; then
    cp "$OLD_APP/app/Config/database.local.php" "$NEW_APP/app/Config/database.local.php"
    echo "  ✓ database.local.php copied"
  fi
  if [ -f "$OLD_APP/app/Config/app.local.php" ] && [ ! -f "$NEW_APP/app/Config/app.local.php" ]; then
    cp "$OLD_APP/app/Config/app.local.php" "$NEW_APP/app/Config/app.local.php"
    echo "  ✓ app.local.php copied"
  fi
else
  echo "  = app already at $NEW_APP or old app not found — skip move"
fi

if [ ! -d "$NEW_APP/app" ]; then
  echo "ERROR: app not found at $NEW_APP after move"
  exit 1
fi

echo "=== consolidate uploads into app ==="
UP="$NEW_APP/public/uploads"
mkdir -p "$UP"
for src in "$OLD_APP/public/uploads" "$OLD_PUBLIC/uploads" "$NEW_PUBLIC/uploads"; do
  if [ -d "$src" ] && [ ! -L "$src" ]; then
    rsync -av "$src/" "$UP/" || true
    echo "  ✓ merged uploads from $src"
  fi
done
UPLOAD_COUNT="$(find "$UP" -type f 2>/dev/null | wc -l | tr -d ' ')"
echo "  upload files in app: $UPLOAD_COUNT"

echo "=== clean paekan public_html (remove copied uploads, use symlink via deploy) ==="
if [ -d "$NEW_PUBLIC/uploads" ] && [ ! -L "$NEW_PUBLIC/uploads" ]; then
  rm -rf "$NEW_PUBLIC/uploads"
  echo "  ✓ removed copied uploads directory"
fi
DEFAULT_INDEX="$NEW_PUBLIC/index.html"
if [ -f "$DEFAULT_INDEX" ]; then
  mv "$DEFAULT_INDEX" "$DEFAULT_INDEX.bak.$(date +%Y%m%d)" 2>/dev/null || rm -f "$DEFAULT_INDEX"
  echo "  ✓ removed default index.html"
fi

echo "=== paekarn.com → 301 redirect ==="
if [ -d "$OLD_PUBLIC" ] && [ -f "$SCRIPT_DIR/redirect-paekarn.htaccess" ]; then
  cp "$SCRIPT_DIR/redirect-paekarn.htaccess" "$OLD_PUBLIC/.htaccess"
  rm -f "$OLD_PUBLIC/index.php"
  echo "  ✓ paekarn.com redirect installed"
fi

echo "=== DB settings ==="
if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ] && [ -f "$SCRIPT_DIR/migrate_domain_settings.sh" ]; then
  bash "$SCRIPT_DIR/migrate_domain_settings.sh"
fi

echo "=== flush page cache ==="
PAGE_CACHE_DIR="$NEW_APP/storage/cache/pages"
if [ -d "$PAGE_CACHE_DIR" ]; then
  rm -f "$PAGE_CACHE_DIR"/*.cache 2>/dev/null || true
  echo "  ✓ page cache cleared"
fi

echo ""
echo "============================================"
echo " ✓ move-app-to-paekan เสร็จ"
echo ""
echo " อัปเดต GitHub Secrets:"
echo "   VPS_APP_DIR=$NEW_APP"
echo "   VPS_PUBLIC_HTML=$NEW_PUBLIC"
echo ""
echo " อัปเดต cron บน VPS (ถ้ามี):"
echo "   * * * * * php $NEW_APP/cli/cron.php"
echo "============================================"
