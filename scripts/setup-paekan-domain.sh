#!/bin/bash
# =============================================================================
# ตั้งค่า paekan.com domain ใหม่บน VPS เดิม (ไม่ย้าย app folder)
#
# สิ่งที่ script นี้ทำ:
#   1. สร้าง public_html/index.php สำหรับ paekan.com ชี้ APP_BASE เดิม
#   2. สร้าง symlink uploads
#   3. ออก SSL ด้วย certbot (ถ้ามี)
#
# วิธีใช้:
#   export APP_BASE='/home/pcj/domains/paekarn.com/paekarnv2'
#   export PAEKAN_PUBLIC_HTML='/home/pcj/domains/paekan.com/public_html'
#   bash scripts/setup-paekan-domain.sh
# =============================================================================
set -e

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

echo "=== ลิงก์ uploads ==="
UPLOADS_TARGET="$APP_BASE/public/uploads"
UPLOADS_LINK="$PAEKAN_PUBLIC_HTML/uploads"
if [ -L "$UPLOADS_LINK" ]; then
  echo "  = uploads symlink มีอยู่แล้ว"
elif [ -d "$UPLOADS_LINK" ] && [ -z "$(ls -A "$UPLOADS_LINK" 2>/dev/null)" ]; then
  rmdir "$UPLOADS_LINK"
  ln -s "$UPLOADS_TARGET" "$UPLOADS_LINK"
  echo "  ✓ สร้าง symlink uploads"
elif [ ! -e "$UPLOADS_LINK" ]; then
  ln -s "$UPLOADS_TARGET" "$UPLOADS_LINK"
  echo "  ✓ สร้าง symlink uploads"
else
  echo "  ! uploads มีข้อมูลอยู่แล้ว — ข้าม (ตรวจสอบเอง)"
fi

echo "=== ลิงก์ assets (css/js) ==="
for subdir in css js images; do
  SRC="$APP_BASE/public/assets/$subdir"
  LINK="$PAEKAN_PUBLIC_HTML/assets/$subdir"
  mkdir -p "$PAEKAN_PUBLIC_HTML/assets"
  if [ -d "$SRC" ] && [ ! -e "$LINK" ]; then
    ln -s "$SRC" "$LINK"
    echo "  ✓ symlink assets/$subdir"
  fi
done

echo "=== ลิงก์ site-logo.png ==="
LOGO_SRC="$APP_BASE/public/site-logo.png"
LOGO_LINK="$PAEKAN_PUBLIC_HTML/site-logo.png"
if [ -f "$LOGO_SRC" ] && [ ! -e "$LOGO_LINK" ]; then
  ln -s "$LOGO_SRC" "$LOGO_LINK"
  echo "  ✓ symlink site-logo.png"
fi

echo ""
echo "============================================"
echo " ✓ ตั้งค่าเสร็จ"
echo ""
echo " ขั้นต่อไป:"
echo "   1. ตั้ง Virtual Host / domain ใน panel ให้ชี้ $PAEKAN_PUBLIC_HTML"
echo "   2. ออก SSL: certbot --nginx -d $DOMAIN -d www.$DOMAIN"
echo "   3. ทดสอบ https://$DOMAIN"
echo "============================================"
