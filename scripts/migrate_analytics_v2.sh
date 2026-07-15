#!/bin/bash
# Analytics V2 — visitor/session hash, bot/internal flags, dedup, tracking_version (idempotent)
set -e

DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
  echo "ERROR: Set DB_NAME, DB_USER, and DB_PASS before running this script."
  exit 1
fi

export MYSQL_PWD="$DB_PASS"
MYSQL="mysql -u${DB_USER} ${DB_NAME}"

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

add_index() {
  local table="$1" index_name="$2" columns="$3"
  local exists
  exists=$($MYSQL -Nse "
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND INDEX_NAME = '${index_name}'
  ")
  if [ "$exists" = "0" ]; then
    echo "  + index ${table}.${index_name}"
    $MYSQL -e "ALTER TABLE \`${table}\` ADD INDEX \`${index_name}\` (${columns})"
  else
    echo "  = index ${table}.${index_name} (exists)"
  fi
}

echo "=== analytics_page_views V2 columns ==="
add_column analytics_page_views visitor_hash     "CHAR(64) NULL DEFAULT NULL COMMENT 'SHA-256 visitor id'"
add_column analytics_page_views session_hash     "CHAR(64) NULL DEFAULT NULL COMMENT 'SHA-256 session id'"
add_column analytics_page_views user_agent       "VARCHAR(255) NULL DEFAULT NULL"
add_column analytics_page_views device_type      "ENUM('mobile','tablet','desktop','unknown') NOT NULL DEFAULT 'unknown'"
add_column analytics_page_views is_bot           "TINYINT(1) NOT NULL DEFAULT 0"
add_column analytics_page_views is_internal      "TINYINT(1) NOT NULL DEFAULT 0"
add_column analytics_page_views is_counted       "TINYINT(1) NOT NULL DEFAULT 1"
add_column analytics_page_views tracking_version "TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=legacy 2=trusted'"

echo "=== property_lead_clicks V2 columns ==="
add_column property_lead_clicks visitor_hash     "CHAR(64) NULL DEFAULT NULL"
add_column property_lead_clicks session_hash     "CHAR(64) NULL DEFAULT NULL"
add_column property_lead_clicks device_type      "ENUM('mobile','tablet','desktop','unknown') NOT NULL DEFAULT 'unknown'"
add_column property_lead_clicks is_bot           "TINYINT(1) NOT NULL DEFAULT 0"
add_column property_lead_clicks is_internal      "TINYINT(1) NOT NULL DEFAULT 0"
add_column property_lead_clicks is_counted       "TINYINT(1) NOT NULL DEFAULT 1"
add_column property_lead_clicks tracking_version "TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=legacy 2=trusted'"
add_column property_lead_clicks referrer_host    "VARCHAR(255) NULL DEFAULT NULL"
add_column property_lead_clicks dedupe_reason    "VARCHAR(32) NULL DEFAULT NULL COMMENT 'duplicate_30m|bot|internal'"

echo "=== property_lead_clicks: extend click_type enum (add map) ==="
$MYSQL <<'SQL'
SET @has_map := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'property_lead_clicks'
    AND COLUMN_NAME = 'click_type'
    AND COLUMN_TYPE LIKE '%map%'
);
SET @sql := IF(@has_map = 0,
  "ALTER TABLE `property_lead_clicks` MODIFY COLUMN `click_type` ENUM('phone','line','coupon','book','map') NOT NULL",
  "SELECT 'click_type already includes map' AS info"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SQL

echo "=== analytics_page_views indexes ==="
add_index analytics_page_views idx_apv_counted_created   "(is_counted, created_at)"
add_index analytics_page_views idx_apv_visitor_created   "(visitor_hash, created_at)"
add_index analytics_page_views idx_apv_version_created   "(tracking_version, created_at)"
add_index analytics_page_views idx_apv_property_counted  "(property_id, is_counted, created_at)"

echo "=== property_lead_clicks indexes ==="
add_index property_lead_clicks idx_plc_counted_created     "(is_counted, created_at)"
add_index property_lead_clicks idx_plc_visitor_dedup       "(visitor_hash, property_id, click_type, unit_id, created_at)"
add_index property_lead_clicks idx_plc_version_created     "(tracking_version, created_at)"
add_index property_lead_clicks idx_plc_property_type_count "(property_id, click_type, is_counted, created_at)"

echo "=== mark existing rows as legacy (tracking_version=1) ==="
$MYSQL -e "UPDATE analytics_page_views SET tracking_version = 1 WHERE tracking_version IS NULL OR tracking_version = 0" 2>/dev/null || true
$MYSQL -e "UPDATE property_lead_clicks SET tracking_version = 1 WHERE tracking_version IS NULL OR tracking_version = 0" 2>/dev/null || true

echo "=== Done ==="
