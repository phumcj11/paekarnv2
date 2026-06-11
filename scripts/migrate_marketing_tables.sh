#!/bin/bash
# Marketing Tools — FB groups, post logs, lead watchlist (idempotent)
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

echo "=== marketing_fb_groups ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `marketing_fb_groups` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(200) NOT NULL,
  `url`        VARCHAR(1000) NOT NULL,
  `rules`      TEXT NULL COMMENT 'กติกากลุ่ม เช่น ห้ามโฆษณา/ห้ามวันหยุด',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mfg_owner` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  marketing_fb_groups OK"

echo "=== content_plan_post_logs ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `content_plan_post_logs` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `content_plan_id` INT UNSIGNED NOT NULL,
  `group_id`        INT UNSIGNED NULL COMMENT 'marketing_fb_groups.id',
  `posted_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note`            VARCHAR(500) NULL,
  INDEX `idx_cppl_plan` (`content_plan_id`),
  INDEX `idx_cppl_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  content_plan_post_logs OK"

echo "=== marketing_leads ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `marketing_leads` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id`       INT UNSIGNED NOT NULL,
  `property_id`    INT UNSIGNED NULL,
  `fb_post_url`    VARCHAR(1000) NULL,
  `customer_text`  TEXT NOT NULL,
  `found_at`       DATE NOT NULL,
  `pax`            TINYINT UNSIGNED NULL,
  `checkin_date`   DATE NULL,
  `checkout_date`  DATE NULL,
  `budget`         VARCHAR(100) NULL,
  `zone`           VARCHAR(100) NULL,
  `status`         ENUM('new','replied','got_lead','closed','lost') NOT NULL DEFAULT 'new',
  `ai_comment`     TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ml_owner`  (`owner_id`),
  INDEX `idx_ml_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  marketing_leads OK"

echo "=== content_plans image_url to TEXT ==="
$MYSQL <<'SQL'
ALTER TABLE `content_plans` MODIFY COLUMN `image_url` TEXT NULL;
SQL
echo "  image_url TEXT OK"

echo "=== properties social columns ==="
$MYSQL <<'SQL'
ALTER TABLE `properties`
  ADD COLUMN IF NOT EXISTS `instagram_url` VARCHAR(500) NULL AFTER `facebook_url`,
  ADD COLUMN IF NOT EXISTS `tiktok_url`    VARCHAR(500) NULL AFTER `instagram_url`;
SQL
echo "  instagram_url / tiktok_url OK"

echo "=== Done ==="
