#!/bin/bash
# Owner Analytics — property_lead_clicks + analytics_page_views (idempotent)
set -e

DB_USER="pcj_paekarn"
DB_PASS="Paekarn@2026!"
DB_NAME="pcj_paekarn"
MYSQL="mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME}"

echo "=== analytics_page_views ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `analytics_page_views` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `path`          VARCHAR(512)    NOT NULL,
  `property_id`   INT UNSIGNED    NULL DEFAULT NULL,
  `slug`          VARCHAR(255)    NULL DEFAULT NULL,
  `referrer_host` VARCHAR(255)    NULL DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_apv_property` (`property_id`),
  INDEX `idx_apv_created`  (`created_at`),
  INDEX `idx_apv_path`     (`path`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  analytics_page_views OK"

echo "=== property_lead_clicks ==="
$MYSQL <<'SQL'
CREATE TABLE IF NOT EXISTS `property_lead_clicks` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `property_id` INT UNSIGNED    NOT NULL,
  `unit_id`     INT UNSIGNED    NULL DEFAULT NULL,
  `click_type`  ENUM('phone','line','coupon','book') NOT NULL,
  `ip_hash`     VARCHAR(64)     NULL DEFAULT NULL,
  `user_agent`  VARCHAR(255)    NULL DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_plc_property` (`property_id`),
  INDEX `idx_plc_type`     (`click_type`),
  INDEX `idx_plc_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
echo "  property_lead_clicks OK"

echo "=== Done ==="
