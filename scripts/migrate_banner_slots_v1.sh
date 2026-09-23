#!/bin/bash
# Banner B01-B10 — เพิ่ม column และ migrate slot เดิม — idempotent
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

echo "=== migrate_banner_slots_v1 ==="

add_column banners mobile_image_path "VARCHAR(500) NULL AFTER image_path"
add_column banners alt_text "VARCHAR(255) NULL AFTER mobile_image_path"
add_column banners open_new_tab "TINYINT(1) NOT NULL DEFAULT 1 AFTER alt_text"
add_column banners priority "INT NOT NULL DEFAULT 0 AFTER sort_order"
add_column banners advertiser_name "VARCHAR(160) NULL AFTER title"

echo "=== migrate legacy slot keys to B-slots ==="
$MYSQL -e "UPDATE banners SET slot = 'home_b03' WHERE slot IN ('home_after_stats', 'home_before_featured')"
$MYSQL -e "UPDATE banners SET slot = 'home_b04' WHERE slot = 'home_before_coupon'"
$MYSQL -e "UPDATE banners SET slot = 'home_b05' WHERE slot = 'home_before_newest'"
$MYSQL -e "UPDATE banners SET slot = 'home_b09' WHERE slot = 'home_before_reviews'"
$MYSQL -e "UPDATE banners SET slot = 'home_b10' WHERE slot = 'home_before_blog'"

echo "=== Done ==="
