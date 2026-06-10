#!/bin/bash
# Owner Manual Booking + LINE OA per-property — idempotent (MySQL 5.7 / MariaDB)
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

add_column() {
  local table="$1" column="$2" definition="$3"
  local exists
  exists=$($MYSQL -Nse "
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = '${column}'
  ")
  if [ "$exists" = "0" ]; then
    echo "  + ${table}.${column}"
    $MYSQL -e "ALTER TABLE \`${table}\` ADD COLUMN \`${column}\` ${definition}"
  else
    echo "  = ${table}.${column} (exists)"
  fi
}

echo "=== bookings columns ==="
add_column bookings source "ENUM('online','manual_phone','manual_line','admin') NOT NULL DEFAULT 'online'"
add_column bookings guest_line_user_id "VARCHAR(64) NULL DEFAULT NULL"
add_column bookings created_by_user_id "INT UNSIGNED NULL DEFAULT NULL"

echo "=== properties LINE OA columns ==="
add_column properties line_messaging_enabled "TINYINT(1) NOT NULL DEFAULT 0"
add_column properties line_channel_access_token "TEXT NULL DEFAULT NULL"
add_column properties line_channel_secret "VARCHAR(255) NULL DEFAULT NULL"
add_column properties line_webhook_verified "TINYINT(1) NOT NULL DEFAULT 0"

echo "=== property_line_contacts table ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `property_line_contacts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `property_id`   INT UNSIGNED NOT NULL,
  `line_user_id`  VARCHAR(64) NOT NULL,
  `display_name`  VARCHAR(200) NULL DEFAULT NULL,
  `picture_url`   VARCHAR(500) NULL DEFAULT NULL,
  `followed_at`   DATETIME NULL DEFAULT NULL,
  `unfollowed_at` DATETIME NULL DEFAULT NULL,
  `last_seen_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_property_line` (`property_id`, `line_user_id`),
  INDEX `idx_property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL

echo "=== properties Rich Menu column ==="
add_column properties line_rich_menu_id "VARCHAR(64) NULL DEFAULT NULL"

echo "=== verify ==="
$MYSQL -e "SHOW COLUMNS FROM properties LIKE 'line_messaging_enabled';"
$MYSQL -e "SHOW COLUMNS FROM properties LIKE 'line_rich_menu_id';"
$MYSQL -e "SHOW TABLES LIKE 'property_line_contacts';"
echo "=== migration complete ==="
