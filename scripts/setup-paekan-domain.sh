#!/bin/bash
# =============================================================================
# ตั้งค่า paekan.com public_html (app อยู่ที่ paekan.com/paekarnv2 แล้ว)
# =============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_BASE="${APP_BASE:-/home/pcj/domains/paekan.com/paekarnv2}"
PAEKAN_PUBLIC_HTML="${PAEKAN_PUBLIC_HTML:-/home/pcj/domains/paekan.com/public_html}"

if [ ! -d "$APP_BASE/app" ]; then
  echo "ERROR: APP_BASE not found: $APP_BASE"
  exit 1
fi

mkdir -p "$PAEKAN_PUBLIC_HTML"

cat > "$PAEKAN_PUBLIC_HTML/index.php" <<PHP
<?php
declare(strict_types=1);
define('APP_BASE', '$APP_BASE');
require_once APP_BASE . '/app/Core/Application.php';
\App\Core\Application::boot(APP_BASE);
\App\Core\Application::run();
PHP

DEFAULT_INDEX="$PAEKAN_PUBLIC_HTML/index.html"
if [ -f "$DEFAULT_INDEX" ]; then
  mv "$DEFAULT_INDEX" "$PAEKAN_PUBLIC_HTML/index.html.bak.$(date +%Y%m%d)" 2>/dev/null || rm -f "$DEFAULT_INDEX"
fi

if [ -f "$APP_BASE/public/.htaccess" ]; then
  cp "$APP_BASE/public/.htaccess" "$PAEKAN_PUBLIC_HTML/.htaccess"
fi

UPLOADS_SRC="$APP_BASE/public/uploads"
UPLOADS_DEST="$PAEKAN_PUBLIC_HTML/uploads"
mkdir -p "$UPLOADS_SRC"
if [ -d "$UPLOADS_DEST" ] && [ ! -L "$UPLOADS_DEST" ]; then
  rm -rf "$UPLOADS_DEST"
fi
if [ ! -e "$UPLOADS_DEST" ]; then
  ln -s "$UPLOADS_SRC" "$UPLOADS_DEST"
fi

echo "✓ paekan.com public_html ready → $APP_BASE"
