#!/bin/bash
# รัน migration content_plans บน VPS
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "ERROR: Set DB_NAME, DB_USER, and DB_PASS before running this script."
  exit 1
fi

MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME" <<'SQL'
CREATE TABLE IF NOT EXISTS `content_plans` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id`      INT UNSIGNED NOT NULL,
  `property_id`   INT UNSIGNED NULL DEFAULT NULL,
  `post_date`     DATE NOT NULL,
  `platform`      ENUM('facebook','line','instagram','other') NOT NULL DEFAULT 'facebook',
  `status`        ENUM('draft','scheduled','published','cancelled') NOT NULL DEFAULT 'draft',
  `title`         VARCHAR(200) NOT NULL DEFAULT '',
  `body`          TEXT NOT NULL,
  `hashtags`      TEXT NULL DEFAULT NULL,
  `image_url`     VARCHAR(500) NULL DEFAULT NULL,
  `ai_generated`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_owner_date` (`owner_id`, `post_date`),
  INDEX `idx_owner_status` (`owner_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL

echo "Migration result: $?"
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME" -e "SHOW TABLES LIKE 'content_plans';"
