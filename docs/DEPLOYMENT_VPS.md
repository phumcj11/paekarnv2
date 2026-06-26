# Deployment Guide: Git + VPS

คู่มือนี้ใช้สำหรับเตรียมโปรเจกต์แพกาญให้พร้อม deploy จาก Git ไป VPS โดยไม่ commit secret ลง repository

## Production Assumptions

- Domain: `paekan.com` (primary) — `paekarn.com` redirects 301 to `paekan.com`
- App runtime: Pure PHP + MySQL + Tailwind CSS
- Recommended document root: `public/`
- Hosting panel split layout is supported: app code outside `public_html`, with `public_html/index.php` booting `APP_BASE`
- Writable directories:
  - `storage/cache`
  - `storage/logs`
  - `storage/backups`
  - `public/uploads`
- Cron entrypoint: `cli/cron.php`
- Web cron and installer should not be exposed in production `public_html`

## Files That Must Stay Private

ห้าม commit ไฟล์เหล่านี้:

- `app/Config/app.local.php`
- `app/Config/database.local.php`
- `.env`
- SQL dumps, backups, uploads, logs

ตัวอย่างไฟล์ที่ใช้คัดลอก:

- `app/Config/app.local.example.php`
- `app/Config/database.local.example.php`

## VPS Manual Setup

1. SSH เข้า VPS

```bash
ssh root@YOUR_VPS_HOST
```

2. Clone หรือ pull repo

```bash
git clone https://github.com/phumcj11/paekarnv2.git /var/www/paekarn
cd /var/www/paekarn
```

3. สร้าง config จริงบน VPS

```bash
cp app/Config/app.local.example.php app/Config/app.local.php
cp app/Config/database.local.example.php app/Config/database.local.php
nano app/Config/database.local.php
```

4. ติดตั้ง dependency และ build CSS

```bash
composer install --no-dev --no-interaction
npm ci
npm run build:css
```

5. ตั้ง permission

```bash
mkdir -p storage/cache storage/logs storage/backups public/uploads
chown -R www-data:www-data storage public/uploads
chmod -R 775 storage public/uploads
```

6. ตั้ง cron

```bash
* * * * * /usr/bin/php /var/www/paekarn/cli/cron.php >> /var/log/paekarn-cron.log 2>&1
```

ถ้าใช้ Hosting panel ที่แยก `public_html` ออกจากโฟลเดอร์ app ให้ทำให้ uploads เข้าถึงได้:

```bash
ln -s /path/to/app/public/uploads /path/to/public_html/uploads
```

7. ตั้ง web server ให้ document root ชี้ไปที่ `public/`

Nginx example:

```nginx
server {
    listen 80;
    server_name paekan.com www.paekan.com;
    root /var/www/paekarn/public;
    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(ht|git|env) { deny all; }
    location ~ ^/(app|database|storage|cli|scripts)/ { deny all; }
}
```

8. เปิด SSL หลัง DNS ชี้มาแล้ว

```bash
certbot --nginx -d paekan.com -d www.paekan.com
```

## Optional VPS Setup Script

`scripts/vps-setup.sh` ต้องรับ DB credential ผ่าน environment เท่านั้น:

```bash
export DB_NAME='production_db_name'
export DB_USER='production_db_user'
export DB_PASS='production_db_password'
export DOMAIN='paekan.com'
export APP_DIR='/home/pcj/domains/paekan.com/paekarnv2'
bash scripts/vps-setup.sh
```

## GitHub Actions Deployment

Workflow: `.github/workflows/deploy.yml`

ต้องตั้ง GitHub repository secrets:

- `VPS_HOST`
- `VPS_USER`
- `VPS_SSH_PRIVATE_KEY`
- `VPS_APP_DIR` → `/home/pcj/domains/paekan.com/paekarnv2`
- `VPS_PUBLIC_HTML` → `/home/pcj/domains/paekan.com/public_html`
- `VPS_FILE_OWNER`
- `VPS_DB_NAME`
- `VPS_DB_USER`
- `VPS_DB_PASS`

หมายเหตุ:

- ใช้ SSH key สำหรับ deploy แทน password
- `VPS_FILE_OWNER` คือ user/group owner ของไฟล์บน VPS เช่น `www-data` หรือ user hosting panel
- `app/Config/database.local.php` ควรถูกสร้างบน VPS และไม่ถูก overwrite โดย deploy
- Workflow จะไม่ sync `public/install.php` และ `public/cron.php` ไป `public_html`
- Workflow รัน `scripts/move-app-to-paekan.sh` อัตโนมัติครั้งแรก (ย้าย app จาก paekarn.com → paekan.com)
- `paekarn.com` ตั้ง redirect 301 ไป `paekan.com` โดยอัตโนมัติ

## Migration Scripts

Migration scripts ใน `scripts/migrate_*.sh` ต้องรันพร้อม environment:

```bash
export DB_NAME='production_db_name'
export DB_USER='production_db_user'
export DB_PASS='production_db_password'
bash scripts/migrate_password_reset.sh
bash scripts/migrate_stripe_coupon_orders.sh
```

## Stripe Payment Gateway (Coupons)

1. Admin → Settings → Commerce: เปิด Gateway, ใส่ `stripe`, keys จาก Stripe Dashboard
2. Stripe Dashboard → Webhooks → Add endpoint `https://paekan.com/webhooks/stripe`
3. เลือก event `checkout.session.completed` แล้วคัดลอก Signing secret (`whsec_...`) ไป Admin
4. ทดสอบ sandbox ด้วยบัตร `4242 4242 4242 4242`

## Pre-Deploy Checklist

- Git working tree สะอาด
- ไม่มี secret ใน tracked files
- `npm ci` ผ่าน
- `npm run build:css` ผ่าน
- `app/Config/app.local.php` บน VPS ตั้ง `env=production`, `debug=false`
- `app/Config/database.local.php` บน VPS ชี้ DB production ถูกต้อง
- Document root ชี้ `public/`
- `public/install.php` ไม่เปิดใช้งานใน production
- `public_html` ไม่มี `install.php` และ `cron.php`
- `public_html/uploads` ชี้ไปยัง uploads path ที่ PHP เขียนจริง
- `storage/*` และ `public/uploads` เขียนได้โดย PHP
- Cron ทำงานด้วย PHP CLI บน VPS
- ตั้ง SSL แล้ว
- เปลี่ยนรหัสผ่าน/secret ที่เคยถูกแชร์หรือเคยฝังในไฟล์
