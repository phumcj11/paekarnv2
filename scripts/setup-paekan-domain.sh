#!/bin/bash
# =============================================================================
# ตั้งค่า paekan.com domain ใหม่บน VPS เดิม (ไม่ย้าย app folder)
#
# สิ่งที่ script นี้ทำ:
#   1. สร้าง public_html/index.php สำหรับ paekan.com ชี้ APP_BASE เดิม
#   2. copy (rsync) uploads มา public_html/uploads ของ paekan.com โดยตรง
#
# วิธีใช้:
#   export APP_BASE='/home/pcj/domains/paekarn.com/paekarnv2'
#   export PAEKAN_PUBLIC_HTML='/home/pcj/domains/paekan.com/public_html'
#   bash scripts/setup-paekan-domain.sh
# =============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_BASE="${APP_BASE:-/home/pcj/domains/paekarn.com/paekarnv2}"
PAEKAN_PUBLIC_HTML="${PAEKAN_PUBLIC_HTML:-/home/pcj/domains/paekan.com/public_html}"
DOMAIN="paekan.com"

echo "============================================"
echo " ตั้งค่า $DOMAIN → $APP_BASE"
echo "============================================"

if [ ! -d "$APP_BASE" ]; then
  echo "ERROR: APP_BASE ไม่พบ: $APP_BASE"
  exit 1
fi

echo "=== สร้าง $PAEKAN_PUBLIC_HTML/index.php ==="
mkdir -p "$PAEKAN_PUBLIC_HTML"
cat > "$PAEKAN_PUBLIC_HTML/index.php" <<PHP
<?php
declare(strict_types=1);
define('APP_BASE', '$APP_BASE');
require_once APP_BASE . '/app/Core/Application.php';
\App\Core\Application::boot(APP_BASE);
\App\Core\Application::run();
PHP
echo "  ✓ index.php เขียนแล้ว"

echo "=== ลบ default index.html ของ hosting (ถ้ามี) ==="
DEFAULT_INDEX="$PAEKAN_PUBLIC_HTML/index.html"
if [ -f "$DEFAULT_INDEX" ]; then
  mv "$DEFAULT_INDEX" "$DEFAULT_INDEX.bak.$(date +%Y%m%d)"
  echo "  ✓ ย้าย index.html → index.html.bak"
fi

echo "=== copy uploads (rsync) ==="
UPLOADS_SRC="$APP_BASE/public/uploads"
UPLOADS_DEST="$PAEKAN_PUBLIC_HTML/uploads"
mkdir -p "$UPLOADS_SRC"

if [ -L "$UPLOADS_DEST" ]; then
  rm -f "$UPLOADS_DEST"
fi
mkdir -p "$UPLOADS_DEST"

if [ -d "$UPLOADS_SRC" ]; then
  rsync -av "$UPLOADS_SRC/" "$UPLOADS_DEST/"
  echo "  ✓ uploads copied → $UPLOADS_DEST"
else
  echo "  WARN: uploads source not found: $UPLOADS_SRC"
fi

echo "=== copy .htaccess (routing + static files) ==="
if [ -f "$SCRIPT_DIR/paekan-public.htaccess" ]; then
  cp "$SCRIPT_DIR/paekan-public.htaccess" "$PAEKAN_PUBLIC_HTML/.htaccess"
  echo "  ✓ paekan-public.htaccess installed"
elif [ -f "$APP_BASE/public/.htaccess" ]; then
  cp "$APP_BASE/public/.htaccess" "$PAEKAN_PUBLIC_HTML/.htaccess"
  echo "  ✓ .htaccess copied"
fi

echo "=== flush homepage page cache ==="
PAGE_CACHE_DIR="$APP_BASE/storage/cache/pages"
if [ -d "$PAGE_CACHE_DIR" ]; then
  rm -f "$PAGE_CACHE_DIR"/*.cache 2>/dev/null || true
  echo "  ✓ page cache cleared"
fi

echo "=== ลิงก์ assets (css/js/images) — rsync จาก migrate script ==="
# assets ถูก rsync ใน migrate-domain-paekan.sh แล้ว ไม่ใช้ symlink

echo ""
echo "============================================"
echo " ✓ ตั้งค่าเสร็จ"
echo ""
echo " ขั้นต่อไป:"
echo "   1. ตั้ง Virtual Host / domain ใน panel ให้ชี้ $PAEKAN_PUBLIC_HTML"
echo "   2. ออก SSL: certbot --nginx -d $DOMAIN -d www.$DOMAIN"
echo "   3. ทดสอบ https://$DOMAIN"
echo "============================================"
