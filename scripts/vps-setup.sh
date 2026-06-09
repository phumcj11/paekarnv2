#!/bin/bash
# =============================================================================
# VPS Setup Script — paekarn.com (HostingLotus Cloud VPS Linux 1)
# IP: 119.59.102.235
# รัน: bash vps-setup.sh
# =============================================================================
set -e

DOMAIN="paekarn.com"
APP_DIR="/var/www/paekarn"
GITHUB_REPO="https://github.com/phumcj11/paekarnv2.git"
DB_NAME="paekarn_db"
DB_USER="paekarn"
# เปลี่ยน DB_PASS ก่อนรัน!
DB_PASS="CHANGE_THIS_PASSWORD"

echo "============================================"
echo " paekarn.com VPS Setup"
echo "============================================"

# -----------------------------------------------------------------------
# 1) อัปเดต system + ติดตั้ง packages
# -----------------------------------------------------------------------
echo "[1/9] Installing packages..."
apt update && apt upgrade -y
apt install -y \
  nginx \
  php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-gd \
  php8.2-curl php8.2-xml php8.2-intl php8.2-zip \
  mysql-server \
  certbot python3-certbot-nginx \
  nodejs npm \
  git curl unzip wget

# -----------------------------------------------------------------------
# 2) MySQL — สร้าง DB + user
# -----------------------------------------------------------------------
echo "[2/9] Setting up MySQL..."
mysql -u root <<MYSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL
echo "    DB '${DB_NAME}' + user '${DB_USER}' ready."

# -----------------------------------------------------------------------
# 3) Clone repo จาก GitHub
# -----------------------------------------------------------------------
echo "[3/9] Cloning repo..."
if [ -d "$APP_DIR/.git" ]; then
  cd "$APP_DIR" && git pull origin main
else
  git clone "$GITHUB_REPO" "$APP_DIR"
fi

# -----------------------------------------------------------------------
# 4) สร้าง config production บน VPS (ไม่ commit ขึ้น git)
# -----------------------------------------------------------------------
echo "[4/9] Writing production config..."
cat > "$APP_DIR/app/Config/database.php" <<PHP
<?php
return [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => '${DB_NAME}',
    'username'  => '${DB_USER}',
    'password'  => '${DB_PASS}',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [],
];
PHP

# เปลี่ยน env=production, debug=false
sed -i "s/'env'.*=>.*'local'/'env' => 'production'/" "$APP_DIR/app/Config/app.php"
sed -i "s/'env'.*=>.*'development'/'env' => 'production'/" "$APP_DIR/app/Config/app.php"
sed -i "s/'debug'.*=>.*true/'debug' => false/" "$APP_DIR/app/Config/app.php"
echo "    Config written."

# -----------------------------------------------------------------------
# 5) สร้าง Nginx config
# -----------------------------------------------------------------------
echo "[5/9] Writing Nginx config..."
cat > /etc/nginx/sites-available/paekarn.conf <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;

    client_max_body_size 20M;

    # Static assets with cache
    location ~* \.(css|js|jpg|jpeg|png|gif|webp|ico|woff2|svg|mp4)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize=10M \n post_max_size=10M";
        fastcgi_read_timeout 60;
    }

    # Block sensitive paths
    location ~ /\.(ht|git|env) { deny all; return 404; }
    location ~ ^/(app|database|storage|cli|scripts)/ { deny all; return 404; }

    error_log  /var/log/nginx/paekarn_error.log;
    access_log /var/log/nginx/paekarn_access.log;
}
NGINX

ln -sf /etc/nginx/sites-available/paekarn.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
echo "    Nginx ready."

# -----------------------------------------------------------------------
# 6) File permissions
# -----------------------------------------------------------------------
echo "[6/9] Setting permissions..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/public/uploads"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/public/uploads"

# -----------------------------------------------------------------------
# 7) Build CSS
# -----------------------------------------------------------------------
echo "[7/9] Building CSS..."
cd "$APP_DIR"
npm install --silent
npm run build:css
echo "    CSS built."

# -----------------------------------------------------------------------
# 8) Crontab
# -----------------------------------------------------------------------
echo "[8/9] Setting up cron..."
CRON_JOB="* * * * * /usr/bin/php ${APP_DIR}/cli/cron.php >> /var/log/paekarn-cron.log 2>&1"
(crontab -l 2>/dev/null | grep -v "paekarn"; echo "$CRON_JOB") | crontab -
echo "    Cron job added."

# -----------------------------------------------------------------------
# 9) SSH Deploy Key สำหรับ GitHub Actions
# -----------------------------------------------------------------------
echo "[9/9] Generating deploy SSH key..."
if [ ! -f /root/.ssh/id_ed25519_github ]; then
  ssh-keygen -t ed25519 -C "vps-paekarn-deploy" -f /root/.ssh/id_ed25519_github -N ""
fi
echo ""
echo "============================================"
echo " SETUP COMPLETE!"
echo "============================================"
echo ""
echo "NEXT STEPS:"
echo ""
echo "1) Import database:"
echo "   mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < /tmp/paekarnv2_export.sql"
echo ""
echo "2) Add this DEPLOY KEY to GitHub repo:"
echo "   (Settings > Deploy keys > Add key — Read access)"
echo ""
cat /root/.ssh/id_ed25519_github.pub
echo ""
echo "3) Add this PRIVATE KEY to GitHub Secrets as VPS_SSH_PRIVATE_KEY:"
echo "   (Repo > Settings > Secrets > Actions > New secret)"
echo ""
echo "4) Run Certbot SSL (after DNS points to this IP):"
echo "   certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
echo ""
echo "5) Test: curl -I http://${DOMAIN}"
echo ""
